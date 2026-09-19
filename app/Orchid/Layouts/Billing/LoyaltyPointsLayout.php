<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Billing;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class LoyaltyPointsLayout extends Table
{
    protected $target = 'balances';

    protected function columns(): iterable
    {
        return [
            TD::make('patient', __('promotions.points.patient'))
                ->cantHide()
                ->render(fn ($row): string => e(
                    ($row->patient_first ?? '').' '.($row->patient_last ?? '')
                        ? trim(($row->patient_first ?? '').' '.($row->patient_last ?? ''))
                        : __('promotions.points.patient').' #'.$row->patient_id
                )),

            TD::make('program', __('promotions.points.program'))
                ->render(fn ($row): string => e($row->program_name ?? ('#'.$row->loyalty_program_id))),

            TD::make('points_balance', __('promotions.points.balance'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn ($row): string => e(number_format((int) $row->points_balance))),

            TD::make('total_points_earned', __('promotions.points.earned'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn ($row): string => e(number_format((int) $row->total_points_earned))),

            TD::make('total_points_redeemed', __('promotions.points.redeemed'))
                ->align(TD::ALIGN_RIGHT)
                ->render(fn ($row): string => e(number_format((int) $row->total_points_redeemed))),

            TD::make('current_tier', __('promotions.points.tier'))
                ->render(fn ($row): string => e((string) ($row->current_tier ?? '—'))),
        ];
    }

    protected function textNotFound(): string
    {
        return __('promotions.points.empty');
    }
}
