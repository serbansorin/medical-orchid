<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Clinics;

use App\Models\ClinicDoctorAssignment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ClinicAssignmentTable extends Table
{
    protected $target = 'assignments';

    protected function textNotFound(): string
    {
        return __('clinics.no_assignments');
    }

    protected function columns(): iterable
    {
        return [
            TD::make('doctor', __('clinics.assignment_doctor'))
                ->cantHide()
                ->render(fn (ClinicDoctorAssignment $a): string => e(trim(
                    (string) ($a->doctor?->first_name.' '.$a->doctor?->last_name)
                ) ?: '#'.$a->doctor_id)),

            TD::make('department', __('clinics.assignment_department'))
                ->render(fn (ClinicDoctorAssignment $a): string => e((string) ($a->clinic_department?->name ?? '—'))),

            TD::make('role', __('clinics.assignment_role'))
                ->render(fn (ClinicDoctorAssignment $a): string => e((string) ($a->role ?? '—'))),

            TD::make('status', __('clinics.assignment_status'))
                ->render(fn (ClinicDoctorAssignment $a): string => e((string) ($a->status ?? '—'))),

            TD::make('start_date', __('clinics.assignment_start'))
                ->render(fn (ClinicDoctorAssignment $a): string => e((string) ($a->start_date ?? '—'))),

            TD::make('end_date', __('clinics.assignment_end'))
                ->render(fn (ClinicDoctorAssignment $a): string => e((string) ($a->end_date ?? '—'))),

            TD::make(__('clinics.actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (ClinicDoctorAssignment $a) => Button::make(__('clinics.detach'))
                    ->icon('bs.x-circle')
                    ->confirm(__('clinics.assignment_detached'))
                    ->method('detachDoctor', ['id' => $a->id])),
        ];
    }
}
