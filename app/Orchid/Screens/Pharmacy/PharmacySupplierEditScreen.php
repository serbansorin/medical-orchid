<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Models\Pharmacy;
use App\Models\PharmacySupplier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PharmacySupplierEditScreen extends Screen
{
    public ?PharmacySupplier $supplier = null;

    public function query(PharmacySupplier $supplier): iterable
    {
        $linked = $supplier->exists
            ? DB::table('pharmacy_purchase_orders')->where('supplier_id', $supplier->id)->orderByDesc('id')->get()
            : collect();

        return [
            'supplier' => $supplier,
            'linkedOrders' => new LengthAwarePaginator($linked, $linked->count(), 25),
        ];
    }

    public function name(): ?string
    {
        return ($this->supplier?->exists ?? false) ? __('suppliers.edit') : __('suppliers.create');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('suppliers.saved'))->method('save')->icon('bs.check-circle'),
            Link::make(__('suppliers.title'))->route('platform.suppliers'),
        ];
    }

    public function layout(): iterable
    {
        // Keys use the REAL pharmacy_suppliers columns (code, city, county,
        // postal_code, country, bank_name, bank_account, payment_terms_days);
        // the verbatim model only fills a subset → save uses forceFill().
        return [
            Layout::rows([
                Select::make('supplier.pharmacy_id')
                    ->fromModel(Pharmacy::class, 'name')
                    ->title(__('inventory.pharmacy')),
                Input::make('supplier.name')->title(__('suppliers.name'))->required(),
                Input::make('supplier.code')->title(__('suppliers.code')),
                Input::make('supplier.contact_person')->title(__('suppliers.contact')),
                Input::make('supplier.email')->type('email')->title(__('suppliers.email')),
                Input::make('supplier.phone')->title(__('suppliers.phone')),
                TextArea::make('supplier.address')->rows(2)->title(__('suppliers.address')),
                Input::make('supplier.city')->title(__('suppliers.city')),
                Input::make('supplier.county')->title(__('suppliers.county')),
                Input::make('supplier.postal_code')->title(__('suppliers.postal')),
                Input::make('supplier.country')->title(__('suppliers.country')),
                Input::make('supplier.tax_id')->title(__('suppliers.tax')),
                Input::make('supplier.bank_name')->title(__('suppliers.bank')),
                Input::make('supplier.bank_account')->title(__('suppliers.account')),
                Input::make('supplier.payment_terms_days')->type('number')->min(0)
                    ->title(__('suppliers.terms')),
                Switcher::make('supplier.is_active')
                    ->sendTrueOrFalse()
                    ->title(__('suppliers.active')),
                TextArea::make('supplier.notes')->rows(2)->title(__('suppliers.notes')),
            ]),
            Layout::block(
                Layout::table('linkedOrders', [
                    TD::make('order_number', __('purchase-orders.number'))
                        ->render(fn ($row): string => e($row->order_number ?? '#'.$row->id)),
                    TD::make('status', __('purchase-orders.status'))
                        ->render(fn ($row): string => e($row->status ?? '—')),
                    TD::make('total_amount', __('purchase-orders.total'))
                        ->render(fn ($row): string => number_format((float) $row->total_amount, 2)),
                ])
            )->title(__('suppliers.orders')),
        ];
    }

    public function save(Request $request, PharmacySupplier $supplier): mixed
    {
        $data = $request->validate([
            'supplier.pharmacy_id' => 'nullable|integer|exists:pharmacies,id',
            'supplier.name' => 'required|string|max:255',
            'supplier.code' => 'nullable|string|max:100',
            'supplier.contact_person' => 'nullable|string|max:255',
            'supplier.email' => 'nullable|email|max:255',
            'supplier.phone' => 'nullable|string|max:50',
            'supplier.address' => 'nullable|string',
            'supplier.city' => 'nullable|string|max:100',
            'supplier.county' => 'nullable|string|max:100',
            'supplier.postal_code' => 'nullable|string|max:20',
            'supplier.country' => 'nullable|string|max:100',
            'supplier.tax_id' => 'nullable|string|max:50',
            'supplier.bank_name' => 'nullable|string|max:255',
            'supplier.bank_account' => 'nullable|string|max:100',
            'supplier.payment_terms_days' => 'nullable|integer|min:0',
            'supplier.is_active' => 'nullable|boolean',
            'supplier.notes' => 'nullable|string',
        ])['supplier'];

        $supplier->forceFill($data)->save();

        Toast::success(__('suppliers.saved'));

        return redirect()->route('platform.suppliers.edit', $supplier);
    }
}
