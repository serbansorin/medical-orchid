<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use App\Models\BillingInvoice;
use App\Services\Billing\InvoiceService;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class InvoiceListLayout extends Table
{
    protected $target = 'invoices';

    protected function columns(): iterable
    {
        return [
            TD::make('invoice_number', __('invoices.list.number'))
                ->cantHide()
                ->render(fn (BillingInvoice $i) => Link::make($i->invoice_number ?? ('#'.$i->id))
                    ->route('platform.invoices.detail', $i->id)),

            TD::make('patient_id', __('invoices.list.patient'))
                ->cantHide()
                ->render(fn (BillingInvoice $i): string => e($i->patient?->full_name ?? '—')),

            TD::make('invoice_date', __('invoices.list.date'))
                ->render(fn (BillingInvoice $i): string => e($i->invoice_date?->format('Y-m-d') ?? '—')),

            TD::make('due_date', __('invoices.list.due'))
                ->render(fn (BillingInvoice $i): string => e($i->due_date?->format('Y-m-d') ?? '—')),

            TD::make('total_amount', __('invoices.list.total'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (BillingInvoice $i): string => e(number_format((float) $i->total_amount, 2))),

            TD::make('paid', __('invoices.list.paid'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (BillingInvoice $i): string => e(number_format(app(InvoiceService::class)->paidTotal($i), 2))),

            TD::make('status', __('invoices.list.status'))
                ->render(fn (BillingInvoice $i): string => e(__('invoices.status.'.$i->status))),

            TD::make('direction', __('invoices.list.direction'))
                ->render(fn (BillingInvoice $i): string => e(__('invoices.direction.'.$i->direction))),
        ];
    }

    protected function textNotFound(): string
    {
        return __('invoices.list.empty');
    }
}
