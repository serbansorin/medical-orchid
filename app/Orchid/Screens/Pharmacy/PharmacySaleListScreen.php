<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Enums\PaymentStatus;
use App\Models\PharmacySale;
use App\Orchid\Layouts\Pharmacy\SaleListLayout;
use App\Services\Pharmacy\PharmacyScope;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PharmacySaleListScreen extends Screen
{
    public function query(): iterable
    {
        $pharmacyId = PharmacyScope::currentPharmacyId();
        $search = (string) request()->query('search', '');
        $status = (string) request()->query('payment_status', '');

        $sales = PharmacySale::with('patient')
            ->when($pharmacyId !== null, fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            ->when($search !== '', fn ($q) => $q->where('sale_number', 'ilike', "%{$search}%"))
            // PaymentStatus enum (App\Enums) for sales.payment_status.
            ->when($status !== '', fn ($q) => $q->where('payment_status', $status))
            ->orderByDesc('sale_date')
            ->paginate(25);

        return ['sales' => $sales];
    }

    public function name(): ?string
    {
        return __('sales.title');
    }

    public function description(): ?string
    {
        return __('sales.description');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('sales.create'))
                ->icon('bs.plus-circle')
                ->route('platform.sales.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('search')
                    ->value(request()->query('search', ''))
                    ->placeholder(__('sales.number'))
                    ->title(__('sales.number')),
                Select::make('payment_status')
                    ->options(['' => '—'] + collect(PaymentStatus::cases())
                        ->mapWithKeys(fn (PaymentStatus $s): array => [$s->value => $s->value])
                        ->all())
                    ->value(request()->query('payment_status', ''))
                    ->title(__('sales.payment_status')),
            ]),
            SaleListLayout::class,
        ];
    }
}
