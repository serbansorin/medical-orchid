<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Clinics;

use App\Models\Clinic;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ClinicListLayout extends Table
{
    protected $target = 'clinics';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('clinics.name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()->placeholder(__('clinics.search_name')))
                ->render(fn (Clinic $c): string => (string) Link::make(e((string) $c->name))
                    ->route('platform.clinics.edit', $c)),

            TD::make('organization_id', __('clinics.organization'))
                ->render(fn (Clinic $c): string => e((string) ($c->organization?->name ?? '—'))),

            TD::make('code', __('clinics.code'))
                ->sort()
                ->filter(Input::make()),

            TD::make('type', __('clinics.type'))
                ->filter(Select::make()->options([
                    'main' => __('clinics.type_main'),
                    'branch' => __('clinics.type_branch'),
                ])->empty())
                ->render(fn (Clinic $c): string => e((string) ($c->type ?? '—'))),

            TD::make('doctors_count', __('clinics.doctors'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Clinic $c): int => (int) ($c->doctors_count ?? 0)),

            TD::make('patients_count', __('clinics.patients'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Clinic $c): int => (int) ($c->patients_count ?? 0)),

            TD::make('is_active', __('clinics.is_active'))
                ->align(TD::ALIGN_CENTER)
                ->filter(Select::make()->options(['1' => __('Yes'), '0' => __('No')])->empty())
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }
}
