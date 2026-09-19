<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Appointment;
use App\Models\BillingInvoice;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use App\Models\Prescription;
use App\Models\PrescriptionFulfillment;
use App\Models\PrescriptionRouting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Aggregates org-wide KPIs for the admin dashboard (FR-5).
 *
 * Stateless: every method takes explicit scope arguments, no HTTP/Auth coupling.
 */
class AdminDashboardService
{
    /**
     * Six KPI cards for Layout::metrics (FR-5).
     *
     * @return array{total_patients: array{value: int}, todays_appointments: array{value: int}, open_invoices_total: array{value: string}, open_invoices_count: array{value: int}, low_stock_count: array{value: int}, pending_fulfillments: array{value: int}, active_locations: array{value: int}}
     */
    public function summary(): array
    {
        return [
            'total_patients' => ['value' => Patient::count()],
            'todays_appointments' => ['value' => Appointment::whereDate('appointment_date', today())->count()],
            'open_invoices_total' => ['value' => number_format((float) $this->openInvoicesQuery()->sum('total_amount'), 2)],
            'open_invoices_count' => ['value' => $this->openInvoicesQuery()->count()],
            'low_stock_count' => ['value' => $this->lowStockQuery()->count()],
            'pending_fulfillments' => ['value' => $this->pendingFulfillmentsQuery()->count()],
            'active_locations' => ['value' => Clinic::where('is_active', true)->count() + Pharmacy::where('is_verified', true)->count()],
        ];
    }

    /**
     * Today's appointments for the dashboard table (max 10 rows, no pagination).
     */
    public function todaysAppointments(int $limit = 10): Collection
    {
        return Appointment::with(['patient', 'doctor', 'clinic'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Open invoices for the dashboard table (max 10 rows, no pagination).
     */
    public function openInvoices(int $limit = 10): Collection
    {
        return $this->openInvoicesQuery()
            ->with(['patient', 'doctor', 'clinic'])
            ->orderByDesc('invoice_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Alert-strip counts (low stock + pending fulfillment).
     *
     * @return array{low_stock_count: int, pending_fulfillments: int}
     */
    public function alerts(): array
    {
        return [
            'low_stock_count' => $this->lowStockQuery()->count(),
            'pending_fulfillments' => $this->pendingFulfillmentsQuery()->count(),
        ];
    }

    /**
     * Open invoices = everything not in a terminal paid/cancelled-style state.
     *
     * Source of truth: legacy app/Filament/Doctor/Resources/BillingInvoiceResource.php:71
     * (status options: draft, sent, paid, overdue, cancelled) and :353
     * (record actions hidden when status in ['paid', 'cancelled']).
     * Live distinct 2026-09-19: ['draft'].
     */
    private function openInvoicesQuery(): Builder
    {
        return BillingInvoice::whereNotIn('status', ['paid', 'cancelled']);
    }

    /**
     * SKUs at or below their reorder point.
     */
    private function lowStockQuery(): Builder
    {
        return PharmacyInventory::whereColumn('stock_quantity', '<=', 'reorder_level');
    }

    /**
     * Routed/fulfillment rows not yet in a terminal state.
     *
     * Source of truth: no status literals exist in the legacy codebase for
     * prescription_routing / prescription_fulfillments (no STATUSES const, no
     * Filament options); both tables are empty live. Defensive exclusion of
     * terminal states only, so any future non-terminal value counts as pending.
     */
    private function pendingFulfillmentsQuery(): Builder
    {
        $terminal = ['fulfilled', 'completed', 'dispensed', 'collected', 'cancelled'];

        $routingIds = PrescriptionRouting::whereNotIn('status', $terminal)->pluck('prescription_id');
        $fulfillmentIds = PrescriptionFulfillment::whereNotIn('status', $terminal)->pluck('prescription_id');

        return Prescription::whereIn('id', $routingIds->merge($fulfillmentIds)->unique()->values()->all());
    }
}
