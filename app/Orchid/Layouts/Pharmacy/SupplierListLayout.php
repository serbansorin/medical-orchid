<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Pharmacy;

use App\Models\PharmacySupplier;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SupplierListLayout extends Table
{
    protected $target = 'suppliers';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('suppliers.name'))
                ->render(fn (PharmacySupplier $s): string => e($s->name)),
            TD::make('contact', __('suppliers.contact'))
                ->render(fn (PharmacySupplier $s): string => e($s->contact_person ?? $s->email ?? $s->phone ?? '—')),
            TD::make('orders', __('suppliers.orders'))
                ->render(fn (PharmacySupplier $s): string => (string) DB::table('pharmacy_purchase_orders')->where('supplier_id', $s->id)->count()),
            TD::make('is_active', __('suppliers.active'))
                ->render(fn (PharmacySupplier $s): string => $s->is_active ? __('suppliers.active') : '—'),
            TD::make(__('suppliers.actions'))
                ->render(fn (PharmacySupplier $s) => Link::make(__('suppliers.edit'))
                    ->route('platform.suppliers.edit', $s)
                    ->icon('bs.pencil')),
        ];
    }

    protected function textNotFound(): string
    {
        return __('suppliers.empty');
    }
}
