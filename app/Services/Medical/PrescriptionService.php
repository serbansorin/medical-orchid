<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Models\Prescription;
use App\Models\PrescriptionFulfillment;
use App\Models\PrescriptionRouting;
use DomainException;
use Illuminate\Support\Facades\DB;

class PrescriptionService
{
    /**
     * Mirror of legacy Prescription::generatePrescriptionNumber():
     * sprintf('RX-%s-%05d', Ymd, daily sequence).
     */
    public function generateNumber(): string
    {
        return Prescription::generatePrescriptionNumber();
    }

    /**
     * One write flow: prescription + its medication rows in a transaction.
     *
     * @param  array<string,mixed>  $data
     * @param  list<array<string,mixed>>  $medicationRows
     */
    public function saveWithMedications(Prescription $prescription, array $data, array $medicationRows): Prescription
    {
        return DB::transaction(function () use ($prescription, $data, $medicationRows): Prescription {
            if (! $prescription->exists && empty($data['prescription_number'])) {
                $data['prescription_number'] = $this->generateNumber();
            }

            $prescription->fill($data);
            $prescription->save();

            $prescription->prescriptionMedications()->delete();

            $order = 1;
            foreach ($medicationRows as $row) {
                $name = trim((string) ($row['medication_name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $prescription->prescriptionMedications()->create([
                    'medication_id' => $row['medication_id'] ?? null,
                    'medication_name' => $name,
                    'dosage' => (string) ($row['dosage'] ?? ''),
                    'frequency' => (string) ($row['frequency'] ?? ''),
                    'route' => (string) ($row['route'] ?? 'oral'),
                    'quantity' => $row['quantity'] ?? null,
                    'quantity_unit' => $row['quantity_unit'] ?? null,
                    'duration_days' => $row['duration_days'] ?? null,
                    'start_date' => $row['start_date'] ?? null,
                    'end_date' => $row['end_date'] ?? null,
                    'instructions' => $row['instructions'] ?? null,
                    'take_with_food' => (bool) ($row['take_with_food'] ?? false),
                    'take_before_meals' => (bool) ($row['take_before_meals'] ?? false),
                    'refills_allowed' => (int) ($row['refills_allowed'] ?? 0),
                    'refills_used' => 0,
                    'is_substitutable' => (bool) ($row['is_substitutable'] ?? true),
                    'order_number' => $order++,
                ]);
            }

            return $prescription->fresh() ?? $prescription;
        });
    }

    /**
     * Mirror of legacy Prescription::sign(): sets is_signed, signed_at=now
     * (plus optional signature path) and flips status to active.
     */
    public function sign(Prescription $prescription, ?string $signaturePath = null): Prescription
    {
        if (in_array($prescription->status, ['cancelled', 'fulfilled', 'expired'], true)) {
            throw new DomainException('Only draft or active prescriptions can be signed.');
        }

        $prescription->sign($signaturePath);

        return $prescription->fresh() ?? $prescription;
    }

    /**
     * Route a signed prescription to a pharmacy.
     *
     * Legacy sends to the pharmacy queue via prescription_fulfillments with
     * status 'pending' (PrescriptionToPharmacyController, dedup on pending).
     * The DB check constraints confirm the literals:
     * prescription_routing.status IN (pending, forwarded, processing, ready,
     * picked_up, cancelled); prescription_fulfillments.status IN (pending,
     * dispensed, cancelled, expired). Both rows use 'pending'.
     */
    public function route(Prescription $prescription, int $pharmacyId, ?string $notes = null): PrescriptionRouting
    {
        return DB::transaction(function () use ($prescription, $pharmacyId, $notes): PrescriptionRouting {
            $existing = PrescriptionFulfillment::query()
                ->where('prescription_id', $prescription->id)
                ->where('pharmacy_id', $pharmacyId)
                ->whereIn('status', ['pending'])
                ->first();

            if (! $existing) {
                PrescriptionFulfillment::create([
                    'prescription_id' => $prescription->id,
                    'pharmacy_id' => $pharmacyId,
                    'status' => 'pending',
                    'notes' => $notes,
                ]);
            }

            return PrescriptionRouting::create([
                'prescription_id' => $prescription->id,
                'pharmacy_id' => $pharmacyId,
                'status' => 'pending',
                'notes' => $notes,
                'status_changed_at' => now(),
            ]);
        });
    }
}
