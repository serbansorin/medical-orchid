<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Payment;

use App\Enums\PaymentMethod;
use App\Models\BillingInvoice;
use App\Models\Payment;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PaymentEditScreen extends Screen
{
    public ?Payment $payment = null;

    public function __construct(private readonly InvoiceService $service) {}

    public function query(): iterable
    {
        $id = request()->route('payment');
        $payment = $id ? Payment::query()->with('billing_invoice')->findOrFail($id) : new Payment;
        $this->payment = $payment;

        $methodOptions = [];
        foreach (PaymentMethod::cases() as $case) {
            $methodOptions[$case->value] = $case->label();
        }

        return [
            'payment' => $payment,
            'methodOptions' => $methodOptions,
        ];
    }

    public function name(): ?string
    {
        return $this->payment?->exists
            ? __('payments.form.edit_title', ['id' => $this->payment->id])
            : __('payments.form.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_payment', 'view_any_cart_payment'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('payments.form.save'))->icon('bs.check-circle')->method('save'),
            Link::make(__('invoices.detail.back'))->icon('bs.arrow-left')->route('platform.payments'),
        ];
    }

    public function layout(): iterable
    {
        $methodOptions = [];
        foreach (PaymentMethod::cases() as $case) {
            $methodOptions[$case->value] = $case->label();
        }

        return [
            Layout::rows([
                Select::make('payment.invoice_id')
                    ->title(__('payments.form.invoice'))
                    ->fromModel(BillingInvoice::class, 'invoice_number')
                    ->required(),

                Input::make('payment.amount')
                    ->title(__('payments.form.amount'))
                    ->type('number')
                    ->required(),

                Select::make('payment.method')
                    ->title(__('payments.form.method'))
                    ->options($methodOptions)
                    ->empty()
                    ->required(),

                DateTimer::make('payment.paid_at')
                    ->title(__('payments.form.paid_at'))
                    ->format('Y-m-d H:i')
                    ->enableTime(),

                Input::make('payment.reference')
                    ->title(__('payments.form.reference')),

                TextArea::make('payment.notes')
                    ->title(__('payments.form.notes'))
                    ->rows(2),
            ]),
        ];
    }

    public function save(Request $request): mixed
    {
        $data = $request->validate([
            'payment.invoice_id' => 'required|exists:billing_invoices,id',
            'payment.amount' => 'required|numeric|min:0.01',
            'payment.method' => 'required|string',
            'payment.paid_at' => 'nullable|date',
            'payment.reference' => 'nullable|string|max:255',
            'payment.notes' => 'nullable|string',
        ])['payment'];

        $id = request()->route('payment');

        if (! $id) {
            $invoice = BillingInvoice::query()->findOrFail((int) $data['invoice_id']);

            try {
                $this->service->recordPayment($invoice, $data, $request->user());
            } catch (InvalidArgumentException $e) {
                Toast::error($e->getMessage());

                return null;
            }

            Toast::success(__('payments.form.saved'));

            return redirect()->route('platform.invoices.detail', $invoice->id);
        }

        $payment = Payment::query()->findOrFail($id);
        $payment->fill($data);
        $payment->save();
        $this->service->recalculateTotals($payment->billing_invoice ?? BillingInvoice::query()->findOrFail((int) $data['invoice_id']));

        $invoice = $payment->fresh()->billing_invoice;

        if ($invoice) {
            $remaining = $this->service->balance($invoice);
            $invoice->status = $remaining <= 0.009 ? 'paid' : ($invoice->status === 'paid' ? 'sent' : $invoice->status);
            $invoice->save();
        }

        Toast::success(__('payments.form.saved'));

        return redirect()->route('platform.payments');
    }
}
