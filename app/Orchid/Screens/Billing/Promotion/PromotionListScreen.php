<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Billing\Promotion;

use App\Models\Promotion;
use App\Orchid\Layouts\Billing\PromotionListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class PromotionListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'promotions' => Promotion::query()->with('pharmacy')->orderByDesc('id')->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('promotions.promotions.title');
    }

    public function description(): ?string
    {
        return __('promotions.promotions.description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_promotion', 'view_any_loyalty_program'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('promotions.promotions.create'))
                ->icon('bs.plus-circle')
                ->route('platform.promotions.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            PromotionListLayout::class,
        ];
    }
}
