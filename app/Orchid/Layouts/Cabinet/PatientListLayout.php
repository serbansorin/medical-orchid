<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Cabinet;

use App\Models\Patient;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PatientListLayout extends Table
{
    protected $target = 'patients';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('patients.name'))
                ->cantHide()
                ->render(fn (Patient $p) => Link::make(e(trim(($p->first_name ?? '').' '.($p->last_name ?? '')) ?: '#'.$p->getKey()))
                    ->route('platform.patients.detail', $p->getKey())),

            TD::make('cnp', __('patients.cnp'))
                ->cantHide()
                ->render(fn (Patient $p) => e($p->cnp ?? '—')),

            TD::make('phone', __('patients.phone'))
                ->render(fn (Patient $p) => e($p->phone ?? '—')),

            TD::make('date_of_birth', __('patients.dob'))
                ->render(fn (Patient $p) => e($p->date_of_birth?->format('Y-m-d') ?? '—')),

            TD::make('city', __('patients.city'))
                ->render(fn (Patient $p) => e($p->city ?? '—')),

            TD::make(__('patients.actions'))
                ->align(TD::ALIGN_RIGHT)
                ->cantHide()
                ->render(fn (Patient $p) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('patients.detail'))->route('platform.patients.detail', $p->getKey())->icon('bs.eye'),
                        Link::make(__('patients.edit'))->route('platform.patients.edit', $p->getKey())->icon('bs.pencil'),
                        Button::make(__('patients.delete'))->icon('bs.trash')
                            ->confirm(__('patients.delete_confirm'))
                            ->method('delete', ['id' => $p->getKey()]),
                    ])),
        ];
    }

    protected function textNotFound(): string
    {
        return __('patients.empty');
    }
}
