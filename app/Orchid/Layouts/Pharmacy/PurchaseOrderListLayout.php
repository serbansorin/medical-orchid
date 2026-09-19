<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Pharmacy;

use App\Models\PharmacyPurchaseOrder;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PurchaseOrderListLayout extends Table
{
    protected $target = 'orders';

    protected function columns(): iterable
    {
        // NOTE: no ->sort()/->filter() — verbatim model has no Filterable trait
        // (MUST NOT edit); screens filter with plain where() clauses.
        return [
            TD::make('order_number', __('purchase-orders.number'))
                ->render(fn (PharmacyPurchaseOrder $o): string => e($o->order_number ?? '#'.$o->id)),
            TD::make('supplier', __('purchase-orders.supplier'))
                ->render(fn (PharmacyPurchaseOrder $o): string => e($o->supplier?->name ?? '—')),
            TD::make('order_date', __('purchase-orders.order_date'))
                ->render(fn (PharmacyPurchaseOrder $o): string => e(optional($o->order_date)->format('Y-m-d') ?? '—')),
            TD::make('expected_delivery_date', __('purchase-orders.expected'))
                ->render(fn (PharmacyPurchaseOrder $o): string => e(optional($o->expected_delivery_date)->format('Y-m-d') ?? '—')),
            TD::make('status', __('purchase-orders.status'))
                ->render(fn (PharmacyPurchaseOrder $o): string => e(is_object($o->status) ? $o->status->value : (string) $o->status)),
            TD::make('total_amount', __('purchase-orders.total'))
                ->render(fn (PharmacyPurchaseOrder $o): string => number_format((float) $o->total_amount, 2)),
            TD::make(__('purchase-orders.actions'))
                ->render(fn (PharmacyPurchaseOrder $o) => Link::make(__('purchase-orders.edit'))
                    ->route('platform.purchase-orders.edit', $o)
                    ->icon('bs.pencil')),
        ];
    }

    protected function textNotFound(): string
    {
        return __('purchase-orders.empty');
    }
}
