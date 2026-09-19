<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Models\PharmacyInventory;
use App\Orchid\Layouts\Pharmacy\InventoryListLayout;
use App\Services\Pharmacy\PharmacyScope;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class InventoryListScreen extends Screen
{
    public function query(): iterable
    {
        $pharmacyId = PharmacyScope::currentPharmacyId();
        $search = (string) request()->query('search', '');
        $mode = (string) request()->query('mode', '');

        $inventory = PharmacyInventory::with('medication')
            ->when($pharmacyId !== null, fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            ->when($search !== '', fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('medication_name', 'ilike', "%{$search}%")
                    ->orWhere('batch_number', 'ilike', "%{$search}%");
            }))
            ->when($mode === 'low', fn ($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_level'))
            ->when($mode === 'expiring', fn ($q) => $q->whereDate('expiry_date', '<=', now()->addDays(90)))
            ->orderBy('medication_name')
            ->paginate(25);

        return [
            'inventory' => $inventory,
            'search' => $search,
            'mode' => $mode,
        ];
    }

    public function name(): ?string
    {
        return __('inventory.title');
    }

    public function description(): ?string
    {
        return __('inventory.description');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('inventory.create'))
                ->icon('bs.plus-circle')
                ->route('platform.inventory.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('search')
                    ->value(request()->query('search', ''))
                    ->placeholder(__('inventory.medication'))
                    ->title(__('inventory.medication')),
                Select::make('mode')
                    ->options(['' => '—', 'low' => __('inventory.low_stock'), 'expiring' => __('inventory.expiring')])
                    ->value(request()->query('mode', ''))
                    ->title(__('inventory.low_stock')),
            ]),
            InventoryListLayout::class,
        ];
    }
}
