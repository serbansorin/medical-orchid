<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\Symptom;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SymptomListLayout extends Table
{
    protected $target = 'symptoms';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('catalogs.name'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('catalogs.search_name')))
                ->render(fn (Symptom $s): string => (string) Link::make(e((string) $s->name))
                    ->route('platform.catalogs.symptoms.edit', $s)),

            TD::make('code', __('catalogs.code'))
                ->filter(Input::make()->placeholder(__('catalogs.search_code')))
                ->render(fn (Symptom $s): string => e((string) ($s->code ?? '—'))),

            TD::make('category', __('catalogs.category'))
                ->render(fn (Symptom $s): string => e((string) ($s->category ?? '—'))),

            TD::make('is_common', __('catalogs.is_common'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }
}
