<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\MedicalCode;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class MedicalCodeListLayout extends Table
{
    protected $target = 'codes';

    protected function columns(): iterable
    {
        return [
            TD::make('code', __('catalogs.code'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('catalogs.search_code')))
                ->render(fn (MedicalCode $c): string => (string) Link::make(e((string) $c->code))
                    ->route('platform.catalogs.codes.edit', $c)),

            TD::make('code_system', __('catalogs.code_system'))
                ->filter(Input::make()->placeholder(__('catalogs.code_system')))
                ->render(fn (MedicalCode $c): string => e((string) ($c->code_system ?? '—'))),

            TD::make('code_text', __('catalogs.code_text'))
                ->render(fn (MedicalCode $c): string => e((string) mb_substr($c->code_text ?? '', 0, 80))),

            TD::make('category', __('catalogs.category'))
                ->render(fn (MedicalCode $c): string => e((string) ($c->category ?? '—'))),

            TD::make('active', __('catalogs.active'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }
}
