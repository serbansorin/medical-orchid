<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use App\Enums\DiscountType;
use App\Models\Promotion;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PromotionListLayout extends Table
{
    protected $target = 'promotions';

    protected function columns(): iterable
    {
        return [
            TD::make('title', __('promotions.promotions.name'))
                ->cantHide()
                ->render(fn (Promotion $p) => Link::make($p->title)
                    ->route('platform.promotions.edit', $p->id)),

            TD::make('discount', __('promotions.promotions.discount'))
                ->align(TD::ALIGN_RIGHT)
                ->render(function (Promotion $p): string {
                    try {
                        $label = DiscountType::from((string) $p->discount_type)->label();
                    } catch (\ValueError) {
                        $label = (string) $p->discount_type;
                    }

                    return e($label.' — '.number_format((float) $p->discount_value, 2));
                }),

            TD::make('validity', __('promotions.promotions.validity'))
                ->render(fn (Promotion $p): string => e(
                    ($p->starts_at?->format('Y-m-d') ?? '—').' → '.($p->ends_at?->format('Y-m-d') ?? '—')
                )),

            TD::make('times_used', __('promotions.promotions.used'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (Promotion $p): string => e(
                    (string) $p->times_used.($p->usage_limit !== null ? '/'.$p->usage_limit : '')
                )),

            TD::make('is_active', __('promotions.promotions.active'))
                ->usingComponent(Boolean::class, true: 'Yes', false: 'No'),
        ];
    }

    protected function textNotFound(): string
    {
        return __('promotions.promotions.empty');
    }
}
