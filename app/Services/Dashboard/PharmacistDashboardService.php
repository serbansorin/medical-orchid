<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\PharmacyInventory;
use App\Models\PharmacyPurchaseOrder;
use App\Models\PharmacySale;
use App\Models\PrescriptionFulfillment;
use App\Models\PrescriptionRouting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Pharmacy-scoped aggregates for the pharmacist dashboard (FR-7).
 *
 * Stateless: the pharmacy identity arrives as an explicit ?int argument.
 * A null/unknown pharmacy yields zeroed structures — never an exception —
 * so the screen renders gracefully (empty state is correct: pharmacy tables
 * are empty live).
 */
class PharmacistDashboardService
{
    /**
     * @return array{low_stock_count: array{value: int}, pending_fulfillments: array{value: int}, todays_sales_total: array{value: string}, open_pos: array{value: int}}
     */
    public function summary(?int $pharmacyId): array
    {
        if ($pharmacyId === null) {
            return [
                'low_stock_count' => ['value' => 0],
                'pending_fulfillments' => ['value' => 0],
                'todays_sales_total' => ['value' => number_format(0, 2)],
                'open_pos' => ['value' => 0],
            ];
        }

        // payment_status literals: legacy app/Enums/PaymentStatus.php
        // (pending, paid, refunded, partial).
        return [
            'low_stock_count' => ['value' => $this->lowStockQuery($pharmacyId)->count()],
            'pending_fulfillments' => ['value' => $this->pendingFulfillmentsQuery($pharmacyId)->count()],
            'todays_sales_total' => ['value' => number_format((float) PharmacySale::where('pharmacy_id', $pharmacyId)
                ->whereDate('sale_date', today())
                ->where('payment_status', 'paid')
                ->sum('total_amount'), 2)],
            // Open POs = not received/cancelled. Source of truth: legacy
            // app/Enums/PurchaseOrderStatus.php
            // (draft, submitted, confirmed, shipped, received, cancelled).
            'open_pos' => ['value' => PharmacyPurchaseOrder::where('pharmacy_id', $pharmacyId)
                ->whereNotIn('status', ['received', 'cancelled'])
                ->count()],
        ];
    }

    /**
     * Fulfillment queue: routed prescriptions awaiting dispense (max 10 rows).
     *
     * No status literals exist in the legacy codebase for prescription_routing /
     * prescription_fulfillments (no STATUSES const, no Filament options); both
     * tables are empty live. Pending = not in defensive terminal states.
     */
    public function fulfillmentQueue(?int $pharmacyId, int $limit = 10): Collection
    {
        if ($pharmacyId === null) {
            return collect();
        }

        return $this->pendingFulfillmentsQuery($pharmacyId)
            ->with(['prescription.patient', 'prescription.doctor'])
            ->orderByDesc('status_changed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * SKUs at or below reorder point (max 10 rows).
     */
    public function lowStock(?int $pharmacyId, int $limit = 10): Collection
    {
        if ($pharmacyId === null) {
            return collect();
        }

        return $this->lowStockQuery($pharmacyId)
            ->orderBy('stock_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Today's sales (max 10 rows).
     */
    public function todaysSales(?int $pharmacyId, int $limit = 10): Collection
    {
        if ($pharmacyId === null) {
            return collect();
        }

        return PharmacySale::where('pharmacy_id', $pharmacyId)
            ->whereDate('sale_date', today())
            ->orderByDesc('sale_date')
            ->limit($limit)
            ->get();
    }

    private function lowStockQuery(int $pharmacyId): Builder
    {
        return PharmacyInventory::where('pharmacy_id', $pharmacyId)
            ->whereColumn('stock_quantity', '<=', 'reorder_level');
    }

    private function pendingFulfillmentsQuery(int $pharmacyId): Builder
    {
        return PrescriptionRouting::where('pharmacy_id', $pharmacyId)
            ->whereNotIn('status', ['fulfilled', 'completed', 'dispensed', 'collected', 'cancelled']);
    }

    /**
     * Fulfillment rows (same defensive terminal-state exclusion).
     */
    public function pendingFulfillmentRows(?int $pharmacyId, int $limit = 10): Collection
    {
        if ($pharmacyId === null) {
            return collect();
        }

        return PrescriptionFulfillment::where('pharmacy_id', $pharmacyId)
            ->whereNotIn('status', ['fulfilled', 'completed', 'dispensed', 'collected', 'cancelled'])
            ->with(['prescription.patient'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
