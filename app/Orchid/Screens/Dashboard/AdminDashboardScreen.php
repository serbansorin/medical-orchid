<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Dashboard;

use App\Models\Appointment;
use App\Models\BillingInvoice;
use App\Services\Dashboard\AdminDashboardService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

/**
 * Org-wide daily ops at a glance (FR-5). Unscoped; gated to admins.
 */
class AdminDashboardScreen extends Screen
{
    public int $openInvoicesCount = 0;

    public function __construct(private readonly AdminDashboardService $service) {}

    public function query(): iterable
    {
        $summary = $this->service->summary();

        return [
            'metrics' => [
                'patients' => $summary['total_patients'],
                'todays_appointments' => $summary['todays_appointments'],
                'open_invoices_total' => $summary['open_invoices_total'],
                'low_stock' => $summary['low_stock_count'],
                'pending_fulfillments' => $summary['pending_fulfillments'],
                'active_locations' => $summary['active_locations'],
            ],
            'alerts' => $this->service->alerts(),
            'openInvoicesCount' => $summary['open_invoices_count']['value'],
            'todaysAppointments' => $this->service->todaysAppointments(),
            'openInvoices' => $this->service->openInvoices(),
        ];
    }

    public function name(): ?string
    {
        return __('dashboard.admin.title');
    }

    public function description(): ?string
    {
        return __('dashboard.admin.description');
    }

    public function permission(): ?iterable
    {
        return ['access_admin_panel'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                __('dashboard.admin.kpi.patients') => 'metrics.patients',
                __('dashboard.admin.kpi.todays_appointments') => 'metrics.todays_appointments',
                __('dashboard.admin.kpi.open_invoices_total') => 'metrics.open_invoices_total',
                __('dashboard.admin.kpi.low_stock') => 'metrics.low_stock',
                __('dashboard.admin.kpi.pending_fulfillments') => 'metrics.pending_fulfillments',
                __('dashboard.admin.kpi.active_locations') => 'metrics.active_locations',
            ]),

            Layout::view('orchid.dashboard.admin-alerts'),

            Layout::block([
                Layout::table('todaysAppointments', [
                    TD::make('appointment_time', __('dashboard.admin.table.time'))
                        ->render(fn (Appointment $a): string => e($a->appointment_time?->format('H:i') ?? '—')),
                    TD::make('patient', __('dashboard.admin.table.patient'))
                        ->render(fn (Appointment $a): string => e(trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')) ?: '—')),
                    TD::make('doctor', __('dashboard.admin.table.doctor'))
                        ->render(fn (Appointment $a): string => e(trim(($a->doctor?->first_name ?? '').' '.($a->doctor?->last_name ?? '')) ?: '—')),
                    TD::make('clinic', __('dashboard.admin.table.clinic'))
                        ->render(fn (Appointment $a): string => e($a->clinic?->name ?? '—')),
                    TD::make('appointment_type', __('dashboard.admin.table.type'))
                        ->render(fn (Appointment $a): string => e($a->appointment_type ?? '—')),
                    TD::make('status', __('dashboard.admin.table.status'))
                        ->render(fn (Appointment $a): string => e($a->status ?? '—')),
                ]),
            ])->title(__('dashboard.admin.appointments_today')),

            Layout::block([
                Layout::table('openInvoices', [
                    TD::make('invoice_number', __('dashboard.admin.table.invoice_number'))
                        ->render(fn (BillingInvoice $i): string => e($i->invoice_number ?? '#'.$i->id)),
                    TD::make('patient', __('dashboard.admin.table.patient'))
                        ->render(fn (BillingInvoice $i): string => e(trim(($i->patient?->first_name ?? '').' '.($i->patient?->last_name ?? '')) ?: '—')),
                    TD::make('invoice_date', __('dashboard.admin.table.invoice_date'))
                        ->render(fn (BillingInvoice $i): string => e($i->invoice_date?->format('Y-m-d') ?? '—')),
                    TD::make('status', __('dashboard.admin.table.status'))
                        ->render(fn (BillingInvoice $i): string => e($i->status ?? '—')),
                    TD::make('total_amount', __('dashboard.admin.table.total'))
                        ->align(TD::ALIGN_RIGHT)
                        ->render(fn (BillingInvoice $i): string => e(number_format((float) $i->total_amount, 2))),
                ]),
            ])->title(__('dashboard.admin.open_invoices', ['count' => $this->openInvoicesCount])),
        ];
    }
}
