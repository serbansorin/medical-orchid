<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Patient;
use App\Services\Cabinet\PatientService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PatientEditScreen extends Screen
{
    public function __construct(private readonly PatientService $service) {}

    public function query(Patient $patient): array
    {
        return ['patient' => $patient];
    }

    public ?Patient $patient = null;

    public function name(): ?string
    {
        return $this->patient?->exists ? __('patients.edit_title') : __('patients.create_title');
    }

    public function description(): ?string
    {
        return __('patients.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_patient'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('patients.save'))->method('save')->type(Color::PRIMARY)->icon('bs.check-circle'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('patient.first_name')->title(__('patients.first_name'))->required()->maxlength(100),
                Input::make('patient.last_name')->title(__('patients.last_name'))->required()->maxlength(100),
                Input::make('patient.cnp')->title(__('patients.cnp'))->maxlength(20),
                Input::make('patient.date_of_birth')->type('date')->title(__('patients.dob')),
                Select::make('patient.gender')->title(__('patients.gender'))
                    ->options([
                        'male' => __('patients.gender_male'),
                        'female' => __('patients.gender_female'),
                        'other' => __('patients.gender_other'),
                    ])->empty(),
            ])->title(__('patients.section_identity')),

            Layout::rows([
                Input::make('patient.phone')->title(__('patients.phone'))->maxlength(30),
                Input::make('patient.email')->type('email')->title(__('patients.email')),
                Input::make('patient.address')->title(__('patients.address')),
                Input::make('patient.city')->title(__('patients.city')),
                Input::make('patient.county')->title(__('patients.county')),
                Input::make('patient.postal_code')->title(__('patients.postal_code'))->maxlength(20),
            ])->title(__('patients.section_contact')),

            Layout::rows([
                Input::make('patient.emergency_contact_name')->title(__('patients.emergency_contact_name'))->maxlength(100),
                Input::make('patient.emergency_contact_phone')->title(__('patients.emergency_contact_phone'))->maxlength(30),
            ])->title(__('patients.section_emergency')),
        ];
    }

    public function save(Request $request, Patient $patient): mixed
    {
        // Legacy Patient model carries no validation rules — keep CNP lenient.
        $data = $request->validate([
            'patient.first_name' => 'required|string|max:100',
            'patient.last_name' => 'required|string|max:100',
            'patient.cnp' => 'nullable|string|max:20',
            'patient.date_of_birth' => 'nullable|date',
            'patient.gender' => 'nullable|in:male,female,other',
            'patient.phone' => 'nullable|string|max:30',
            'patient.email' => 'nullable|email|max:255',
            'patient.address' => 'nullable|string',
            'patient.city' => 'nullable|string|max:100',
            'patient.county' => 'nullable|string|max:100',
            'patient.postal_code' => 'nullable|string|max:20',
            'patient.emergency_contact_name' => 'nullable|string|max:100',
            'patient.emergency_contact_phone' => 'nullable|string|max:30',
        ])['patient'];

        $saved = $patient->exists
            ? $this->service->update($patient, $data)
            : $this->service->create($data);

        Toast::success(__('patients.saved'));

        return redirect()->route('platform.patients.detail', $saved);
    }
}
