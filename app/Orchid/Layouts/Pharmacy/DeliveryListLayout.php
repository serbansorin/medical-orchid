<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Pharmacy;

use App\Models\PharmacyDelivery;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class DeliveryListLayout extends Table
{
    protected $target = 'deliveries';

    protected function columns(): iterable
    {
        return [
            TD::make('scheduled_at', __('deliveries.scheduled'))
                ->render(fn (PharmacyDelivery $d): string => e(optional($d->scheduled_at)->format('Y-m-d H:i') ?? '—')),
            TD::make('sale', __('deliveries.sale'))
                ->render(fn (PharmacyDelivery $d): string => e('#'.($d->getAttribute('pharmacy_sale_id') ?? $d->getAttribute('sale_id') ?? $d->id))),
            TD::make('address', __('deliveries.address'))
                ->render(fn (PharmacyDelivery $d): string => e(mb_substr((string) $d->delivery_address, 0, 60) ?: '—')),
            TD::make('status', __('deliveries.status'))
                ->render(fn (PharmacyDelivery $d): string => e(is_object($d->status) ? $d->status->value : (string) $d->status)),
            TD::make(__('deliveries.actions'))
                ->render(fn (PharmacyDelivery $d) => Link::make(__('deliveries.edit'))
                    ->route('platform.deliveries.edit', $d)
                    ->icon('bs.pencil')),
        ];
    }

    protected function textNotFound(): string
    {
        return __('deliveries.empty');
    }
}
