<?php

declare(strict_types=1);

namespace App\Services\Visits;

use App\Models\Appointment;
use App\Models\MedicalVisit;
use Illuminate\Support\Facades\DB;

/**
 * Clinical encounter lifecycle (Step 3 — Cabinet).
 *
 * Start-visit semantics mirror the legacy Filament AppointmentsTable actions:
 * appointment statuses live in
 * `legacy: Filament/Doctor/Resources/Appointments/Tables/AppointmentsTable.php`
 * (scheduled/confirmed/completed/cancelled/no-show) and visit statuses in
 * `MedicalVisit::STATUSES` (scheduled/checked_in/in_progress/completed/
 * no_show/cancelled). Legacy has no "start visit" action — this flow is new:
 * creating the visit does NOT complete the appointment; finalizing does.
 */
class VisitService
{
    /**
     * Create (or reuse) the medical visit for an appointment.
     *
     * Idempotent: if a visit already exists for the appointment it is
     * returned instead of duplicating.
     */
    public function startFromAppointment(Appointment $appointment): MedicalVisit
    {
        $existing = MedicalVisit::query()
            ->where('appointment_id', $appointment->getKey())
            ->first();

        if ($existing instanceof MedicalVisit) {
            return $existing;
        }

        return DB::transaction(function () use ($appointment): MedicalVisit {
            $userId = auth()->id() ?? $appointment->doctor?->user_id;
            $visit = MedicalVisit::query()->create([
                'appointment_id' => $appointment->getKey(),
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'clinic_id' => $appointment->clinic_id,
                'visit_date' => now(),
                'visit_type' => $this->resolveVisitType($appointment),
                'chief_complaint' => $appointment->notes,
                'status' => 'in_progress',
                'scheduled_at' => $appointment->appointment_date,
                'started_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if (($appointment->status ?? null) === 'scheduled') {
                $appointment->update(['status' => 'confirmed']);
            }

            return $visit;
        });
    }

    /**
     * Finalize a visit: lock the file and complete the source appointment.
     */
    public function finalize(MedicalVisit $visit): MedicalVisit
    {
        return DB::transaction(function () use ($visit): MedicalVisit {
            $visit->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_by' => auth()->id() ?? $visit->updated_by,
            ]);

            if ($visit->appointment_id !== null) {
                Appointment::query()
                    ->whereKey($visit->appointment_id)
                    ->whereNotIn('status', ['completed', 'cancelled', 'no-show'])
                    ->update(['status' => 'completed']);
            }

            return $visit->refresh();
        });
    }

    private function resolveVisitType(Appointment $appointment): string
    {
        $type = (string) ($appointment->appointment_type ?? '');

        return array_key_exists($type, MedicalVisit::TYPES) ? $type : 'consultation';
    }
}
