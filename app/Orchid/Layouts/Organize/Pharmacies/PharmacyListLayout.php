<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Pharmacies;

use App\Models\Pharmacy;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PharmacyListLayout extends Table
{
    protected $target = 'pharmacies';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('pharmacies.name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()->placeholder(__('pharmacies.search_name')))
                ->render(fn (Pharmacy $p): string => (string) Link::make(e((string) $p->name))
                    ->route('platform.pharmacies.edit', $p)),

            TD::make('organization_id', __('pharmacies.organization'))
                ->render(fn (Pharmacy $p): string => e((string) ($p->organization?->name ?? '—'))),

            TD::make('slug', __('pharmacies.slug'))
                ->render(fn (Pharmacy $p): string => e((string) ($p->slug ?? '—'))),

            TD::make('is_verified', __('pharmacies.is_verified'))
                ->align(TD::ALIGN_CENTER)
                ->filter(Select::make()->options(['1' => __('Yes'), '0' => __('No')])->empty())
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),

            TD::make('staff_count', __('pharmacies.staff_count'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Pharmacy $p): int => (int) ($p->staff_count ?? 0)),
        ];
    }
}
