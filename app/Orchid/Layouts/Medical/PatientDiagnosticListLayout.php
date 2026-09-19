<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Enums\PatientDiagnosticStatus;
use App\Models\PatientDiagnostic;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PatientDiagnosticListLayout extends Table
{
    protected $target = 'diagnostics';

    protected function columns(): iterable
    {
        $statusOptions = [];
        foreach (PatientDiagnosticStatus::cases() as $case) {
            $statusOptions[$case->value] = $case->label();
        }

        return [
            TD::make('title', __('diagnostics.title'))
                ->cantHide()
                ->filter(Input::make()->placeholder(__('diagnostics.search_title')))
                ->render(fn (PatientDiagnostic $d): string => (string) Link::make(e((string) $d->title))
                    ->route('platform.diagnostics.edit', $d)),

            TD::make('patient', __('diagnostics.patient'))
                ->filter(Input::make()->placeholder(__('diagnostics.search_patient')))
                ->render(fn (PatientDiagnostic $d): string => e(trim((string) ($d->patient?->first_name.' '.$d->patient?->last_name)) ?: '—')),

            TD::make('doctor', __('diagnostics.doctor'))
                ->render(fn (PatientDiagnostic $d): string => e(trim((string) ($d->doctor?->first_name.' '.$d->doctor?->last_name)) ?: '—')),

            TD::make('status', __('diagnostics.status'))
                ->filter(Select::make()->options($statusOptions)->empty())
                ->render(fn (PatientDiagnostic $d): string => e($d->status instanceof PatientDiagnosticStatus ? $d->status->label() : (string) $d->status)),

            TD::make('start_date', __('diagnostics.start_date'))
                ->sort()
                ->render(fn (PatientDiagnostic $d): string => e($d->start_date?->format('Y-m-d') ?? '—')),

            TD::make('conditions_count', __('diagnostics.conditions_count'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (PatientDiagnostic $d): int => (int) ($d->conditions_count ?? 0)),
        ];
    }
}
