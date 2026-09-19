<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Diagnostic;

use App\Enums\PatientDiagnosticStatus;
use App\Models\Appointment;
use App\Models\PatientDiagnostic;
use App\Orchid\Support\ClinicalOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PatientDiagnosticEditScreen extends Screen
{
    public ?PatientDiagnostic $diagnostic = null;

    public function query(PatientDiagnostic $diagnostic): iterable
    {
        $diagnostic->loadMissing(['conditions', 'appointments']);

        return [
            'diagnostic' => $diagnostic,
            'condition_ids' => $diagnostic->conditions->pluck('id')->all(),
            'appointment_ids' => $diagnostic->appointments->pluck('id')->all(),
        ];
    }

    public function name(): ?string
    {
        return $this->diagnostic?->exists
            ? __('diagnostics.edit_title')
            : __('diagnostics.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_record'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.diagnostics'),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $statuses = [];
        foreach (PatientDiagnosticStatus::cases() as $case) {
            $statuses[$case->value] = $case->label();
        }

        return [
            Layout::rows([
                Select::make('diagnostic.patient_id')
                    ->title(__('diagnostics.patient'))
                    ->options(ClinicalOptions::patients())
                    ->empty()
                    ->required(),

                Select::make('diagnostic.doctor_id')
                    ->title(__('diagnostics.doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty(),

                Select::make('diagnostic.main_doctor_id')
                    ->title(__('diagnostics.main_doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty(),

                Input::make('diagnostic.title')
                    ->title(__('diagnostics.title'))
                    ->maxlength(255)
                    ->required(),

                TextArea::make('diagnostic.chief_complaint')
                    ->title(__('diagnostics.chief_complaint'))
                    ->rows(3),

                Select::make('diagnostic.status')
                    ->title(__('diagnostics.status'))
                    ->options($statuses)
                    ->required(),

                DateTimer::make('diagnostic.start_date')
                    ->title(__('diagnostics.start_date'))
                    ->format('Y-m-d')
                    ->allowInput()
                    ->required(),

                DateTimer::make('diagnostic.end_date')
                    ->title(__('diagnostics.end_date'))
                    ->format('Y-m-d')
                    ->allowInput(),

                TextArea::make('diagnostic.notes')
                    ->title(__('diagnostics.notes'))
                    ->rows(3),

                Select::make('condition_ids.')
                    ->title(__('diagnostics.conditions'))
                    ->options(ClinicalOptions::conditions())
                    ->multiple(),

                Select::make('appointment_ids.')
                    ->title(__('diagnostics.appointments'))
                    ->fromModel(Appointment::class, 'id')
                    ->multiple(),
            ]),
        ];
    }

    public function save(PatientDiagnostic $diagnostic, Request $request): RedirectResponse
    {
        $statusValues = array_map(fn (PatientDiagnosticStatus $c): string => $c->value, PatientDiagnosticStatus::cases());

        $validated = $request->validate([
            'diagnostic.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'diagnostic.doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'diagnostic.main_doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'diagnostic.appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'diagnostic.title' => ['required', 'string', 'max:255'],
            'diagnostic.chief_complaint' => ['nullable', 'string'],
            'diagnostic.status' => ['required', 'string', Rule::in($statusValues)],
            'diagnostic.start_date' => ['required', 'date'],
            'diagnostic.end_date' => ['nullable', 'date', 'after_or_equal:diagnostic.start_date'],
            'diagnostic.notes' => ['nullable', 'string'],
            'condition_ids' => ['nullable', 'array'],
            'condition_ids.*' => ['integer', 'exists:conditions,id'],
            'appointment_ids' => ['nullable', 'array'],
            'appointment_ids.*' => ['integer', 'exists:appointments,id'],
        ]);

        $diagnostic->fill($validated['diagnostic']);
        $diagnostic->save();

        $diagnostic->conditions()->sync($validated['condition_ids'] ?? []);

        $primaryId = $diagnostic->appointments()->wherePivot('is_primary', true)->pluck('appointments.id')->first();
        $sync = [];
        foreach ((array) ($validated['appointment_ids'] ?? []) as $appointmentId) {
            $sync[(int) $appointmentId] = ['is_primary' => (int) $primaryId === (int) $appointmentId];
        }
        $diagnostic->appointments()->sync($sync);

        Toast::success(__('diagnostics.saved'));

        return redirect()->route('platform.diagnostics');
    }
}
