<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Clinics;

use App\Models\Patient;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ClinicPatientTable extends Table
{
    protected $target = 'clinicPatients';

    protected function textNotFound(): string
    {
        return __('clinics.no_patients');
    }

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('clinics.patient_name'))
                ->cantHide()
                ->render(fn (Patient $p): string => e((string) ($p->full_name ?: '—'))),

            TD::make('email', 'E-mail')
                ->render(fn (Patient $p): string => e((string) ($p->email ?? '—'))),

            TD::make('phone', __('clinics.phone'))
                ->render(fn (Patient $p): string => e((string) ($p->phone ?? '—'))),

            TD::make(__('clinics.actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (Patient $p) => Button::make(__('clinics.detach'))
                    ->icon('bs.x-circle')
                    ->confirm(__('clinics.patient_detached'))
                    ->method('detachPatient', ['id' => $p->id])),
        ];
    }
}
