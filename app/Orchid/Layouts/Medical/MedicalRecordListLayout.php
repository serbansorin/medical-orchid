<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\MedicalRecord;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class MedicalRecordListLayout extends Table
{
    protected $target = 'records';

    protected function columns(): iterable
    {
        return [
            TD::make('created_date', __('records.created_date'))
                ->sort()
                ->render(fn (MedicalRecord $r): string => (string) Link::make(e($r->created_date?->format('Y-m-d') ?? '—'))
                    ->route('platform.records.edit', $r)),

            TD::make('patient', __('records.patient'))
                ->filter(Input::make()->placeholder(__('records.search_patient')))
                ->render(fn (MedicalRecord $r): string => e(trim((string) ($r->patient?->first_name.' '.$r->patient?->last_name)) ?: '—')),

            TD::make('title', __('records.title'))
                ->cantHide()
                ->filter(Input::make()->placeholder(__('records.search_title')))
                ->render(fn (MedicalRecord $r): string => e((string) $r->title)),

            TD::make('record_type', __('records.record_type'))
                ->filter(Select::make()->options([
                    'record' => __('records.type_record'),
                    'note' => __('records.type_note'),
                    'certificate' => __('records.type_certificate'),
                    'consent' => __('records.type_consent'),
                ])->empty())
                ->render(fn (MedicalRecord $r): string => e((string) ($r->record_type ?? '—'))),

            TD::make('doctor', __('records.doctor'))
                ->render(fn (MedicalRecord $r): string => e(trim((string) ($r->doctor?->first_name.' '.$r->doctor?->last_name)) ?: '—')),
        ];
    }
}
