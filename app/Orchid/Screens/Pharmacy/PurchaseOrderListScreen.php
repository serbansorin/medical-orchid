<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Enums\PurchaseOrderStatus;
use App\Models\PharmacyPurchaseOrder;
use App\Orchid\Layouts\Pharmacy\PurchaseOrderListLayout;
use App\Services\Pharmacy\PharmacyScope;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PurchaseOrderListScreen extends Screen
{
    public function query(): iterable
    {
        $pharmacyId = PharmacyScope::currentPharmacyId();
        $search = (string) request()->query('search', '');
        $status = (string) request()->query('status', '');

        $orders = PharmacyPurchaseOrder::with('supplier')
            ->when($pharmacyId !== null, fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            ->when($search !== '', fn ($q) => $q->where('order_number', 'ilike', "%{$search}%"))
            // PurchaseOrderStatus enum (App\Enums) for POs.
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('order_date')
            ->paginate(25);

        return ['orders' => $orders];
    }

    public function name(): ?string
    {
        return __('purchase-orders.title');
    }

    public function description(): ?string
    {
        return __('purchase-orders.description');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('purchase-orders.create'))
                ->icon('bs.plus-circle')
                ->route('platform.purchase-orders.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('search')
                    ->value(request()->query('search', ''))
                    ->placeholder(__('purchase-orders.number'))
                    ->title(__('purchase-orders.number')),
                Select::make('status')
                    ->options(['' => '—'] + collect(PurchaseOrderStatus::cases())
                        ->mapWithKeys(fn (PurchaseOrderStatus $s): array => [$s->value => $s->value])
                        ->all())
                    ->value(request()->query('status', ''))
                    ->title(__('purchase-orders.status')),
            ]),
            PurchaseOrderListLayout::class,
        ];
    }
}
