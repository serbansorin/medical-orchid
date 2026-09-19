<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Dashboard;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\Dashboard\DoctorDashboardService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

/**
 * Doctor's day at a glance (FR-6): Now bar, KPI metrics, agenda + alerts
 * columns, recent patients. Scoped to the logged-in doctor identity only.
 *
 * Out of scope for this step (design doc): MiniCalendar interactions,
 * QuickAdd modal, messages preview — their screens/tables do not exist yet.
 */
class DoctorDashboardScreen extends Screen
{
    public function __construct(private readonly DoctorDashboardService $service) {}

    public function query(): iterable
    {
        $doctorId = Doctor::where('user_id', auth()->id())->first()?->id;
        $summary = $this->service->summary($doctorId);
        $now = $this->service->nowBar($doctorId);
        $alerts = $this->service->alerts($doctorId);

        return [
            'metrics' => [
                'today_total' => $summary['today_total'],
                'completed' => $summary['completed'],
                'remaining' => $summary['remaining'],
                'unconfirmed' => $summary['unconfirmed'],
                'open_visits' => $summary['open_visits'],
                'rx_awaiting_signature' => $summary['rx_awaiting_signature'],
            ],
            'nowCurrent' => $now['current'],
            'nowNext' => $now['next'],
            'agenda' => $this->service->agenda($doctorId),
            'unconfirmedAppointments' => $alerts['unconfirmed_appointments'],
            'unsignedPrescriptions' => $alerts['unsigned_prescriptions'],
            'weekStrip' => $this->service->weekStrip($doctorId),
            'recentPatients' => $this->service->recentPatients($doctorId),
        ];
    }

    public function name(): ?string
    {
        return __('dashboard.doctor.title');
    }

    public function description(): ?string
    {
        return __('dashboard.doctor.description');
    }

    public function permission(): ?iterable
    {
        return ['access_doctor_panel', 'view_any_appointment'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('orchid.dashboard.doctor-now'),

            Layout::metrics([
                __('dashboard.doctor.kpi.today_total') => 'metrics.today_total',
                __('dashboard.doctor.kpi.completed') => 'metrics.completed',
                __('dashboard.doctor.kpi.remaining') => 'metrics.remaining',
                __('dashboard.doctor.kpi.unconfirmed') => 'metrics.unconfirmed',
                __('dashboard.doctor.kpi.open_visits') => 'metrics.open_visits',
                __('dashboard.doctor.kpi.rx_awaiting_signature') => 'metrics.rx_awaiting_signature',
            ]),

            Layout::columns([
                Layout::block([
                    Layout::table('agenda', [
                        TD::make('appointment_time', __('dashboard.doctor.agenda.time'))
                            ->render(fn (Appointment $a): string => e($a->appointment_time?->format('H:i') ?? '—')),
                        TD::make('patient', __('dashboard.doctor.agenda.patient'))
                            ->render(fn (Appointment $a): string => e(trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')) ?: '—')),
                        TD::make('clinic', __('dashboard.doctor.agenda.clinic'))
                            ->render(fn (Appointment $a): string => e($a->clinic?->name ?? '—')),
                        TD::make('appointment_type', __('dashboard.doctor.agenda.type'))
                            ->render(fn (Appointment $a): string => e($a->appointment_type ?? '—')),
                        TD::make('status', __('dashboard.doctor.agenda.status'))
                            ->render(fn (Appointment $a): string => e($a->status ?? '—')),
                    ]),
                ])->title(__('dashboard.doctor.agenda.title')),

                Layout::block([
                    Layout::table('unconfirmedAppointments', [
                        TD::make('appointment_time', __('dashboard.doctor.agenda.time'))
                            ->render(fn (Appointment $a): string => e($a->appointment_time?->format('H:i') ?? '—')),
                        TD::make('patient', __('dashboard.doctor.agenda.patient'))
                            ->render(fn (Appointment $a): string => e(trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')) ?: '—')),
                    ]),
                    Layout::table('unsignedPrescriptions', [
                        TD::make('prescription_number', __('dashboard.pharmacist.queue.prescription'))
                            ->render(fn (Prescription $p): string => e($p->prescription_number ?? '#'.$p->id)),
                        TD::make('patient', __('dashboard.pharmacist.queue.patient'))
                            ->render(fn (Prescription $p): string => e(trim(($p->patient?->first_name ?? '').' '.($p->patient?->last_name ?? '')) ?: '—')),
                    ]),
                    Layout::view('orchid.dashboard.doctor-week'),
                ])->title(__('dashboard.doctor.alerts.title')),
            ]),

            Layout::block([
                Layout::table('recentPatients', [
                    TD::make('name', __('dashboard.doctor.recent_patients.name'))
                        ->render(fn (Patient $p): string => e(trim(($p->first_name ?? '').' '.($p->last_name ?? '')) ?: '—')),
                    TD::make('cnp', __('dashboard.doctor.recent_patients.cnp'))
                        ->render(fn (Patient $p): string => e($p->cnp ?? '—')),
                    TD::make('phone', __('dashboard.doctor.recent_patients.phone'))
                        ->render(fn (Patient $p): string => e($p->phone ?? '—')),
                ]),
            ])->title(__('dashboard.doctor.recent_patients.title')),
        ];
    }
}
