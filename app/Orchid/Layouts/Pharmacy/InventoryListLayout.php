<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Pharmacy;

use App\Models\PharmacyInventory;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class InventoryListLayout extends Table
{
    protected $target = 'inventory';

    protected function columns(): iterable
    {
        // NOTE: no ->sort()/->filter() — the verbatim PharmacyInventory model
        // has no Filterable trait (models MUST NOT be edited), so automatic
        // HTTP sort/filter would silently do nothing. The screen applies
        // search + low-stock + expiring scopes with plain where() clauses.
        return [
            TD::make('medication_name', __('inventory.medication'))
                ->render(fn (PharmacyInventory $i): string => e($i->medication_name ?? $i->medication?->name ?? '—')),
            TD::make('strength', __('inventory.strength'))
                ->render(fn (PharmacyInventory $i): string => e($i->strength ?? '—')),
            TD::make('form', __('inventory.form'))
                ->render(fn (PharmacyInventory $i): string => e($i->form ?? '—')),
            TD::make('stock_quantity', __('inventory.stock'))
                ->render(fn (PharmacyInventory $i): string => (string) $i->stock_quantity),
            TD::make('reorder_level', __('inventory.reorder'))
                ->render(fn (PharmacyInventory $i): string => (string) ($i->reorder_level ?? '—')),
            TD::make('price_per_unit', __('inventory.price'))
                ->render(fn (PharmacyInventory $i): string => number_format((float) $i->price_per_unit, 2)),
            TD::make('batch_number', __('inventory.batch'))
                ->render(fn (PharmacyInventory $i): string => e($i->batch_number ?? '—')),
            TD::make('expiry_date', __('inventory.expiry'))
                ->render(fn (PharmacyInventory $i): string => e(optional($i->expiry_date)->format('Y-m-d') ?? '—')),
            TD::make(__('inventory.actions'))
                ->render(fn (PharmacyInventory $i) => Link::make(__('inventory.adjust'))
                    ->route('platform.inventory.edit', $i)
                    ->icon('bs.pencil')),
        ];
    }

    protected function textNotFound(): string
    {
        return __('inventory.empty');
    }
}
