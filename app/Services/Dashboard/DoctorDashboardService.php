<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Appointment;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Doctor-scoped aggregates for the doctor dashboard (FR-6).
 *
 * Stateless: the doctor identity arrives as an explicit ?int argument.
 * A null/unknown doctor yields zeroed structures — never an exception —
 * so the screen renders gracefully for users without a doctor profile.
 */
class DoctorDashboardService
{
    /**
     * @return array{today_total: array{value: int}, completed: array{value: int}, remaining: array{value: int}, unconfirmed: array{value: int}, open_visits: array{value: int}, rx_awaiting_signature: array{value: int}}
     */
    public function summary(?int $doctorId): array
    {
        if ($doctorId === null) {
            return $this->zeroedSummary();
        }

        $todayTotal = $this->todayQuery($doctorId)->count();
        // Source of truth for appointment statuses: legacy
        // app/Filament/Doctor/Resources/Appointments/Tables/AppointmentsTable.php
        // SelectFilter options (scheduled, confirmed, completed, cancelled, no-show)
        // + Schemas/AppointmentForm.php form options. Live distinct: ['scheduled'].
        $completed = $this->todayQuery($doctorId)->where('status', 'completed')->count();
        $cancelled = $this->todayQuery($doctorId)->whereIn('status', ['cancelled', 'no-show'])->count();

        return [
            'today_total' => ['value' => $todayTotal],
            'completed' => ['value' => $completed],
            'remaining' => ['value' => max(0, $todayTotal - $completed - $cancelled)],
            'unconfirmed' => ['value' => $this->todayQuery($doctorId)->where('status', 'scheduled')->count()],
            // Open visits = not in terminal states. Source of truth: legacy
            // app/Models/MedicalVisit.php::STATUSES
            // (scheduled, checked_in, in_progress, completed, no_show, cancelled).
            'open_visits' => ['value' => MedicalVisit::where('doctor_id', $doctorId)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count()],
            // Prescriptions awaiting signature. Source of truth: legacy
            // app/Models/Prescription.php::STATUSES
            // (draft, active, fulfilled, cancelled, expired) + is_signed flag.
            'rx_awaiting_signature' => ['value' => Prescription::where('doctor_id', $doctorId)
                ->where('is_signed', false)
                ->whereNotIn('status', ['cancelled', 'expired', 'fulfilled'])
                ->count()],
        ];
    }

    /**
     * Current + next appointment today for the Now bar (Layout::view).
     *
     * @return array{current: ?Appointment, next: ?Appointment}
     */
    public function nowBar(?int $doctorId): array
    {
        if ($doctorId === null) {
            return ['current' => null, 'next' => null];
        }

        $upcoming = $this->todayQuery($doctorId)
            ->with(['patient', 'clinic'])
            ->whereNotIn('status', ['cancelled', 'no-show', 'completed'])
            ->orderBy('appointment_time')
            ->limit(2)
            ->get();

        return [
            'current' => $upcoming->get(0),
            'next' => $upcoming->get(1),
        ];
    }

    /**
     * Today's agenda ordered by time (max 10 rows).
     */
    public function agenda(?int $doctorId, ?string $date = null, int $limit = 10): Collection
    {
        if ($doctorId === null) {
            return collect();
        }

        $day = $date !== null ? Carbon::parse($date)->toDateString() : today()->toDateString();

        return Appointment::with(['patient', 'clinic'])
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $day)
            ->orderBy('appointment_time')
            ->limit($limit)
            ->get();
    }

    /**
     * 7-day strip: date => appointment count (simplified MiniCalendar).
     *
     * @return array<int, array{date: string, label: string, count: int}>
     */
    public function weekStrip(?int $doctorId): array
    {
        if ($doctorId === null) {
            return [];
        }

        $start = today();
        $counts = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<', $start->copy()->addDays(7)->toDateString())
            ->selectRaw('appointment_date::date as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $strip = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $strip[] = [
                'date' => $key,
                'label' => $day->format('D d M'),
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $strip;
    }

    /**
     * Alerts: unconfirmed (scheduled) appointments + unsigned prescriptions.
     *
     * @return array{unconfirmed_appointments: Collection, unsigned_prescriptions: Collection}
     */
    public function alerts(?int $doctorId): array
    {
        if ($doctorId === null) {
            return ['unconfirmed_appointments' => collect(), 'unsigned_prescriptions' => collect()];
        }

        return [
            'unconfirmed_appointments' => $this->todayQuery($doctorId)
                ->with(['patient'])
                ->where('status', 'scheduled')
                ->orderBy('appointment_time')
                ->limit(10)
                ->get(),
            'unsigned_prescriptions' => Prescription::with(['patient'])
                ->where('doctor_id', $doctorId)
                ->where('is_signed', false)
                ->whereNotIn('status', ['cancelled', 'expired', 'fulfilled'])
                ->orderByDesc('issue_date')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Recently seen patients via latest visits/appointments (max 8).
     */
    public function recentPatients(?int $doctorId, int $limit = 8): Collection
    {
        if ($doctorId === null) {
            return collect();
        }

        $patientIds = MedicalVisit::where('doctor_id', $doctorId)
            ->orderByDesc('visit_date')
            ->limit($limit * 3)
            ->pluck('patient_id')
            ->merge(
                Appointment::where('doctor_id', $doctorId)
                    ->orderByDesc('appointment_date')
                    ->limit($limit * 3)
                    ->pluck('patient_id')
            )
            ->unique()
            ->take($limit)
            ->values();

        if ($patientIds->isEmpty()) {
            return collect();
        }

        return Patient::whereIn('id', $patientIds)->limit($limit)->get()
            ->sortBy(fn (Patient $p): int => array_search($p->id, $patientIds->all(), true) ?: PHP_INT_MAX)
            ->values();
    }

    private function todayQuery(int $doctorId): Builder
    {
        return Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', today());
    }

    /**
     * @return array{today_total: array{value: int}, completed: array{value: int}, remaining: array{value: int}, unconfirmed: array{value: int}, open_visits: array{value: int}, rx_awaiting_signature: array{value: int}}
     */
    private function zeroedSummary(): array
    {
        return [
            'today_total' => ['value' => 0],
            'completed' => ['value' => 0],
            'remaining' => ['value' => 0],
            'unconfirmed' => ['value' => 0],
            'open_visits' => ['value' => 0],
            'rx_awaiting_signature' => ['value' => 0],
        ];
    }
}
