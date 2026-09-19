<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Cabinet;

use App\Models\DoctorSchedule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ScheduleListLayout extends Table
{
    protected $target = 'schedules';

    protected function columns(): iterable
    {
        return [
            TD::make('doctor', __('schedules.doctor'))
                ->cantHide()
                ->render(fn (DoctorSchedule $s) => e(trim(($s->doctor?->first_name ?? '').' '.($s->doctor?->last_name ?? '')) ?: '—')),

            TD::make('clinic', __('schedules.clinic'))
                ->render(fn (DoctorSchedule $s) => e($s->clinic?->name ?? __('schedules.all_clinics_short'))),

            TD::make('available_day', __('schedules.weekday'))
                ->cantHide()
                ->render(fn (DoctorSchedule $s) => e(__('schedules.day_'.((int) ($s->available_day ?? 1))))),

            TD::make('from', __('schedules.from'))
                ->render(fn (DoctorSchedule $s) => e(is_string($s->from) ? substr($s->from, 0, 5) : '—')),

            TD::make('to', __('schedules.to'))
                ->render(fn (DoctorSchedule $s) => e(is_string($s->to) ? substr($s->to, 0, 5) : '—')),

            TD::make(__('schedules.actions'))
                ->align(TD::ALIGN_RIGHT)
                ->cantHide()
                ->render(fn (DoctorSchedule $s) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('schedules.edit'))->route('platform.schedules.edit', $s->getKey())->icon('bs.pencil'),
                        Button::make(__('schedules.delete'))->icon('bs.trash')
                            ->confirm(__('schedules.delete_confirm'))
                            ->method('delete', ['id' => $s->getKey()]),
                    ])),
        ];
    }

    protected function textNotFound(): string
    {
        return __('schedules.time_off_empty');
    }
}
