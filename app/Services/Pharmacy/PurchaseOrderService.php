<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Enums\PurchaseOrderStatus;
use App\Models\PharmacyPurchaseOrder;
use Illuminate\Support\Facades\DB;

/**
 * Purchase-order lifecycle (FR-11).
 *
 * Mirrors ../legacy-medical/app/Http/Controllers/Api/Pharmacist/PurchaseOrderController.php:
 * - Only submitted/confirmed/shipped orders can be received.
 * - Per-line quantity_received/batch/expiry persist on the item row.
 * - Inventory match: legacy matched (pharmacy_id, medication_id, batch_number),
 *   but live FKs point PO items.medication_id → medicine_documents(id) while
 *   pharmacy_inventory.medication_id → medications(id) — DIFFERENT id spaces.
 *   So matching is by medication NAME (+batch): the medicine document name is
 *   looked up per line and matched against inventory.medication_name; on miss
 *   a row is created with medication_id NULL (nullable) and the resolved name.
 * - After lines: status → Received enum, actual_delivery_date = now,
 *   received_by = auth id. All inside DB::transaction().
 *
 * Model/DB mismatches (models verbatim, MUST NOT edit): writes use forceFill()
 * and DB::table inserts with the REAL column names.
 */
class PurchaseOrderService
{
    /**
     * Create or update a draft order with its lines. Totals recomputed (19% TVA).
     *
     * Real pharmacy_purchase_order_items columns (dumped): NO medication_name —
     * lines are medication_id-keyed (NOT NULL); display names resolve via
     * medications. Legacy API required medication_name, but the live table wins.
     *
     * @param  array{pharmacy_id: int, supplier_id: int, expected_delivery_date?: string|null, notes?: string|null}  $data
     * @param  array<int, array{medication_id: int, quantity_ordered: int, unit_price: float}>  $lines
     */
    public function saveOrder(array $data, array $lines, ?PharmacyPurchaseOrder $order = null): PharmacyPurchaseOrder
    {
        return DB::transaction(function () use ($data, $lines, $order) {
            $subtotal = 0.0;
            foreach ($lines as $line) {
                $subtotal += ((int) $line['quantity_ordered']) * ((float) $line['unit_price']);
            }
            $tax = $subtotal * 0.19;

            if ($order === null) {
                $order = new PharmacyPurchaseOrder;
                $order->forceFill([
                    'pharmacy_id' => $data['pharmacy_id'],
                    'supplier_id' => $data['supplier_id'],
                    'status' => PurchaseOrderStatus::Draft->value,
                    'order_date' => now()->toDateString(),
                    'created_by' => auth()->id(),
                ]);
            } else {
                if ($order->status !== PurchaseOrderStatus::Draft) {
                    throw new \DomainException('Only draft orders can be edited.');
                }
                $order->forceFill([
                    'supplier_id' => $data['supplier_id'],
                ]);
            }

            $order->forceFill([
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
            ])->save();

            // Replace lines wholesale on edit (draft only, legacy mirrors this
            // via delete-missing + upsert; replace-all is equivalent for drafts).
            DB::table('pharmacy_purchase_order_items')->where('purchase_order_id', $order->id)->delete();

            foreach ($lines as $line) {
                $qty = (int) $line['quantity_ordered'];
                $price = (float) $line['unit_price'];
                DB::table('pharmacy_purchase_order_items')->insert([
                    'purchase_order_id' => $order->id,
                    'medication_id' => $line['medication_id'],
                    'quantity_ordered' => $qty,
                    'quantity_received' => 0,
                    'unit_price' => $price,
                    'total_price' => $qty * $price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $order->fresh();
        });
    }

    public function submit(PharmacyPurchaseOrder $order): PharmacyPurchaseOrder
    {
        if ($order->status !== PurchaseOrderStatus::Draft) {
            throw new \DomainException('Only draft orders can be submitted.');
        }
        $order->forceFill(['status' => PurchaseOrderStatus::Submitted->value])->save();

        return $order->fresh();
    }

    public function cancel(PharmacyPurchaseOrder $order): PharmacyPurchaseOrder
    {
        if ($order->status === PurchaseOrderStatus::Received) {
            throw new \DomainException('Received orders cannot be cancelled.');
        }
        $order->forceFill(['status' => PurchaseOrderStatus::Cancelled->value])->save();

        return $order->fresh();
    }

    /**
     * Receive flow. $received maps item_id → ['quantity_received', 'batch_number'?,'expiry_date'?].
     * Empty array = receive full ordered quantities (UI simplification; the legacy
     * API took per-line quantities and this method still honors them when given).
     */
    public function receive(PharmacyPurchaseOrder $order, array $received = []): PharmacyPurchaseOrder
    {
        return DB::transaction(function () use ($order, $received) {
            $order = $order->fresh();
            if (! in_array($order->status, [PurchaseOrderStatus::Submitted, PurchaseOrderStatus::Confirmed, PurchaseOrderStatus::Shipped], true)) {
                throw new \DomainException('This order cannot be received.');
            }

            $items = DB::table('pharmacy_purchase_order_items')->where('purchase_order_id', $order->id)->get();

            foreach ($items as $item) {
                $override = $received[$item->id] ?? [];
                $qtyReceived = (int) ($override['quantity_received'] ?? $item->quantity_ordered);
                $batch = $override['batch_number'] ?? $item->batch_number;
                $expiry = $override['expiry_date'] ?? $item->expiry_date;

                DB::table('pharmacy_purchase_order_items')->where('id', $item->id)->update([
                    'quantity_received' => $qtyReceived,
                    'batch_number' => $batch,
                    'expiry_date' => $expiry,
                    'updated_at' => now(),
                ]);

                if ($qtyReceived > 0) {
                    $docName = DB::table('medicine_documents')->where('id', $item->medication_id)->value('name');
                    $this->incrementInventory($order, $item, $qtyReceived, $batch, $expiry, $docName);
                }
            }

            $order->forceFill([
                'status' => PurchaseOrderStatus::Received->value,
                'actual_delivery_date' => now()->toDateString(),
                'received_by' => auth()->id(),
            ])->save();

            return $order->fresh();
        });
    }

    private function incrementInventory(PharmacyPurchaseOrder $order, object $item, int $qty, mixed $batch, mixed $expiry, mixed $docName): void
    {
        $q = DB::table('pharmacy_inventory')->where('pharmacy_id', $order->pharmacy_id);
        if ($docName !== null) {
            $q->where('medication_name', $docName);
        } else {
            $q->where('medication_id', $item->medication_id);
        }
        if ($batch !== null) {
            $q->where('batch_number', $batch);
        }
        $existing = $q->first();

        if ($existing !== null) {
            DB::table('pharmacy_inventory')->where('id', $existing->id)->increment('stock_quantity', $qty);
            if ($expiry !== null) {
                DB::table('pharmacy_inventory')->where('id', $existing->id)->update(['expiry_date' => $expiry, 'updated_at' => now()]);
            }

            return;
        }

        DB::table('pharmacy_inventory')->insert([
            'pharmacy_id' => $order->pharmacy_id,
            'medication_id' => null,
            // No medication_name on PO items (live table); resolve from catalog.
            'medication_name' => $docName,
            'stock_quantity' => $qty,
            'reorder_level' => 10,
            'price_per_unit' => $item->unit_price,
            'batch_number' => $batch,
            'expiry_date' => $expiry,
            // Live DB check pharmacy_inventory_form_check ∈ {tablet, capsule,
            // liquid, injection, ointment, other} — legacy wrote 'unspecified',
            // which the constraint rejects; 'other' is the compliant fallback.
            'form' => 'other',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
