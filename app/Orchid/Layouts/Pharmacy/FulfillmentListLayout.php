<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Pharmacy;

use App\Models\PrescriptionRouting;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class FulfillmentListLayout extends Table
{
    protected $target = 'queue';

    protected function columns(): iterable
    {
        return [
            TD::make('rx', __('fulfillment.rx'))
                ->render(fn (PrescriptionRouting $r): string => e($r->prescription?->prescription_number ?? '#'.$r->prescription_id)),
            TD::make('patient', __('fulfillment.patient'))
                ->render(fn (PrescriptionRouting $r): string => e($r->prescription?->patient?->full_name ?? '—')),
            TD::make('lines', __('fulfillment.lines'))
                ->render(fn (PrescriptionRouting $r): string => (string) ($r->prescription?->prescriptionMedications?->count() ?? 0)),
            TD::make('created_at', __('fulfillment.routed'))
                ->render(fn (PrescriptionRouting $r): string => e(optional($r->created_at)->format('Y-m-d H:i') ?? '—')),
            TD::make('status', __('fulfillment.status'))
                ->render(fn (PrescriptionRouting $r): string => e($r->status ?? '—')),
            TD::make(__('fulfillment.actions'))
                ->render(fn (PrescriptionRouting $r) => ModalToggle::make(__('fulfillment.dispense'))
                    ->modal('dispenseModal')
                    ->method('dispense', ['id' => $r->id])
                    ->icon('bs.capsule')),
        ];
    }

    protected function textNotFound(): string
    {
        return __('fulfillment.empty');
    }
}
