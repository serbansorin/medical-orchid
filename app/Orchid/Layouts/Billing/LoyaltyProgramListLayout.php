<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use App\Models\LoyaltyProgram;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class LoyaltyProgramListLayout extends Table
{
    protected $target = 'programs';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('promotions.programs.name'))
                ->cantHide()
                ->render(fn (LoyaltyProgram $p): string => e($p->name)),

            TD::make('earn_rule', __('promotions.programs.earn_rule'))
                ->render(fn (LoyaltyProgram $p): string => e(
                    number_format((float) $p->points_per_currency, 2).' pts/unit'
                )),

            TD::make('enrolled', __('promotions.programs.enrolled'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (LoyaltyProgram $p): string => e((string) $p->loyaltyPoints()->count())),

            TD::make('is_active', __('promotions.programs.active'))
                ->usingComponent(Boolean::class, true: 'Yes', false: 'No'),
        ];
    }

    protected function textNotFound(): string
    {
        return __('promotions.programs.empty');
    }
}
