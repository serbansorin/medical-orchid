<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Pharmacies;

use App\Models\PharmacyStaff;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PharmacyStaffTable extends Table
{
    protected $target = 'staff';

    protected function textNotFound(): string
    {
        return __('pharmacies.no_staff');
    }

    protected function columns(): iterable
    {
        return [
            TD::make('member', __('pharmacies.staff_member'))
                ->cantHide()
                ->render(fn (PharmacyStaff $s): string => e((string) ($s->full_name ?: $s->user?->name ?: '#'.$s->user_id))),

            TD::make('role', __('pharmacies.staff_role'))
                ->render(fn (PharmacyStaff $s): string => e((string) ($s->role ?? '—'))),

            TD::make('license_number', __('pharmacies.staff_license'))
                ->render(fn (PharmacyStaff $s): string => e((string) ($s->license_number ?? '—'))),

            TD::make('status', __('pharmacies.staff_status'))
                ->render(fn (PharmacyStaff $s): string => e((string) ($s->status ?? '—'))),

            TD::make(__('pharmacies.actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (PharmacyStaff $s) => Button::make(__('pharmacies.detach'))
                    ->icon('bs.x-circle')
                    ->confirm(__('pharmacies.staff_detached'))
                    ->method('detachStaff', ['id' => $s->id])),
        ];
    }
}
