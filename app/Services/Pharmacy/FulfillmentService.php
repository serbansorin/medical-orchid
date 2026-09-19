<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Enums\PaymentStatus;
use App\Models\PharmacyInventory;
use App\Models\PharmacySale;
use App\Models\PharmacyStaff;
use App\Models\PrescriptionFulfillment;
use App\Models\PrescriptionRouting;
use Illuminate\Support\Facades\DB;

/**
 * Dispense flow for the fulfillment queue (FR-11).
 *
 * Legacy sources (all in ../legacy-medical):
 * - Status literals: app/Http/Controllers/Api/Pharmacist/PrescriptionFulfillmentController.php
 *   (pending, accepted, dispensed, cancelled, expired) and
 *   app/Http/Controllers/Api/Doctor/PrescriptionToPharmacyController.php ('pending' on create).
 * - Dispense write itself: legacy dispense() only flips the fulfillment row to
 *   'dispensed' (+ pharmacist_id, dispensed_date). It does NOT create a sale —
 *   sales are created by POSController::store() and finalized (+receipt) by
 *   POSController::processSale(). This service merges both halves into one
 *   transaction so the queue action is single-click: fulfillment → sale+items →
 *   stock decrement → receipt, mirroring POS totals (19% TVA) and receipt shape.
 * - prescription_routing literals: NO legacy PHP literals exist, but the live DB
 *   enforces prescription_routing_status_check ∈ {pending, forwarded,
 *   processing, ready, picked_up, cancelled} (dumped from pg_constraint).
 *   Dispense sets routing → 'picked_up' (terminal); queue = not in
 *   {picked_up, cancelled}.
 *
 * Model/DB column mismatches (models are verbatim copies, MUST NOT be edited):
 * real columns are pharmacy_sales.customer_id, pharmacy_sale_items.pharmacy_sale_id,
 * pharmacy_receipts.pharmacy_sale_id — the copied models use patient_id/sale_id.
 * All writes below use forceFill() so the real columns persist regardless.
 */
class FulfillmentService
{
    /**
     * Terminal routing statuses. Source: live DB check constraint
     * prescription_routing_status_check (pending, forwarded, processing,
     * ready, picked_up, cancelled) — terminal = picked_up + cancelled.
     *
     * @var string[]
     */
    public const TERMINAL_ROUTING_STATUSES = ['picked_up', 'cancelled'];

    /**
     * @param  array{quantity_dispensed?: int, expiry_date?: string|null, notes?: string|null}  $data
     * @return array{fulfillment: PrescriptionFulfillment, sale: PharmacySale}
     */
    public function dispense(PrescriptionRouting $routing, array $data): array
    {
        return DB::transaction(function () use ($routing, $data) {
            if (in_array($routing->status, self::TERMINAL_ROUTING_STATUSES, true)) {
                throw new \DomainException('Routing already in a terminal state.');
            }

            $prescription = $routing->prescription()->with('prescriptionMedications')->firstOrFail();
            $medications = $prescription->prescriptionMedications;

            $quantityDispensed = (int) ($data['quantity_dispensed'] ?? $medications->sum('quantity'));
            $quantityDispensed = max($quantityDispensed, 1);

            // FK reality (dumped): prescription_fulfillments.pharmacist_id →
            // pharmacy_staff(id) NULLABLE; pharmacy_sales.pharmacist_id →
            // users(id) NOT NULL. Legacy dispense() stored $staff->id (staff row),
            // POS stored auth()->id() (user). Mirror both; staff-less callers
            // (super-admin) get NULL on the fulfillment row.
            $staffId = PharmacyStaff::where('user_id', auth()->id())->value('id');

            $fulfillment = new PrescriptionFulfillment;
            $fulfillment->forceFill([
                'prescription_id' => $routing->prescription_id,
                'pharmacy_id' => $routing->pharmacy_id,
                'status' => 'dispensed',
                'quantity_dispensed' => $quantityDispensed,
                'dispensed_date' => now(),
                'expiry_date' => $data['expiry_date'] ?? null,
                'pharmacist_id' => $staffId,
                'accepted_at' => now(),
                'notes' => $data['notes'] ?? null,
            ])->save();

            // Sale mirrors POSController::store()/processSale(): pending until paid,
            // 19% TVA, receipt generated at dispense (POS generates it on finalize).
            $sale = new PharmacySale;
            $sale->forceFill([
                'pharmacy_id' => $routing->pharmacy_id,
                'customer_id' => $prescription->patient_id,
                'pharmacist_id' => auth()->id(),
                'sale_date' => now(),
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'payment_status' => PaymentStatus::Pending->value,
                'notes' => 'Dispense Rx '.($prescription->prescription_number ?? '#'.$prescription->id),
            ])->save();

            $subtotal = 0.0;

            foreach ($medications as $med) {
                $inventory = $this->matchInventory((int) $routing->pharmacy_id, $med);
                $unitPrice = (float) ($inventory?->price_per_unit ?? 0);
                $qty = max((int) $med->quantity, 1);
                $lineTotal = $unitPrice * $qty;

                DB::table('pharmacy_sale_items')->insert([
                    'pharmacy_sale_id' => $sale->id,
                    'inventory_id' => $inventory?->id,
                    'medication_name' => $med->medication_name ?? $inventory?->medication_name ?? '—',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_percent' => 0,
                    'total_price' => $lineTotal,
                    'prescription_id' => $prescription->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $subtotal += $lineTotal;

                // Legacy POSController::processSale() decrements a 'quantity_in_stock'
                // column that does not exist in the real table; the real column is
                // stock_quantity. Missing inventory is recorded (zero-price line)
                // but never crashes the dispense.
                if ($inventory !== null && $qty > 0) {
                    $inventory->decrement('stock_quantity', $qty);
                }
            }

            $tax = $subtotal * 0.19;
            $sale->forceFill([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
            ])->save();

            DB::table('pharmacy_receipts')->insert([
                'pharmacy_sale_id' => $sale->id,
                'receipt_number' => $this->receiptNumber(),
                'receipt_data' => json_encode(['sale_id' => $sale->id, 'source' => 'fulfillment_dispense']),
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $routing->forceFill([
                'status' => 'picked_up',
                'status_changed_at' => now(),
            ])->save();

            return ['fulfillment' => $fulfillment->fresh(), 'sale' => $sale->fresh()];
        });
    }

    /**
     * Match inventory by medication_id first, then medication_name (+strength
     * when the Rx line carries a dosage). Returns null when nothing matches.
     */
    private function matchInventory(int $pharmacyId, object $med): ?PharmacyInventory
    {
        if (! empty($med->medication_id)) {
            $found = PharmacyInventory::where('pharmacy_id', $pharmacyId)
                ->where('medication_id', $med->medication_id)
                ->orderBy('expiry_date')
                ->first();
            if ($found !== null) {
                return $found;
            }
        }

        if (! empty($med->medication_name)) {
            return PharmacyInventory::where('pharmacy_id', $pharmacyId)
                ->where('medication_name', $med->medication_name)
                ->orderBy('expiry_date')
                ->first();
        }

        return null;
    }

    private function receiptNumber(): string
    {
        $date = now()->format('Ymd');
        $seq = DB::table('pharmacy_receipts')->whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('RCP-%s-%05d', $date, $seq);
    }
}
