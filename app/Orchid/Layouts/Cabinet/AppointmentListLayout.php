<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Cabinet;

use App\Models\Appointment;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class AppointmentListLayout extends Table
{
    protected $target = 'appointments';

    protected function columns(): iterable
    {
        return [
            TD::make('appointment_date', __('appointments.date'))
                ->cantHide()
                ->render(function (Appointment $a) {
                    $date = $a->appointment_date?->format('Y-m-d') ?? '—';
                    $time = is_string($a->appointment_time) ? substr($a->appointment_time, 0, 5) : ($a->appointment_time?->format('H:i') ?? '');

                    return Link::make(e(trim($date.' '.$time)))
                        ->route('platform.appointments.detail', $a->getKey());
                }),

            TD::make('patient', __('appointments.patient'))
                ->cantHide()
                ->render(fn (Appointment $a) => e(trim(($a->patient?->first_name ?? '').' '.($a->patient?->last_name ?? '')) ?: '—')),

            TD::make('doctor', __('appointments.doctor'))
                ->render(fn (Appointment $a) => e(trim(($a->doctor?->first_name ?? '').' '.($a->doctor?->last_name ?? '')) ?: '—')),

            TD::make('clinic', __('appointments.clinic'))
                ->render(fn (Appointment $a) => e($a->clinic?->name ?? '—')),

            TD::make('appointment_type', __('appointments.type'))
                ->render(fn (Appointment $a) => e($a->appointment_type ?? '—')),

            TD::make('status', __('appointments.status'))
                ->render(fn (Appointment $a) => e(__('appointments.status_'.str_replace('-', '_', (string) ($a->status ?? 'scheduled'))))),

            TD::make(__('appointments.actions'))
                ->align(TD::ALIGN_RIGHT)
                ->cantHide()
                ->render(fn (Appointment $a) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('appointments.detail'))->route('platform.appointments.detail', $a->getKey())->icon('bs.eye'),
                        Link::make(__('appointments.edit'))->route('platform.appointments.edit', $a->getKey())->icon('bs.pencil'),
                        Button::make(__('appointments.delete'))->icon('bs.trash')
                            ->confirm(__('appointments.delete_confirm'))
                            ->method('delete', ['id' => $a->getKey()]),
                    ])),
        ];
    }

    protected function textNotFound(): string
    {
        return __('appointments.empty');
    }
}
