<?php

declare(strict_types=1);

namespace App\Services\Cabinet;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Appointment create/update + status transitions (Step 3 — Cabinet).
 *
 * Status literals mirror the legacy source
 * `Filament/Doctor/Resources/Appointments/Tables/AppointmentsTable.php`
 * (badges) and `Schemas/AppointmentForm.php` (select options):
 * scheduled / confirmed / completed / cancelled / no-show.
 */
class AppointmentService
{
    public const STATUSES = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no-show'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Appointment
    {
        return DB::transaction(
            fn (): Appointment => Appointment::query()->create($this->attributes($data))
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment->update($this->attributes($data));

            return $appointment->refresh();
        });
    }

    public function transition(Appointment $appointment, string $status, ?string $note = null): Appointment
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Unknown appointment status [{$status}].");
        }

        return DB::transaction(function () use ($appointment, $status, $note): Appointment {
            $payload = ['status' => $status];

            if ($note !== null && $note !== '') {
                $stamp = now()->format('d/m/Y H:i');
                $existing = (string) ($appointment->notes ?? '');
                $payload['notes'] = trim($existing.($existing !== '' ? "\n\n" : '')."[{$stamp}] {$note}");
            }

            $appointment->update($payload);

            return $appointment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'patient_id' => $data['patient_id'] ?? null,
            'doctor_id' => $data['doctor_id'] ?? null,
            'clinic_id' => $data['clinic_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'appointment_date' => $data['appointment_date'] ?? null,
            'appointment_time' => $data['appointment_time'] ?? null,
            'duration' => $data['duration'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
            'appointment_type' => $data['appointment_type'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
