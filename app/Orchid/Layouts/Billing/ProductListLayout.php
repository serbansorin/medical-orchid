<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use App\Models\Product;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ProductListLayout extends Table
{
    protected $target = 'products';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('products.list.name'))
                ->cantHide()
                ->render(fn (Product $p) => Link::make($p->name)
                    ->route('platform.products.edit', $p->id)),

            TD::make('product_code', __('products.list.code'))
                ->render(fn (Product $p): string => e($p->product_code ?? $p->sku ?? '—')),

            TD::make('level', __('products.list.level'))
                ->render(function (Product $p): string {
                    if ($p->doctor_id) {
                        return e(__('products.level.doctor'));
                    }

                    if ($p->clinic_id) {
                        return e(__('products.level.clinic'));
                    }

                    return e(__('products.level.organization'));
                }),

            TD::make('type', 'Type')
                ->render(fn (Product $p): string => e(__('products.type.'.$p->type))),

            TD::make('price', __('products.list.price'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (Product $p): string => e(number_format((float) $p->price, 2))),

            TD::make('duration_minutes', __('products.list.duration'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (Product $p): string => e($p->duration_minutes !== null ? (string) $p->duration_minutes : '—')),

            TD::make('available', __('products.list.active'))
                ->usingComponent(Boolean::class, true: 'Yes', false: 'No'),
        ];
    }

    protected function textNotFound(): string
    {
        return __('products.list.empty');
    }
}
