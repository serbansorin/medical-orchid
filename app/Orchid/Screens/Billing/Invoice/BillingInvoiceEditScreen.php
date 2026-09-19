<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Invoice;

use App\Models\Appointment;
use App\Models\BillingInvoice;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Services\Billing\InvoiceService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BillingInvoiceEditScreen extends Screen
{
    public ?BillingInvoice $invoice = null;

    public function __construct(private readonly InvoiceService $service) {}

    public function query(): iterable
    {
        $id = request()->route('invoice');
        $invoice = $id ? BillingInvoice::query()->with('billing_invoice_items')->findOrFail($id) : new BillingInvoice;
        $this->invoice = $invoice;

        return [
            'invoice' => $invoice,
        ];
    }

    public function name(): ?string
    {
        return $this->invoice?->exists
            ? __('invoices.form.edit_title', ['number' => $this->invoice->invoice_number])
            : __('invoices.form.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_billing_invoice'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('invoices.form.save'))->icon('bs.check-circle')->method('save'),
            Link::make(__('invoices.detail.back'))->icon('bs.arrow-left')->route('platform.invoices'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('invoice.patient_id')
                    ->title(__('invoices.form.patient'))
                    ->fromModel(Patient::class, 'first_name')
                    ->displayAppend('full_name')
                    ->required()
                    ->help(__('invoices.form.patient')),

                Select::make('invoice.clinic_id')
                    ->title(__('invoices.form.clinic'))
                    ->fromModel(Clinic::class, 'name')
                    ->empty(),

                Select::make('invoice.doctor_id')
                    ->title(__('invoices.form.doctor'))
                    ->fromModel(Doctor::class, 'first_name')
                    ->empty(),

                Select::make('invoice.appointment_id')
                    ->title(__('invoices.form.appointment'))
                    ->fromModel(Appointment::class, 'id')
                    ->empty(),

                Select::make('invoice.medical_visit_id')
                    ->title(__('invoices.form.visit'))
                    ->fromModel(MedicalVisit::class, 'id')
                    ->empty(),

                DateTimer::make('invoice.invoice_date')
                    ->title(__('invoices.form.invoice_date'))
                    ->format('Y-m-d')
                    ->required(),

                DateTimer::make('invoice.due_date')
                    ->title(__('invoices.form.due_date'))
                    ->format('Y-m-d'),

                Select::make('invoice.direction')
                    ->title(__('invoices.form.direction'))
                    ->options([
                        'cash_in' => __('invoices.direction.cash_in'),
                        'cash_out' => __('invoices.direction.cash_out'),
                    ])
                    ->empty(),

                Input::make('invoice.discount_amount')
                    ->title(__('invoices.form.discount'))
                    ->type('number')
                    ->value(0),

                Input::make('invoice.tax_amount')
                    ->title(__('invoices.form.tax'))
                    ->type('number')
                    ->value(0),

                TextArea::make('invoice.notes')
                    ->title(__('invoices.form.notes'))
                    ->rows(3),
            ]),
        ];
    }

    public function save(Request $request): mixed
    {
        $data = $request->validate([
            'invoice.patient_id' => 'required|exists:patients,id',
            'invoice.clinic_id' => 'nullable|exists:clinics,id',
            'invoice.doctor_id' => 'nullable|exists:doctors,id',
            'invoice.appointment_id' => 'nullable|exists:appointments,id',
            'invoice.medical_visit_id' => 'nullable|exists:medical_visits,id',
            'invoice.invoice_date' => 'required|date',
            'invoice.due_date' => 'nullable|date|after_or_equal:invoice.invoice_date',
            'invoice.direction' => 'nullable|in:cash_in,cash_out',
            'invoice.discount_amount' => 'nullable|numeric|min:0',
            'invoice.tax_amount' => 'nullable|numeric|min:0',
            'invoice.notes' => 'nullable|string',
        ])['invoice'];

        $id = request()->route('invoice');

        if (! $id) {
            $invoice = $this->service->createDraft($data, $request->user());
            Toast::success(__('invoices.form.saved'));

            return redirect()->route('platform.invoices.detail', $invoice->id);
        }

        $invoice = BillingInvoice::query()->findOrFail($id);

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            Toast::error(__('invoices.detail.void'));

            return redirect()->route('platform.invoices.detail', $invoice->id);
        }

        $invoice->fill($data);
        $invoice->save();
        $this->service->recalculateTotals($invoice);

        Toast::success(__('invoices.form.saved'));

        return redirect()->route('platform.invoices.detail', $invoice->id);
    }
}
