<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Pharmacy;

use App\Models\PharmacySale;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SaleListLayout extends Table
{
    protected $target = 'sales';

    protected function columns(): iterable
    {
        return [
            // NOTE: no ->sort()/->filter() here — the verbatim PharmacySale model
            // has no Filterable trait (models MUST NOT be edited), so automatic
            // HTTP sort/filter would silently do nothing. Screens scope + search
            // with plain where() clauses instead.
            TD::make('sale_number', __('sales.number'))
                ->render(fn (PharmacySale $s): string => e($s->sale_number ?? '#'.$s->id)),
            TD::make('sale_date', __('sales.date'))
                ->render(fn (PharmacySale $s): string => e(optional($s->sale_date)->format('Y-m-d H:i') ?? '—')),
            TD::make('customer', __('sales.customer'))
                ->render(fn (PharmacySale $s): string => e($s->patient?->full_name ?? ('#'.$s->getAttribute('customer_id') ?? '—'))),
            TD::make('lines', __('sales.lines'))
                ->render(fn (PharmacySale $s): string => (string) $s->items()->count()),
            TD::make('total_amount', __('sales.total'))
                ->render(fn (PharmacySale $s): string => number_format((float) $s->total_amount, 2)),
            TD::make('payment_status', __('sales.payment_status'))
                ->render(fn (PharmacySale $s): string => e(is_object($s->payment_status) ? $s->payment_status->value : (string) $s->payment_status)),
            TD::make(__('sales.actions'))
                ->render(fn (PharmacySale $s) => Link::make(__('sales.edit'))
                    ->route('platform.sales.edit', $s)
                    ->icon('bs.pencil')),
        ];
    }

    protected function textNotFound(): string
    {
        return __('sales.empty');
    }
}
