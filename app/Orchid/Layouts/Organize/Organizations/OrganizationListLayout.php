<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Organizations;

use App\Models\Organization;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class OrganizationListLayout extends Table
{
    protected $target = 'organizations';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('organizations.name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()->placeholder(__('organizations.search_name')))
                ->render(fn (Organization $o): string => (string) Link::make(e((string) $o->name))
                    ->route('platform.organizations.edit', $o)),

            TD::make('type', __('organizations.type'))
                ->filter(Select::make()->options([
                    'clinic' => __('organizations.type_clinic'),
                    'pharmacy' => __('organizations.type_pharmacy'),
                    'hospital' => __('organizations.type_hospital'),
                    'laboratory' => __('organizations.type_laboratory'),
                    'other' => __('organizations.type_other'),
                ])->empty())
                ->render(fn (Organization $o): string => e((string) ($o->type ?? '—'))),

            TD::make('clinics_count', __('organizations.clinics'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Organization $o): int => (int) ($o->clinics_count ?? 0)),

            TD::make('pharmacies_count', __('organizations.pharmacies'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Organization $o): int => (int) ($o->pharmacies_count ?? 0)),

            TD::make('is_active', __('organizations.is_active'))
                ->align(TD::ALIGN_CENTER)
                ->filter(Select::make()->options(['1' => __('Yes'), '0' => __('No')])->empty())
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }
}
