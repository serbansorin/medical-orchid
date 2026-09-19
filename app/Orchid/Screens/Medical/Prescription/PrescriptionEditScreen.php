<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Prescription;

use App\Models\Prescription;
use App\Orchid\Support\ClinicalOptions;
use App\Services\Medical\PrescriptionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PrescriptionEditScreen extends Screen
{
    public ?Prescription $prescription = null;

    public function query(Prescription $prescription): iterable
    {
        $prescription->loadMissing(['prescriptionMedications', 'patient', 'doctor']);

        $medications = $prescription->prescriptionMedications
            ->map(fn ($m): array => [
                'medication_id' => $m->medication_id,
                'medication_name' => $m->medication_name,
                'dosage' => $m->dosage,
                'frequency' => $m->frequency,
                'route' => $m->route,
                'quantity' => $m->quantity,
                'duration_days' => $m->duration_days,
                'instructions' => $m->instructions,
            ])->all();

        return [
            'prescription' => $prescription,
            'medications' => $medications,
        ];
    }

    public function name(): ?string
    {
        return $this->prescription?->exists
            ? __('prescriptions.edit_title')
            : __('prescriptions.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_prescription'];
    }

    public function commandBar(): iterable
    {
        $locked = (bool) $this->prescription?->is_signed;

        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.prescriptions'),

            Button::make(__('prescriptions.cancel_rx'))
                ->icon('bs.x-circle')
                ->type(Color::DANGER)
                ->confirm(__('prescriptions.cancel_confirm'))
                ->method('cancel')
                ->canSee((bool) $this->prescription?->exists && ! $locked && $this->prescription?->status !== 'cancelled'),

            ModalToggle::make(__('prescriptions.route_button'))
                ->icon('bs.send')
                ->modal('routeModal')
                ->modalTitle(__('prescriptions.route_title'))
                ->method('routeToPharmacy')
                ->canSee((bool) $this->prescription?->exists && $locked),

            Button::make(__('prescriptions.save_sign'))
                ->icon('bs.pen')
                ->type(Color::WARNING)
                ->method('saveAndSign')
                ->canSee(! $locked),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save')
                ->canSee(! $locked),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('prescription.patient_id')
                    ->title(__('prescriptions.patient'))
                    ->options(ClinicalOptions::patients())
                    ->empty()
                    ->required(),

                Select::make('prescription.doctor_id')
                    ->title(__('prescriptions.doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty()
                    ->required(),

                Select::make('prescription.clinic_id')
                    ->title(__('prescriptions.clinic'))
                    ->options(ClinicalOptions::clinics())
                    ->empty(),

                Select::make('prescription.prescription_type')
                    ->title(__('prescriptions.type'))
                    ->options([
                        'standard' => __('prescriptions.type_standard'),
                        'compensated' => __('prescriptions.type_compensated'),
                        'narcotic' => __('prescriptions.type_narcotic'),
                        'otc' => __('prescriptions.type_otc'),
                    ])
                    ->required(),

                Input::make('prescription.diagnosis')
                    ->title(__('prescriptions.diagnosis'))
                    ->maxlength(255),

                Input::make('prescription.diagnosis_code')
                    ->title(__('prescriptions.diagnosis_code'))
                    ->maxlength(50),

                DateTimer::make('prescription.issue_date')
                    ->title(__('prescriptions.issue_date'))
                    ->format('Y-m-d')
                    ->allowInput(),

                DateTimer::make('prescription.valid_until')
                    ->title(__('prescriptions.valid_until'))
                    ->format('Y-m-d')
                    ->allowInput(),

                TextArea::make('prescription.notes')
                    ->title(__('prescriptions.notes'))
                    ->rows(3),
            ]),

            Layout::block(Layout::rows([
                Matrix::make('medications')
                    ->title(__('prescriptions.medications'))
                    ->columns([
                        'medication_name',
                        'dosage',
                        'frequency',
                        'route',
                        'quantity',
                        'duration_days',
                        'instructions',
                    ])
                    ->fields([
                        'medication_name' => Input::make()->placeholder(__('prescriptions.medication_name')),
                        'dosage' => Input::make()->placeholder(__('prescriptions.dosage')),
                        'frequency' => Input::make()->placeholder(__('prescriptions.frequency')),
                        'route' => Input::make()->placeholder(__('prescriptions.route')),
                        'quantity' => Input::make()->type('number'),
                        'duration_days' => Input::make()->type('number'),
                        'instructions' => Input::make()->placeholder(__('prescriptions.instructions')),
                    ])
                    ->maxRows(20),
            ]))->title(__('prescriptions.medications'))->description(__('prescriptions.medications_help')),

            Layout::modal('routeModal', Layout::rows([
                Select::make('pharmacy_id')
                    ->title(__('prescriptions.routed_pharmacy'))
                    ->options(ClinicalOptions::pharmacies())
                    ->empty()
                    ->required(),
            ]))->title(__('prescriptions.route_title'))->applyButton(__('prescriptions.route_button')),
        ];
    }

    public function save(Prescription $prescription, Request $request, PrescriptionService $service): RedirectResponse
    {
        $data = $this->validateData($request);

        $service->saveWithMedications($prescription, $data, (array) $request->input('medications', []));

        Toast::success(__('prescriptions.saved'));

        return redirect()->route('platform.prescriptions');
    }

    public function saveAndSign(Prescription $prescription, Request $request, PrescriptionService $service): RedirectResponse
    {
        $data = $this->validateData($request);

        $prescription = $service->saveWithMedications($prescription, $data, (array) $request->input('medications', []));

        try {
            $service->sign($prescription);
        } catch (DomainException $e) {
            Toast::error($e->getMessage());

            return redirect()->route('platform.prescriptions.edit', $prescription);
        }

        Toast::success(__('prescriptions.signed_msg'));

        return redirect()->route('platform.prescriptions');
    }

    public function routeToPharmacy(Prescription $prescription, Request $request, PrescriptionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'pharmacy_id' => ['required', 'integer', 'exists:pharmacies,id'],
        ]);

        $service->route($prescription, (int) $validated['pharmacy_id']);

        Toast::success(__('prescriptions.routed_msg'));

        return redirect()->route('platform.prescriptions.edit', $prescription);
    }

    public function cancel(Prescription $prescription): RedirectResponse
    {
        $prescription->cancel();

        Toast::info(__('prescriptions.cancelled_msg'));

        return redirect()->route('platform.prescriptions');
    }

    /** @return array<string,mixed> */
    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'prescription.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'prescription.doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'prescription.clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'prescription.prescription_type' => ['required', 'string', 'in:standard,compensated,narcotic,otc'],
            'prescription.diagnosis' => ['nullable', 'string', 'max:255'],
            'prescription.diagnosis_code' => ['nullable', 'string', 'max:50'],
            'prescription.issue_date' => ['nullable', 'date'],
            'prescription.valid_until' => ['nullable', 'date', 'after_or_equal:prescription.issue_date'],
            'prescription.notes' => ['nullable', 'string'],
        ]);

        return $validated['prescription'];
    }
}
