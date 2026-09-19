<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\Treatment;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class TreatmentListLayout extends Table
{
    protected $target = 'treatments';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('catalogs.name'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('catalogs.search_name')))
                ->render(fn (Treatment $t): string => (string) Link::make(e((string) $t->name))
                    ->route('platform.catalogs.treatments.edit', $t)),

            TD::make('category', __('catalogs.category'))
                ->render(fn (Treatment $t): string => e((string) ($t->category ?? '—'))),

            TD::make('description', __('catalogs.description'))
                ->render(fn (Treatment $t): string => e((string) mb_substr($t->description ?? '—', 0, 80))),
        ];
    }
}
