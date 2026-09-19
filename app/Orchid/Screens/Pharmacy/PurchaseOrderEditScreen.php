<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Enums\PurchaseOrderStatus;
use App\Models\MedicineDocument;
use App\Models\Pharmacy;
use App\Models\PharmacyPurchaseOrder;
use App\Models\PharmacySupplier;
use App\Services\Pharmacy\PharmacyScope;
use App\Services\Pharmacy\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\DB;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * PO form (designs/purchase-order.md): supplier + dates header, lines editor
 * (add/remove), status flow Save draft → Send → ★ Receive (confirm modal →
 * PurchaseOrderService::receive() increments inventory in DB::transaction())
 * → Cancel. Receiving twice blocked (service rejects non-receivable states).
 */
class PurchaseOrderEditScreen extends Screen
{
    public ?PharmacyPurchaseOrder $order = null;

    /** @var array<int, string> medication_id → name for line display. */
    public array $medNames = [];

    public function __construct(private readonly PurchaseOrderService $orders) {}

    public function query(PharmacyPurchaseOrder $order): iterable
    {
        $lines = $order->exists
            ? DB::table('pharmacy_purchase_order_items')->where('purchase_order_id', $order->id)->orderBy('id')->get()
            : collect();

        // Live FK: PO items.medication_id → medicine_documents(id) (NOT NULL).
        $this->medNames = $lines->isNotEmpty()
            ? DB::table('medicine_documents')->whereIn('id', $lines->pluck('medication_id')->all())->pluck('name', 'id')->all()
            : [];

        return [
            'order' => $order,
            'lines' => new LengthAwarePaginator($lines, $lines->count(), 25),
        ];
    }

    public function name(): ?string
    {
        return ($this->order?->exists ?? false) ? __('purchase-orders.edit') : __('purchase-orders.create');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        $bar = [
            Button::make(__('purchase-orders.saved'))->method('save')->icon('bs.check-circle'),
            Link::make(__('purchase-orders.title'))->route('platform.purchase-orders'),
        ];

        if (($this->order?->exists ?? false) && $this->order->status === PurchaseOrderStatus::Draft) {
            $bar[] = Button::make(__('purchase-orders.send'))->method('send')->icon('bs.send');
        }

        if (($this->order?->exists ?? false)
            && in_array($this->order->status, [PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::Shipped], true)) {
            $bar[] = ModalToggle::make(__('purchase-orders.receive'))
                ->modal('receiveModal')->method('receive')->icon('bs.box-seam');
        }

        if (($this->order?->exists ?? false)
            && ! in_array($this->order->status, [PurchaseOrderStatus::Received, PurchaseOrderStatus::Cancelled], true)) {
            $bar[] = Button::make(__('purchase-orders.cancel'))->method('cancel')
                ->confirm(__('purchase-orders.cancel').'?')->icon('bs.x-circle');
        }

        return $bar;
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('order.pharmacy_id')
                    ->fromModel(Pharmacy::class, 'name')
                    ->title(__('purchase-orders.pharmacy'))
                    ->required(),
                Select::make('order.supplier_id')
                    ->fromModel(PharmacySupplier::class, 'name')
                    ->title(__('purchase-orders.supplier'))
                    ->required(),
                DateTimer::make('order.expected_delivery_date')->format('Y-m-d')
                    ->title(__('purchase-orders.expected')),
                TextArea::make('order.notes')->rows(2)->title(__('purchase-orders.notes')),
            ]),
            Layout::block(
                Layout::table('lines', [
                    TD::make('medication_name', __('purchase-orders.medication'))
                        ->render(fn ($row): string => e($this->medNames[$row->medication_id] ?? '#'.$row->medication_id)),
                    TD::make('quantity_ordered', __('purchase-orders.qty_ordered'))
                        ->render(fn ($row): string => (string) $row->quantity_ordered),
                    TD::make('quantity_received', __('purchase-orders.qty_received'))
                        ->render(fn ($row): string => (string) ($row->quantity_received ?? 0)),
                    TD::make('unit_price', __('purchase-orders.unit_cost'))
                        ->render(fn ($row): string => number_format((float) $row->unit_price, 2)),
                    TD::make('batch_number', __('purchase-orders.batch'))
                        ->render(fn ($row): string => e($row->batch_number ?? '—')),
                    TD::make(__('purchase-orders.actions'))
                        ->render(fn ($row) => Button::make(__('purchase-orders.remove'))
                            ->method('removeLine', ['line_id' => $row->id])
                            ->icon('bs.trash')),
                ])
            )->title(__('purchase-orders.title'))->commands([
                ModalToggle::make(__('purchase-orders.add_line'))->modal('addLineModal')->icon('bs.plus-circle'),
            ]),
            Layout::modal('addLineModal', Layout::rows([
                // Live FK: PO items.medication_id → medicine_documents(id), NOT
                // NULL — picker over the medicine catalog (deviation from design,
                // which assumed the medications table like inventory lines).
                Select::make('line.medication_id')
                    ->fromModel(MedicineDocument::class, 'name')
                    ->title(__('purchase-orders.medication'))
                    ->required(),
                Input::make('line.quantity_ordered')->type('number')->min(1)->value(1)
                    ->title(__('purchase-orders.qty_ordered'))->required(),
                Input::make('line.unit_price')->type('number')->step(0.01)->min(0)->value(0)
                    ->title(__('purchase-orders.unit_cost'))->required(),
            ]))->title(__('purchase-orders.add_line'))->applyButton(__('purchase-orders.add_line')),
            // ★ RECEIVE confirm modal. Per-line received qty/batch/expiry are
            // optional overrides (legacy API shape); empty = full ordered qty.
            Layout::modal('receiveModal', Layout::rows([
                TextArea::make('receive.notes')->rows(2)
                    ->title(__('purchase-orders.notes'))
                    ->help(__('purchase-orders.receive_hint')),
            ]))->title(__('purchase-orders.receive_title'))->applyButton(__('purchase-orders.receive')),
        ];
    }

    public function save(Request $request, PharmacyPurchaseOrder $order): mixed
    {
        $data = $request->validate([
            'order.pharmacy_id' => 'required|integer|exists:pharmacies,id',
            'order.supplier_id' => 'required|integer|exists:pharmacy_suppliers,id',
            'order.expected_delivery_date' => 'nullable|date',
            'order.notes' => 'nullable|string|max:2000',
        ])['order'];

        $pharmacyId = PharmacyScope::currentPharmacyId();
        if ($order->exists && $pharmacyId !== null && (int) $order->pharmacy_id !== $pharmacyId) {
            abort(403);
        }

        $lines = $order->exists
            ? DB::table('pharmacy_purchase_order_items')->where('purchase_order_id', $order->id)->get()
                ->map(fn ($r): array => [
                    'medication_id' => $r->medication_id,
                    'quantity_ordered' => $r->quantity_ordered,
                    'unit_price' => (float) $r->unit_price,
                ])->all()
            : [];

        $order = $this->orders->saveOrder($data, $lines, $order->exists ? $order : null);

        Toast::success(__('purchase-orders.saved'));

        return redirect()->route('platform.purchase-orders.edit', $order);
    }

    public function addLine(Request $request, PharmacyPurchaseOrder $order): void
    {
        $data = $request->validate([
            'line.medication_id' => 'required|integer|exists:medicine_documents,id',
            'line.quantity_ordered' => 'required|integer|min:1',
            'line.unit_price' => 'required|numeric|min:0',
        ])['line'];

        $qty = (int) $data['quantity_ordered'];
        $price = (float) $data['unit_price'];
        DB::table('pharmacy_purchase_order_items')->insert([
            'purchase_order_id' => $order->id,
            'medication_id' => $data['medication_id'],
            'quantity_ordered' => $qty,
            'quantity_received' => 0,
            'unit_price' => $price,
            'total_price' => $qty * $price,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recompute($order);

        Toast::success(__('purchase-orders.saved'));
    }

    public function removeLine(Request $request, PharmacyPurchaseOrder $order): void
    {
        if ($order->status !== PurchaseOrderStatus::Draft) {
            abort(422, 'Only draft lines can be removed.');
        }
        DB::table('pharmacy_purchase_order_items')
            ->where('purchase_order_id', $order->id)->where('id', (int) $request->input('line_id'))->delete();

        $this->recompute($order);

        Toast::success(__('purchase-orders.saved'));
    }

    public function send(Request $request, PharmacyPurchaseOrder $order): void
    {
        $this->orders->submit($order);

        Toast::success(__('purchase-orders.saved'));
    }

    public function receive(Request $request, PharmacyPurchaseOrder $order): void
    {
        $request->validate(['receive.notes' => 'nullable|string|max:2000']);

        $this->orders->receive($order);

        Toast::success(__('purchase-orders.received_ok'));
    }

    public function cancel(Request $request, PharmacyPurchaseOrder $order): void
    {
        $this->orders->cancel($order);

        Toast::success(__('purchase-orders.saved'));
    }

    private function recompute(PharmacyPurchaseOrder $order): void
    {
        $subtotal = (float) DB::table('pharmacy_purchase_order_items')
            ->where('purchase_order_id', $order->id)->sum('total_price');
        $tax = $subtotal * 0.19;
        $order->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
        ])->save();
    }
}
