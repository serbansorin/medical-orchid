<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Invoice;

use App\Enums\PaymentMethod;
use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use App\Models\Product;
use App\Orchid\Layouts\Billing\InvoiceItemsLayout;
use App\Orchid\Layouts\Billing\InvoicePaymentsLayout;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BillingInvoiceScreen extends Screen
{
    public ?BillingInvoice $invoice = null;

    public function __construct(private readonly InvoiceService $service) {}

    public function query(): iterable
    {
        $id = (int) request()->route('invoice');
        $invoice = BillingInvoice::query()
            ->with(['patient', 'clinic', 'doctor', 'appointment', 'creator'])
            ->findOrFail($id);

        $this->invoice = $invoice;
        $editable = InvoiceService::isEditable($invoice);

        return [
            'invoice' => $invoice,
            'editable' => $editable,
            'paidTotal' => $this->service->paidTotal($invoice),
            'balanceDue' => $this->service->balance($invoice),
            'items' => $invoice->billing_invoice_items()->with('product')->orderBy('id')->paginate(25),
            'payments' => $invoice->payments()->orderByDesc('paid_at')->orderByDesc('id')->paginate(10),
        ];
    }

    public function name(): ?string
    {
        return __('invoices.detail.title', ['number' => $this->invoice?->invoice_number ?? '']);
    }

    public function description(): ?string
    {
        return __('invoices.detail.description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_billing_invoice'];
    }

    public function commandBar(): iterable
    {
        $invoice = $this->invoice;
        $editable = $invoice ? InvoiceService::isEditable($invoice) : false;
        $isDraft = $invoice?->status === 'draft';
        $isOpen = $invoice && ! in_array($invoice->status, ['paid', 'cancelled'], true);

        return [
            ModalToggle::make(__('invoices.detail.add_item'))
                ->icon('bs.plus-circle')
                ->modal('addItemModal')
                ->method('addItem')
                ->canSee($editable),

            ModalToggle::make(__('invoices.detail.record_payment'))
                ->icon('bs.cash-coin')
                ->modal('recordPaymentModal')
                ->method('recordPayment')
                ->type(Color::SUCCESS())
                ->canSee($isOpen),

            Button::make(__('invoices.detail.finalize'))
                ->icon('bs.send-check')
                ->method('finalize')
                ->confirm()
                ->type(Color::PRIMARY())
                ->canSee($isDraft),

            Button::make(__('invoices.detail.mark_overdue'))
                ->icon('bs.exclamation-triangle')
                ->method('markOverdue')
                ->confirm()
                ->canSee($invoice?->status === 'sent'),

            ModalToggle::make(__('invoices.detail.void'))
                ->icon('bs.x-circle')
                ->modal('voidModal')
                ->method('voidInvoice')
                ->type(Color::DANGER())
                ->canSee($isOpen),

            Link::make(__('invoices.detail.edit'))
                ->icon('bs.pencil')
                ->route('platform.invoices.edit', $invoice?->id)
                ->canSee($editable),

            Link::make(__('invoices.detail.back'))
                ->icon('bs.arrow-left')
                ->route('platform.invoices'),
        ];
    }

    public function layout(): iterable
    {
        $tab = request()->query('tab', 'overview');
        $valid = ['overview', 'items', 'payments', 'lineage'];

        if (! in_array($tab, $valid, true)) {
            $tab = 'overview';
        }

        $methodOptions = [];
        foreach (PaymentMethod::cases() as $case) {
            $methodOptions[$case->value] = $case->label();
        }

        return [
            Layout::view('orchid.billing.invoice-header'),

            Layout::tabs([
                __('invoices.detail.tabs.overview') => [
                    Layout::legend('invoice', [
                        Sight::make('invoice_number', __('invoices.list.number')),
                        Sight::make('status', __('invoices.list.status'))
                            ->render(fn (BillingInvoice $i) => __('invoices.status.'.$i->status)),
                        Sight::make('invoice_date', __('invoices.list.date'))
                            ->render(fn (BillingInvoice $i) => $i->invoice_date?->format('Y-m-d') ?? '—'),
                        Sight::make('due_date', __('invoices.list.due'))
                            ->render(fn (BillingInvoice $i) => $i->due_date?->format('Y-m-d') ?? '—'),
                        Sight::make('total_amount', __('invoices.list.total'))
                            ->render(fn (BillingInvoice $i) => number_format((float) $i->total_amount, 2)),
                        Sight::make('notes', __('invoices.form.notes'))
                            ->render(fn (BillingInvoice $i) => e($i->notes ?? '—')),
                    ]),
                ],
                __('invoices.detail.tabs.items') => [
                    InvoiceItemsLayout::class,
                ],
                __('invoices.detail.tabs.payments') => [
                    InvoicePaymentsLayout::class,
                ],
                __('invoices.detail.tabs.lineage') => [
                    Layout::legend('invoice', [
                        Sight::make('patient_id', __('invoices.detail.lineage.patient'))
                            ->render(fn (BillingInvoice $i) => e($i->patient?->full_name ?? '—')),
                        Sight::make('appointment_id', __('invoices.detail.lineage.appointment'))
                            ->render(fn (BillingInvoice $i) => $i->appointment_id ? '#'.$i->appointment_id : '—'),
                        Sight::make('medical_visit_id', __('invoices.detail.lineage.visit'))
                            ->render(fn (BillingInvoice $i) => $i->medical_visit_id ? '#'.$i->medical_visit_id : '—'),
                        Sight::make('clinic_id', __('invoices.detail.lineage.clinic'))
                            ->render(fn (BillingInvoice $i) => e($i->clinic?->name ?? '—')),
                        Sight::make('doctor_id', __('invoices.detail.lineage.doctor'))
                            ->render(fn (BillingInvoice $i) => e(($i->doctor ? trim($i->doctor->first_name.' '.$i->doctor->last_name) : '') ?: '—')),
                    ]),
                ],
            ])->activeTab($this->tabKey($tab)),

            Layout::modal('addItemModal', Layout::rows([
                Select::make('item.product_id')
                    ->title(__('invoices.detail.product'))
                    ->fromModel(Product::class, 'name')
                    ->empty(__('invoices.detail.custom_line')),
                Input::make('item.description')
                    ->title(__('invoices.detail.description'))
                    ->placeholder(__('invoices.detail.custom_line')),
                Input::make('item.quantity')
                    ->title(__('invoices.detail.quantity'))
                    ->type('number')
                    ->value(1),
                Input::make('item.unit_price')
                    ->title(__('invoices.detail.unit_price'))
                    ->type('number')
                    ->value(0),
            ]))->title(__('invoices.detail.item_modal_title'))->applyButton(__('invoices.detail.add_item')),

            Layout::modal('recordPaymentModal', Layout::rows([
                Input::make('payment.amount')
                    ->title(__('invoices.detail.amount'))
                    ->type('number')
                    ->required(),
                Select::make('payment.method')
                    ->title(__('invoices.detail.method'))
                    ->options($methodOptions)
                    ->empty()
                    ->required(),
                DateTimer::make('payment.paid_at')
                    ->title(__('invoices.detail.paid_at'))
                    ->format('Y-m-d H:i')
                    ->enableTime(),
                Input::make('payment.reference')
                    ->title(__('invoices.detail.reference')),
                TextArea::make('payment.notes')
                    ->title(__('invoices.form.notes'))
                    ->rows(2),
            ]))->title(__('invoices.detail.payment_modal_title'))->applyButton(__('invoices.detail.record_payment')),

            Layout::modal('voidModal', Layout::rows([
                Input::make('void.reason')
                    ->title(__('invoices.detail.reason'))
                    ->required(),
            ]))->title(__('invoices.detail.void_modal_title'))->applyButton(__('invoices.detail.void')),
        ];
    }

    public function addItem(Request $request): void
    {
        $invoice = $this->findInvoice();

        $data = $request->validate([
            'item.product_id' => 'nullable|exists:products,id',
            'item.description' => 'nullable|string|max:255',
            'item.quantity' => 'required|integer|min:1',
            'item.unit_price' => 'required|numeric|min:0',
        ])['item'];

        if (empty($data['description']) && ! empty($data['product_id'])) {
            $data['description'] = (string) Product::query()->whereKey($data['product_id'])->value('name');
        }

        try {
            $this->service->addItem($invoice, $data);
            Toast::success(__('invoices.detail.item_added'));
        } catch (InvalidArgumentException $e) {
            Toast::error($e->getMessage());
        }
    }

    public function removeItem(Request $request): void
    {
        $invoice = $this->findInvoice();
        $item = BillingInvoiceItem::query()->findOrFail((int) $request->input('item'));

        try {
            $this->service->removeItem($invoice, $item);
            Toast::success(__('invoices.detail.item_removed'));
        } catch (InvalidArgumentException $e) {
            Toast::error($e->getMessage());
        }
    }

    public function recordPayment(Request $request): void
    {
        $invoice = $this->findInvoice();

        $data = $request->validate([
            'payment.amount' => 'required|numeric|min:0.01',
            'payment.method' => 'required|string',
            'payment.paid_at' => 'nullable|date',
            'payment.reference' => 'nullable|string|max:255',
            'payment.notes' => 'nullable|string',
        ])['payment'];

        try {
            $this->service->recordPayment($invoice, $data, $request->user());
            Toast::success(__('invoices.detail.payment_recorded'));
        } catch (InvalidArgumentException $e) {
            Toast::error($e->getMessage());
        }
    }

    public function finalize(): void
    {
        try {
            $this->service->finalize($this->findInvoice());
            Toast::success(__('invoices.detail.finalized'));
        } catch (InvalidArgumentException $e) {
            Toast::error($e->getMessage());
        }
    }

    public function markOverdue(): void
    {
        try {
            $this->service->markOverdue($this->findInvoice());
            Toast::success(__('invoices.detail.marked_overdue'));
        } catch (InvalidArgumentException $e) {
            Toast::error($e->getMessage());
        }
    }

    public function voidInvoice(Request $request): void
    {
        $data = $request->validate(['void.reason' => 'required|string|max:500'])['void'];

        try {
            $this->service->void($this->findInvoice(), $data['reason']);
            Toast::success(__('invoices.detail.voided'));
        } catch (InvalidArgumentException $e) {
            Toast::error($e->getMessage());
        }
    }

    private function findInvoice(): BillingInvoice
    {
        return BillingInvoice::query()->findOrFail((int) request()->route('invoice'));
    }

    private function tabKey(string $tab): string
    {
        return match ($tab) {
            'items' => __('invoices.detail.tabs.items'),
            'payments' => __('invoices.detail.tabs.payments'),
            'lineage' => __('invoices.detail.tabs.lineage'),
            default => __('invoices.detail.tabs.overview'),
        };
    }
}
