<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Models\PharmacySupplier;
use App\Orchid\Layouts\Pharmacy\SupplierListLayout;
use App\Services\Pharmacy\PharmacyScope;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PharmacySupplierListScreen extends Screen
{
    public function query(): iterable
    {
        $pharmacyId = PharmacyScope::currentPharmacyId();
        $search = (string) request()->query('search', '');

        $suppliers = PharmacySupplier::query()
            ->when($pharmacyId !== null, fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            ->when($search !== '', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25);

        return ['suppliers' => $suppliers];
    }

    public function name(): ?string
    {
        return __('suppliers.title');
    }

    public function description(): ?string
    {
        return __('suppliers.description');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('suppliers.create'))
                ->icon('bs.plus-circle')
                ->route('platform.suppliers.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('search')
                    ->value(request()->query('search', ''))
                    ->placeholder(__('suppliers.name'))
                    ->title(__('suppliers.name')),
            ]),
            SupplierListLayout::class,
        ];
    }
}
