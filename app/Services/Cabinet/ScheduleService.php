<?php

declare(strict_types=1);

namespace App\Services\Cabinet;

use App\Models\DoctorSchedule;
use App\Models\DoctorTimeOff;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Doctor weekly schedules + time-off ranges (Step 3 — Cabinet).
 *
 * `doctor_schedules`: doctor_id, clinic_id (nullable = every clinic),
 * available_day (Carbon dayOfWeek: 0 = Sunday … 6 = Saturday — matches the
 * legacy BookingComponent lookup `where('available_day', $date->dayOfWeek)`),
 * from/to (time). `doctor_time_offs`: doctor_id, type, start/end date, reason.
 */
class ScheduleService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DoctorSchedule
    {
        $this->assertNoOverlap($data);

        return DB::transaction(
            fn (): DoctorSchedule => DoctorSchedule::query()->create($this->attributes($data))
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DoctorSchedule $schedule, array $data): DoctorSchedule
    {
        $this->assertNoOverlap($data, (int) $schedule->getKey());

        return DB::transaction(function () use ($schedule, $data): DoctorSchedule {
            $schedule->update($this->attributes($data));

            return $schedule->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addTimeOff(array $data): DoctorTimeOff
    {
        if (empty($data['doctor_id']) || empty($data['start_date']) || empty($data['end_date'])) {
            throw new InvalidArgumentException('Doctor, start date and end date are required for time off.');
        }

        if ($data['start_date'] > $data['end_date']) {
            throw new InvalidArgumentException('Time-off start date must be before the end date.');
        }

        return DB::transaction(fn (): DoctorTimeOff => DoctorTimeOff::query()->create([
            'doctor_id' => $data['doctor_id'],
            'type' => $data['type'] ?? 'vacation',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ]));
    }

    public function deleteTimeOff(DoctorTimeOff $timeOff): void
    {
        DB::transaction(fn (): ?bool => $timeOff->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertNoOverlap(array $data, ?int $ignoreId = null): void
    {
        if (empty($data['doctor_id']) || ! isset($data['available_day'], $data['from'], $data['to'])) {
            return;
        }

        $query = DoctorSchedule::query()
            ->where('doctor_id', $data['doctor_id'])
            ->where('available_day', $data['available_day'])
            ->where('from', '<', $data['to'])
            ->where('to', '>', $data['from']);

        if (! empty($data['clinic_id'])) {
            $query->where('clinic_id', $data['clinic_id']);
        }

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Overlapping schedule range for the same doctor, clinic and weekday.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'doctor_id' => $data['doctor_id'] ?? null,
            'clinic_id' => $data['clinic_id'] ?? null,
            'available_day' => $data['available_day'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ];
    }
}
