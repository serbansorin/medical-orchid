<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Models\Medication;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class InventoryEditScreen extends Screen
{
    public ?PharmacyInventory $inventory = null;

    public function query(PharmacyInventory $inventory): iterable
    {
        return ['inventory' => $inventory];
    }

    public function name(): ?string
    {
        return ($this->inventory?->exists ?? false)
            ? __('inventory.edit')
            : __('inventory.create');
    }

    public function permission(): ?iterable
    {
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('inventory.saved'))->method('save')->icon('bs.check-circle'),
            Link::make(__('inventory.title'))->route('platform.inventory'),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Keys mirror the REAL pharmacy_inventory columns (dumped from DB).
            Layout::rows([
                Select::make('inventory.pharmacy_id')
                    ->fromModel(Pharmacy::class, 'name')
                    ->title(__('inventory.pharmacy'))
                    ->required(),
                Select::make('inventory.medication_id')
                    ->fromModel(Medication::class, 'name')
                    ->empty()
                    ->title(__('inventory.medication')),
                Input::make('inventory.medication_name')
                    ->title(__('inventory.medication'))
                    ->required(),
                Input::make('inventory.strength')->title(__('inventory.strength')),
                Input::make('inventory.form')->title(__('inventory.form')),
                Input::make('inventory.stock_quantity')->type('number')->min(0)
                    ->title(__('inventory.stock'))->required(),
                Input::make('inventory.reorder_level')->type('number')->min(0)
                    ->title(__('inventory.reorder')),
                Input::make('inventory.price_per_unit')->type('number')->step(0.01)->min(0)
                    ->title(__('inventory.price')),
                Input::make('inventory.batch_number')->title(__('inventory.batch')),
                DateTimer::make('inventory.expiry_date')->format('Y-m-d')
                    ->title(__('inventory.expiry')),
            ]),
            // NOTE: designs/pharmacy-inventory.md asks for an adjust modal with a
            // reason → audit columns, but the real pharmacy_inventory table has no
            // audit/reason columns (dumped). Adjustments are plain quantity edits
            // on this form; deviation documented in the step report.
        ];
    }

    public function save(Request $request, PharmacyInventory $inventory): void
    {
        $data = $request->validate([
            'inventory.pharmacy_id' => 'required|integer|exists:pharmacies,id',
            'inventory.medication_id' => 'nullable|integer|exists:medications,id',
            'inventory.medication_name' => 'required|string|max:255',
            'inventory.strength' => 'nullable|string|max:100',
            // Live DB check pharmacy_inventory_form_check.
            'inventory.form' => 'nullable|string|in:tablet,capsule,liquid,injection,ointment,other',
            'inventory.stock_quantity' => 'required|integer|min:0',
            // reorder_level is NOT NULL on the live table.
            'inventory.reorder_level' => 'required|integer|min:0',
            'inventory.price_per_unit' => 'nullable|numeric|min:0',
            'inventory.batch_number' => 'nullable|string|max:100',
            'inventory.expiry_date' => 'nullable|date',
        ]);

        // PharmacyInventory has NO $fillable (verbatim copy) — forceFill the
        // real columns so the write persists regardless.
        $inventory->forceFill($data['inventory'])->save();

        Toast::success(__('inventory.saved'));
    }
}
