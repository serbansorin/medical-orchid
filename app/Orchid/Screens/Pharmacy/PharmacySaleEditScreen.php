<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Enums\PaymentMethod;
use App\Models\Patient;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use App\Models\PharmacySale;
use App\Services\Pharmacy\PharmacyScope;
use App\Services\Pharmacy\SaleService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\DB;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * Sale form (designs/pharmacy-sale.md): header fields + lines editor
 * (add/remove via modal/row actions, totals recomputed in SaleService) +
 * payment modal (creates pharmacy_payments + receipt) + read-only
 * receipts/payments tables. Standalone walk-in sales AND fulfillment-linked
 * sales (sale items carry prescription_id when created by dispense).
 */
class PharmacySaleEditScreen extends Screen
{
    public ?PharmacySale $sale = null;

    public function __construct(private readonly SaleService $sales) {}

    public function query(PharmacySale $sale): iterable
    {
        $lines = $sale->exists
            ? DB::table('pharmacy_sale_items')->where('pharmacy_sale_id', $sale->id)->orderBy('id')->get()
            : collect();

        return [
            'sale' => $sale,
            'lines' => new LengthAwarePaginator($lines, $lines->count(), 25),
            'receipts' => $sale->exists
                ? DB::table('pharmacy_receipts')->where('pharmacy_sale_id', $sale->id)->orderByDesc('id')->get()
                : collect(),
            'payments' => $sale->exists
                ? DB::table('pharmacy_payments')->where('pharmacy_sale_id', $sale->id)->orderByDesc('id')->get()
                : collect(),
            'inventoryOptions' => PharmacyInventory::orderBy('medication_name')->limit(200)
                ->get()->mapWithKeys(fn (PharmacyInventory $i): array => [
                    $i->id => $i->medication_name.' ('.number_format((float) $i->price_per_unit, 2).')',
                ])->all(),
        ];
    }

    public function name(): ?string
    {
        return ($this->sale?->exists ?? false) ? __('sales.edit') : __('sales.create');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('sales.saved'))->method('save')->icon('bs.check-circle'),
            Link::make(__('sales.title'))->route('platform.sales'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('sale.pharmacy_id')
                    ->fromModel(Pharmacy::class, 'name')
                    ->title(__('sales.title'))
                    ->required(),
                Select::make('sale.customer_id')
                    ->fromModel(Patient::class, 'first_name')
                    ->empty(__('sales.walk_in'))
                    ->title(__('sales.customer')),
                Input::make('sale.discount_amount')->type('number')->step(0.01)->min(0)
                    ->title(__('sales.subtotal')),
                TextArea::make('sale.notes')->rows(2)->title(__('sales.notes')),
            ]),
            Layout::block(
                Layout::table('lines', [
                    TD::make('medication_name', __('sales.item'))
                        ->render(fn ($row): string => e($row->medication_name ?? '—')),
                    TD::make('quantity', __('sales.qty'))
                        ->render(fn ($row): string => (string) $row->quantity),
                    TD::make('unit_price', __('sales.price'))
                        ->render(fn ($row): string => number_format((float) $row->unit_price, 2)),
                    TD::make('total_price', __('sales.total'))
                        ->render(fn ($row): string => number_format((float) $row->total_price, 2)),
                    TD::make(__('sales.actions'))
                        ->render(fn ($row) => Button::make(__('sales.remove'))
                            ->method('removeLine', ['line_id' => $row->id])
                            ->icon('bs.trash')),
                ])
            )->title(__('sales.lines'))->commands([
                ModalToggle::make(__('sales.add_line'))->modal('addLineModal')->icon('bs.plus-circle'),
                ModalToggle::make(__('sales.record_payment'))->modal('payModal')->icon('bs.cash'),
            ]),
            Layout::modal('addLineModal', Layout::rows([
                Select::make('line.inventory_id')
                    ->fromModel(PharmacyInventory::class, 'medication_name')
                    ->title(__('sales.item'))->required(),
                Input::make('line.quantity')->type('number')->min(1)->value(1)
                    ->title(__('sales.qty'))->required(),
                Input::make('line.discount_percent')->type('number')->step(0.01)->min(0)->max(100)->value(0)
                    ->title(__('sales.discount')),
            ]))->title(__('sales.add_line'))->applyButton(__('sales.add_line')),
            Layout::modal('payModal', Layout::rows([
                Select::make('payment.method')
                    ->options(collect(PaymentMethod::cases())
                        ->mapWithKeys(fn (PaymentMethod $m): array => [$m->value => $m->value])->all())
                    ->title(__('sales.method'))->required(),
                Input::make('payment.amount')->type('number')->step(0.01)->min(0)
                    ->title(__('sales.amount'))->required(),
                Input::make('payment.reference')->title(__('sales.reference')),
            ]))->title(__('sales.record_payment'))->applyButton(__('sales.record_payment')),
            Layout::block(
                Layout::table('payments', [
                    TD::make('payment_method', __('sales.method'))
                        ->render(fn ($row): string => e($row->payment_method ?? '—')),
                    TD::make('amount', __('sales.amount'))
                        ->render(fn ($row): string => number_format((float) $row->amount, 2)),
                    TD::make('reference_number', __('sales.reference'))
                        ->render(fn ($row): string => e($row->reference_number ?? '—')),
                ])
            )->title(__('sales.payments')),
            Layout::block(
                Layout::table('receipts', [
                    TD::make('receipt_number', __('sales.receipts'))
                        ->render(fn ($row): string => e($row->receipt_number ?? '—')),
                    TD::make('generated_at', __('sales.date'))
                        ->render(fn ($row): string => e($row->generated_at ?? '—')),
                ])
            )->title(__('sales.receipts')),
        ];
    }

    public function save(Request $request, PharmacySale $sale): mixed
    {
        $data = $request->validate([
            'sale.pharmacy_id' => 'required|integer|exists:pharmacies,id',
            'sale.customer_id' => 'nullable|integer|exists:patients,id',
            'sale.discount_amount' => 'nullable|numeric|min:0',
            'sale.notes' => 'nullable|string|max:2000',
        ])['sale'];

        $pharmacyId = PharmacyScope::currentPharmacyId();
        if ($sale->exists && $pharmacyId !== null && (int) $sale->pharmacy_id !== $pharmacyId) {
            abort(403);
        }

        $lines = $sale->exists
            ? DB::table('pharmacy_sale_items')->where('pharmacy_sale_id', $sale->id)->get()
                ->map(fn ($r): array => [
                    'inventory_id' => $r->inventory_id,
                    'medication_name' => $r->medication_name,
                    'quantity' => $r->quantity,
                    'unit_price' => (float) $r->unit_price,
                    'discount_percent' => (float) $r->discount_percent,
                    'prescription_id' => $r->prescription_id,
                ])->all()
            : [];

        $sale = $this->sales->saveSale($data, $lines, $sale->exists ? $sale : null);

        Toast::success(__('sales.saved'));

        return redirect()->route('platform.sales.edit', $sale);
    }

    public function addLine(Request $request, PharmacySale $sale): void
    {
        $data = $request->validate([
            'line.inventory_id' => 'required|integer|exists:pharmacy_inventory,id',
            'line.quantity' => 'required|integer|min:1',
            'line.discount_percent' => 'nullable|numeric|min:0|max:100',
        ])['line'];

        $inv = PharmacyInventory::findOrFail($data['inventory_id']);
        $qty = (int) $data['quantity'];
        $discount = (float) ($data['discount_percent'] ?? 0);
        $price = (float) $inv->price_per_unit;

        DB::table('pharmacy_sale_items')->insert([
            'pharmacy_sale_id' => $sale->id,
            'inventory_id' => $inv->id,
            'medication_name' => $inv->medication_name,
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_percent' => $discount,
            'total_price' => $price * $qty * (1 - $discount / 100),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recompute($sale);

        Toast::success(__('sales.saved'));
    }

    public function removeLine(Request $request, PharmacySale $sale): void
    {
        $lineId = (int) $request->input('line_id');
        DB::table('pharmacy_sale_items')
            ->where('pharmacy_sale_id', $sale->id)->where('id', $lineId)->delete();

        $this->recompute($sale);

        Toast::success(__('sales.saved'));
    }

    public function recordPayment(Request $request, PharmacySale $sale): void
    {
        $data = $request->validate([
            'payment.method' => 'required|string',
            'payment.amount' => 'required|numeric|min:0',
            'payment.reference' => 'nullable|string|max:255',
        ])['payment'];

        $this->sales->recordPayment($sale, $data['method'], (float) $data['amount'], $data['reference'] ?? null);

        Toast::success(__('sales.paid_ok'));
    }

    private function recompute(PharmacySale $sale): void
    {
        $subtotal = (float) DB::table('pharmacy_sale_items')->where('pharmacy_sale_id', $sale->id)->sum('total_price');
        $discount = (float) ($sale->getAttribute('discount_amount') ?? 0);
        $tax = $subtotal * 0.19;
        $sale->forceFill([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax - $discount,
        ])->save();
    }
}
