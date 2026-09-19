<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\Prescription;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PrescriptionListLayout extends Table
{
    protected $target = 'prescriptions';

    protected function columns(): iterable
    {
        return [
            TD::make('prescription_number', __('prescriptions.number'))
                ->cantHide()
                ->filter(Input::make()->placeholder(__('prescriptions.search_number')))
                ->render(fn (Prescription $p): string => (string) Link::make(e((string) ($p->prescription_number ?? '#'.$p->id)))
                    ->route('platform.prescriptions.edit', $p)),

            TD::make('patient', __('prescriptions.patient'))
                ->filter(Input::make()->placeholder(__('prescriptions.search_patient')))
                ->render(fn (Prescription $p): string => e(trim((string) ($p->patient?->first_name.' '.$p->patient?->last_name)) ?: '—')),

            TD::make('doctor', __('prescriptions.doctor'))
                ->render(fn (Prescription $p): string => e(trim((string) ($p->doctor?->first_name.' '.$p->doctor?->last_name)) ?: '—')),

            TD::make('issue_date', __('prescriptions.issue_date'))
                ->sort()
                ->render(fn (Prescription $p): string => e($p->issue_date?->format('Y-m-d') ?? '—')),

            TD::make('status', __('prescriptions.status'))
                ->sort()
                ->filter(Select::make()->options([
                    'draft' => __('prescriptions.status_draft'),
                    'active' => __('prescriptions.status_active'),
                    'fulfilled' => __('prescriptions.status_fulfilled'),
                    'cancelled' => __('prescriptions.status_cancelled'),
                    'expired' => __('prescriptions.status_expired'),
                ])->empty())
                ->render(fn (Prescription $p): string => e(Prescription::STATUSES[$p->status] ?? $p->status)),

            TD::make('is_signed', __('prescriptions.signed'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }
}
