<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Pharmacy;

use App\Models\PrescriptionRouting;
use App\Orchid\Layouts\Pharmacy\FulfillmentListLayout;
use App\Services\Pharmacy\FulfillmentService;
use App\Services\Pharmacy\PharmacyScope;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * ★ Fulfillment queue (designs/fulfillment.md): prescription_routing rows
 * joined to prescriptions/patients, per-row "Dispense" modal
 * (quantity + expiry inputs) handled by FulfillmentService::dispense().
 */
class FulfillmentScreen extends Screen
{
    public function __construct(private readonly FulfillmentService $fulfillment) {}

    public function query(): iterable
    {
        $pharmacyId = PharmacyScope::currentPharmacyId();

        $queue = PrescriptionRouting::with(['prescription.patient', 'prescription.prescriptionMedications'])
            ->when($pharmacyId !== null, fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            // No status literals exist in legacy code for prescription_routing;
            // pending = not in defensive terminal states (commented in service).
            ->whereNotIn('status', FulfillmentService::TERMINAL_ROUTING_STATUSES)
            ->orderByDesc('created_at')
            ->paginate(25);

        return ['queue' => $queue];
    }

    public function name(): ?string
    {
        return __('fulfillment.title');
    }

    public function description(): ?string
    {
        return __('fulfillment.description');
    }

    public function permission(): ?iterable
    {
        // Verified legacy slugs: access_pharmacy_panel exists; there is no
        // view_any_pharmacy_sale / fulfillment-specific slug.
        return ['access_pharmacy_panel'];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            FulfillmentListLayout::class,
            Layout::modal('dispenseModal', Layout::rows([
                Input::make('dispense.quantity_dispensed')
                    ->type('number')->min(1)
                    ->title(__('fulfillment.quantity')),
                DateTimer::make('dispense.expiry_date')
                    ->format('Y-m-d')
                    ->title(__('fulfillment.expiry')),
                TextArea::make('dispense.notes')->rows(2)
                    ->title(__('fulfillment.notes')),
            ]))->title(__('fulfillment.modal_title'))->applyButton(__('fulfillment.modal_apply')),
        ];
    }

    public function dispense(Request $request): void
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:prescription_routing,id',
            'dispense.quantity_dispensed' => 'nullable|integer|min:1',
            'dispense.expiry_date' => 'nullable|date',
            'dispense.notes' => 'nullable|string|max:2000',
        ]);

        $routing = PrescriptionRouting::findOrFail($data['id']);

        $pharmacyId = PharmacyScope::currentPharmacyId();
        if ($pharmacyId !== null && (int) $routing->pharmacy_id !== $pharmacyId) {
            abort(403);
        }

        $this->fulfillment->dispense($routing, [
            'quantity_dispensed' => $data['dispense']['quantity_dispensed'] ?? null,
            'expiry_date' => $data['dispense']['expiry_date'] ?? null,
            'notes' => $data['dispense']['notes'] ?? null,
        ]);

        Toast::success(__('fulfillment.dispensed_ok'));
    }
}
