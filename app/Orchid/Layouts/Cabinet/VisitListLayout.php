<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Cabinet;

use App\Models\MedicalVisit;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class VisitListLayout extends Table
{
    protected $target = 'visits';

    protected function columns(): iterable
    {
        return [
            TD::make('visit_date', __('visits.date'))
                ->cantHide()
                ->render(fn (MedicalVisit $v) => Link::make(e($v->visit_date?->format('Y-m-d H:i') ?? '#'.$v->getKey()))
                    ->route('platform.visits.detail', $v->getKey())),

            TD::make('patient', __('visits.patient'))
                ->cantHide()
                ->render(fn (MedicalVisit $v) => e(trim(($v->patient?->first_name ?? '').' '.($v->patient?->last_name ?? '')) ?: '—')),

            TD::make('doctor', __('visits.doctor'))
                ->render(fn (MedicalVisit $v) => e(trim(($v->doctor?->first_name ?? '').' '.($v->doctor?->last_name ?? '')) ?: '—')),

            TD::make('clinic', __('visits.clinic'))
                ->render(fn (MedicalVisit $v) => e($v->clinic?->name ?? '—')),

            TD::make('status', __('visits.status'))
                ->render(fn (MedicalVisit $v) => e(__('visits.status_'.((string) ($v->status ?? 'scheduled'))))),
        ];
    }

    protected function textNotFound(): string
    {
        return __('visits.empty');
    }
}
