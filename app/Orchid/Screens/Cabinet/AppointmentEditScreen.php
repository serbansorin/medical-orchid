<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\Cabinet\AppointmentService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class AppointmentEditScreen extends Screen
{
    public ?Appointment $appointment = null;

    public function __construct(private readonly AppointmentService $service) {}

    public function query(Appointment $appointment): array
    {
        if (! $appointment->exists && ($prefill = (string) request()->query('patient', '')) !== '') {
            $appointment->patient_id = (int) $prefill;
        }

        return ['appointment' => $appointment];
    }

    public function name(): ?string
    {
        return $this->appointment?->exists ? __('appointments.edit_title') : __('appointments.create_title');
    }

    public function description(): ?string
    {
        return __('appointments.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_appointment'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('appointments.save'))->method('save')->type(Color::PRIMARY)->icon('bs.check-circle'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('appointment.patient_id')->title(__('appointments.patient'))
                    ->fromModel(Patient::class, 'last_name')->required()
                    ->empty()->help(__('appointments.patient')),
                Select::make('appointment.doctor_id')->title(__('appointments.doctor'))
                    ->fromModel(Doctor::class, 'last_name')->required()->empty(),
                Select::make('appointment.clinic_id')->title(__('appointments.clinic'))
                    ->fromModel(Clinic::class, 'name')->required()->empty(),
                DateTimer::make('appointment.appointment_date')->title(__('appointments.date'))
                    ->format('Y-m-d')->required()->allowInput(),
                Input::make('appointment.appointment_time')->type('time')->title(__('appointments.time'))->required(),
                Input::make('appointment.duration')->type('number')->title(__('appointments.duration')),
                Select::make('appointment.status')->title(__('appointments.status'))
                    ->options([
                        'scheduled' => __('appointments.status_scheduled'),
                        'confirmed' => __('appointments.status_confirmed'),
                        'completed' => __('appointments.status_completed'),
                        'cancelled' => __('appointments.status_cancelled'),
                        'no-show' => __('appointments.status_no_show'),
                    ]),
                Input::make('appointment.appointment_type')->title(__('appointments.type'))->maxlength(100),
                TextArea::make('appointment.notes')->title(__('appointments.notes'))->rows(4),
            ]),
        ];
    }

    public function save(Request $request, Appointment $appointment): mixed
    {
        $data = $request->validate([
            'appointment.patient_id' => 'required|exists:patients,id',
            'appointment.doctor_id' => 'required|exists:doctors,id',
            'appointment.clinic_id' => 'required|exists:clinics,id',
            'appointment.appointment_date' => 'required|date',
            'appointment.appointment_time' => 'required',
            'appointment.duration' => 'nullable|integer|min:5|max:480',
            'appointment.status' => 'nullable|in:scheduled,confirmed,completed,cancelled,no-show',
            'appointment.appointment_type' => 'nullable|string|max:100',
            'appointment.notes' => 'nullable|string',
        ])['appointment'];

        $saved = $appointment->exists
            ? $this->service->update($appointment, $data)
            : $this->service->create($data);

        Toast::success(__('appointments.saved'));

        return redirect()->route('platform.appointments.detail', $saved);
    }
}
