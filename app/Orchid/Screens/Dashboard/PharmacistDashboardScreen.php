<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Dashboard;

use App\Models\PharmacyInventory;
use App\Models\PharmacySale;
use App\Models\PharmacyStaff;
use App\Models\PrescriptionRouting;
use App\Services\Dashboard\PharmacistDashboardService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

/**
 * Pharmacy stock risks + fulfillment work + today's takings (FR-7).
 * Scoped to the logged-in staff member's pharmacy only.
 */
class PharmacistDashboardScreen extends Screen
{
    public function __construct(private readonly PharmacistDashboardService $service) {}

    public function query(): iterable
    {
        $pharmacyId = PharmacyStaff::where('user_id', auth()->id())->first()?->pharmacy_id;
        $summary = $this->service->summary($pharmacyId);

        return [
            'metrics' => [
                'low_stock' => $summary['low_stock_count'],
                'pending_fulfillments' => $summary['pending_fulfillments'],
                'todays_sales' => $summary['todays_sales_total'],
                'open_pos' => $summary['open_pos'],
            ],
            'fulfillmentQueue' => $this->service->fulfillmentQueue($pharmacyId),
            'lowStock' => $this->service->lowStock($pharmacyId),
            'todaysSales' => $this->service->todaysSales($pharmacyId),
        ];
    }

    public function name(): ?string
    {
        return __('dashboard.pharmacist.title');
    }

    public function description(): ?string
    {
        return __('dashboard.pharmacist.description');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                __('dashboard.pharmacist.kpi.low_stock') => 'metrics.low_stock',
                __('dashboard.pharmacist.kpi.pending_fulfillments') => 'metrics.pending_fulfillments',
                __('dashboard.pharmacist.kpi.todays_sales') => 'metrics.todays_sales',
                __('dashboard.pharmacist.kpi.open_pos') => 'metrics.open_pos',
            ]),

            Layout::block([
                Layout::table('fulfillmentQueue', [
                    TD::make('prescription', __('dashboard.pharmacist.queue.prescription'))
                        ->render(fn (PrescriptionRouting $r): string => e($r->prescription?->prescription_number ?? '#'.$r->prescription_id)),
                    TD::make('patient', __('dashboard.pharmacist.queue.patient'))
                        ->render(fn (PrescriptionRouting $r): string => e(trim(($r->prescription?->patient?->first_name ?? '').' '.($r->prescription?->patient?->last_name ?? '')) ?: '—')),
                    TD::make('status', __('dashboard.pharmacist.queue.status'))
                        ->render(fn (PrescriptionRouting $r): string => e($r->status ?? '—')),
                    TD::make('status_changed_at', __('dashboard.pharmacist.queue.updated'))
                        ->render(fn (PrescriptionRouting $r): string => e($r->status_changed_at?->format('Y-m-d H:i') ?? '—')),
                ]),
            ])->title(__('dashboard.pharmacist.queue.title')),

            Layout::block([
                Layout::table('lowStock', [
                    TD::make('medication_name', __('dashboard.pharmacist.low_stock.sku'))
                        ->render(fn (PharmacyInventory $s): string => e(trim($s->medication_name.' '.($s->strength ?? '').' '.($s->form ?? '')) ?: '—')),
                    TD::make('stock_quantity', __('dashboard.pharmacist.low_stock.on_hand'))
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (PharmacyInventory $s): string => e((string) $s->stock_quantity)),
                    TD::make('reorder_level', __('dashboard.pharmacist.low_stock.reorder_point'))
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (PharmacyInventory $s): string => e((string) $s->reorder_level)),
                    TD::make('price_per_unit', __('dashboard.pharmacist.low_stock.price'))
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (PharmacyInventory $s): string => e(number_format((float) $s->price_per_unit, 2))),
                ]),
            ])->title(__('dashboard.pharmacist.low_stock.title')),

            Layout::block([
                Layout::table('todaysSales', [
                    TD::make('sale_number', __('dashboard.pharmacist.sales.sale_number'))
                        ->render(fn (PharmacySale $s): string => e($s->sale_number ?? '#'.$s->id)),
                    TD::make('total_amount', __('dashboard.pharmacist.sales.total'))
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (PharmacySale $s): string => e(number_format((float) $s->total_amount, 2))),
                    TD::make('payment_status', __('dashboard.pharmacist.sales.payment'))
                        ->render(fn (PharmacySale $s): string => e(is_string($s->payment_status) ? $s->payment_status : ($s->payment_status?->value ?? '—'))),
                ]),
            ])->title(__('dashboard.pharmacist.sales.title')),
        ];
    }
}
