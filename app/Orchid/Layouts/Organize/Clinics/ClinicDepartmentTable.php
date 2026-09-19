<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Clinics;

use App\Models\ClinicDepartment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ClinicDepartmentTable extends Table
{
    protected $target = 'departments';

    protected function textNotFound(): string
    {
        return __('clinics.no_departments');
    }

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('clinics.department_name'))
                ->cantHide()
                ->render(fn (ClinicDepartment $d): string => e((string) $d->name)),

            TD::make('description', __('clinics.department_description'))
                ->render(fn (ClinicDepartment $d): string => e((string) ($d->description ?? '—'))),

            TD::make('is_active', __('clinics.department_active'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),

            TD::make(__('clinics.actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('140px')
                ->render(fn (ClinicDepartment $d) => [
                    ModalToggle::make(__('clinics.department_edit'))
                        ->modal('departmentModal')
                        ->modalTitle(__('clinics.department_edit'))
                        ->method('saveDepartment')
                        ->asyncParameters(['department' => $d->id])
                        ->icon('bs.pencil'),
                    Button::make(__('clinics.delete'))
                        ->icon('bs.trash3')
                        ->confirm(__('clinics.department_removed'))
                        ->method('removeDepartment', ['id' => $d->id]),
                ]),
        ];
    }
}
