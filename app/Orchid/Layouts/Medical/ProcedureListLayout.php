<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\Procedure;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ProcedureListLayout extends Table
{
    protected $target = 'procedures';

    protected function columns(): iterable
    {
        return [
            TD::make('procedure_name', __('catalogs.procedure_name'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('catalogs.search_name')))
                ->render(fn (Procedure $p): string => (string) Link::make(e((string) $p->procedure_name))
                    ->route('platform.catalogs.procedures.edit', $p)),

            TD::make('procedure_code', __('catalogs.procedure_code'))
                ->render(fn (Procedure $p): string => e((string) ($p->procedure_code ?? '—'))),

            TD::make('patient', __('catalogs.patient'))
                ->render(fn (Procedure $p): string => e(trim((string) ($p->patient?->first_name.' '.$p->patient?->last_name)) ?: '—')),

            TD::make('status', __('catalogs.status'))
                ->filter(Select::make()->options([
                    'scheduled' => __('catalogs.status_scheduled'),
                    'in_progress' => __('catalogs.status_in_progress'),
                    'completed' => __('catalogs.status_completed'),
                    'cancelled' => __('catalogs.status_cancelled'),
                ])->empty())
                ->render(fn (Procedure $p): string => e((string) ($p->status ?? '—'))),
        ];
    }
}
