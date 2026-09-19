<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Enums\DeliveryStatus;
use App\Models\PharmacyDelivery;
use App\Orchid\Layouts\Pharmacy\DeliveryListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PharmacyDeliveryListScreen extends Screen
{
    public function query(): iterable
    {
        $search = (string) request()->query('search', '');
        $status = (string) request()->query('status', '');

        // NOTE: the real pharmacy_deliveries table is home-delivery shaped
        // (pharmacy_sale_id, driver_name/phone, pickup/delivery addresses) —
        // not PO-linked. DeliveryStatus enum drives the status filter.
        $deliveries = PharmacyDelivery::query()
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('delivery_address', 'ilike', "%{$search}%"))
            ->orderByDesc('scheduled_at')
            ->paginate(25);

        return ['deliveries' => $deliveries];
    }

    public function name(): ?string
    {
        return __('deliveries.title');
    }

    public function description(): ?string
    {
        return __('deliveries.description');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('deliveries.create'))
                ->icon('bs.plus-circle')
                ->route('platform.deliveries.create'),
        ];
    }

    public function layout(): iterable
    {
        $statuses = [];
        foreach (DeliveryStatus::cases() as $s) {
            $statuses[$s->value] = $s->value;
        }

        return [
            Layout::rows([
                Input::make('search')
                    ->value(request()->query('search', ''))
                    ->placeholder(__('deliveries.address'))
                    ->title(__('deliveries.address')),
                Select::make('status')
                    ->options(['' => '—'] + $statuses)
                    ->value(request()->query('status', ''))
                    ->title(__('deliveries.status')),
            ]),
            DeliveryListLayout::class,
        ];
    }
}
