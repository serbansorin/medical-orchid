<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\Condition;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ConditionListLayout extends Table
{
    protected $target = 'conditions';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('catalogs.name'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('catalogs.search_name')))
                ->render(fn (Condition $c): string => (string) Link::make(e((string) $c->name))
                    ->route('platform.catalogs.conditions.edit', $c)),

            TD::make('icd_code', __('catalogs.icd_code'))
                ->filter(Input::make()->placeholder(__('catalogs.search_code')))
                ->render(fn (Condition $c): string => e((string) ($c->icd_code ?? '—'))),

            TD::make('category', __('catalogs.category'))
                ->render(fn (Condition $c): string => e((string) ($c->category ?? '—'))),

            TD::make('symptoms_count', __('catalogs.symptoms'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Condition $c): int => (int) ($c->symptoms_count ?? 0)),
        ];
    }
}
