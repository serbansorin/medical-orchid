<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Enums\DeliveryStatus;
use App\Models\DeliveryZone;
use App\Models\PharmacyDelivery;
use App\Models\PharmacySale;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PharmacyDeliveryEditScreen extends Screen
{
    public ?PharmacyDelivery $delivery = null;

    public function query(PharmacyDelivery $delivery): iterable
    {
        return ['delivery' => $delivery];
    }

    public function name(): ?string
    {
        return ($this->delivery?->exists ?? false) ? __('deliveries.edit') : __('deliveries.create');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('deliveries.saved'))->method('save')->icon('bs.check-circle'),
            Link::make(__('deliveries.title'))->route('platform.deliveries'),
        ];
    }

    public function layout(): iterable
    {
        $statuses = [];
        foreach (DeliveryStatus::cases() as $s) {
            $statuses[$s->value] = $s->value;
        }

        // Keys use the REAL pharmacy_deliveries columns (pharmacy_sale_id,
        // driver_name/phone, pickup_address, proof_of_delivery_url,
        // customer_signature); the verbatim model only fills sale_id/driver_id/
        // recipient_* → save uses forceFill(). Deviation from
        // designs/supplier-delivery.md (PO-linked deliveries): the real table
        // has no purchase_order_id — deliveries link to sales instead.
        return [
            Layout::rows([
                Select::make('delivery.pharmacy_sale_id')
                    ->fromModel(PharmacySale::class, 'sale_number')
                    ->empty()
                    ->title(__('deliveries.sale')),
                Select::make('delivery.zone_id')
                    ->fromModel(DeliveryZone::class, 'name')
                    ->empty()
                    ->title(__('deliveries.zone')),
                Input::make('delivery.driver_name')->title(__('deliveries.driver')),
                Input::make('delivery.driver_phone')->title(__('deliveries.driver_phone')),
                TextArea::make('delivery.pickup_address')->rows(2)->title(__('deliveries.pickup')),
                TextArea::make('delivery.delivery_address')->rows(2)->title(__('deliveries.address')),
                DateTimer::make('delivery.scheduled_at')->format('Y-m-d H:i')->enableTime()
                    ->title(__('deliveries.scheduled')),
                DateTimer::make('delivery.picked_up_at')->format('Y-m-d H:i')->enableTime()
                    ->title(__('deliveries.picked')),
                DateTimer::make('delivery.delivered_at')->format('Y-m-d H:i')->enableTime()
                    ->title(__('deliveries.delivered')),
                Select::make('delivery.status')
                    ->options($statuses)
                    ->title(__('deliveries.status')),
                Input::make('delivery.proof_of_delivery_url')->title(__('deliveries.proof')),
                TextArea::make('delivery.customer_signature')->rows(2)->title(__('deliveries.signature')),
                TextArea::make('delivery.notes')->rows(2)->title(__('deliveries.notes')),
            ]),
        ];
    }

    public function save(Request $request, PharmacyDelivery $delivery): mixed
    {
        $data = $request->validate([
            'delivery.pharmacy_sale_id' => 'nullable|integer',
            'delivery.zone_id' => 'nullable|integer|exists:delivery_zones,id',
            'delivery.driver_name' => 'nullable|string|max:255',
            'delivery.driver_phone' => 'nullable|string|max:50',
            'delivery.pickup_address' => 'nullable|string',
            'delivery.delivery_address' => 'nullable|string',
            'delivery.scheduled_at' => 'nullable|date',
            'delivery.picked_up_at' => 'nullable|date',
            'delivery.delivered_at' => 'nullable|date',
            'delivery.status' => 'nullable|string|max:50',
            'delivery.proof_of_delivery_url' => 'nullable|string|max:1000',
            'delivery.customer_signature' => 'nullable|string',
            'delivery.notes' => 'nullable|string',
        ])['delivery'];

        $delivery->forceFill($data)->save();

        Toast::success(__('deliveries.saved'));

        return redirect()->route('platform.deliveries.edit', $delivery);
    }
}
