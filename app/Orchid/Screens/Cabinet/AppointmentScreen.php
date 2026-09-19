<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Appointment;
use App\Models\MedicalVisit;
use App\Services\Cabinet\AppointmentService;
use App\Services\Visits\VisitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * ★ Appointment detail: header view + commandBar + ?tab= tabs (FR-14).
 */
class AppointmentScreen extends Screen
{
    public ?Appointment $appointment = null;

    public function __construct(
        private readonly AppointmentService $appointments,
        private readonly VisitService $visits,
    ) {}

    public function query(Appointment $appointment): iterable
    {
        $appointment->loadMissing(['patient', 'doctor', 'clinic', 'products', 'diagnostics', 'billing_invoices', 'medical_records']);

        $visit = MedicalVisit::query()->where('appointment_id', $appointment->getKey())->first();

        $patientName = trim(($appointment->patient?->first_name ?? '').' '.($appointment->patient?->last_name ?? '')) ?: '—';
        $doctorName = trim(($appointment->doctor?->first_name ?? '').' '.($appointment->doctor?->last_name ?? '')) ?: '—';
        $slot = ($appointment->appointment_date?->format('Y-m-d') ?? '—').' '.
            (is_string($appointment->appointment_time) ? substr($appointment->appointment_time, 0, 5) : ($appointment->appointment_time?->format('H:i') ?? ''));

        return [
            'appointment' => $appointment,
            'visit' => $visit,
            'visitId' => $visit?->getKey(),
            'patientName' => $patientName,
            'doctorName' => $doctorName,
            'clinicName' => $appointment->clinic?->name ?? '—',
            'slot' => trim($slot),
            'statusLabel' => __('appointments.status_'.str_replace('-', '_', (string) ($appointment->status ?? 'scheduled'))),
            'isPaid' => $appointment->isPaid(),
            'allergyAlert' => null,
            'tab' => $this->activeTab(),
            'products' => $appointment->products,
            'diagnostics' => $appointment->diagnostics,
            'invoices' => $appointment->billing_invoices,
        ];
    }

    public function name(): ?string
    {
        return __('appointments.detail_title');
    }

    public function description(): ?string
    {
        return __('appointments.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_appointment'];
    }

    public function commandBar(): iterable
    {
        $id = $this->appointment?->getKey();
        $hasVisit = MedicalVisit::query()->where('appointment_id', $id)->exists();
        $bar = [];

        if (! $hasVisit) {
            $bar[] = Button::make(__('appointments.start_visit'))
                ->method('startVisit')->type(Color::PRIMARY)->icon('bs.play-circle');
        }

        $bar[] = Button::make(__('appointments.confirm'))->method('confirm')->icon('bs.check')
            ->canSee(($this->appointment?->status ?? null) === 'scheduled');
        $bar[] = Button::make(__('appointments.complete'))->method('complete')->icon('bs.check-all')
            ->canSee(in_array($this->appointment?->status, ['scheduled', 'confirmed'], true));
        $bar[] = Button::make(__('appointments.no_show'))->method('markNoShow')->icon('bs.clock')
            ->canSee(in_array($this->appointment?->status, ['scheduled', 'confirmed'], true));
        $bar[] = ModalToggle::make(__('appointments.cancel'))->modal('cancelModal')
            ->method('cancel')->icon('bs.x-circle')
            ->canSee(in_array($this->appointment?->status, ['scheduled', 'confirmed'], true));
        $bar[] = ModalToggle::make(__('appointments.add_note'))->modal('noteModal')
            ->method('addNote')->icon('bs.sticky');

        if (Route::has('platform.invoices.create')) {
            $bar[] = Link::make(__('appointments.create_invoice'))
                ->route('platform.invoices.create', ['appointment' => $id])->icon('bs.receipt');
        }
        if (Route::has('platform.prescriptions.create')) {
            $bar[] = Link::make(__('appointments.create_prescription'))
                ->route('platform.prescriptions.create', ['appointment' => $id])->icon('bs.capsule');
        }

        return $bar;
    }

    public function layout(): iterable
    {
        return [
            Layout::view('orchid.cabinet.appointment-header'),

            Layout::modal('cancelModal', Layout::rows([
                TextArea::make('cancel_reason')->title(__('appointments.cancel_reason'))->rows(3)->required(),
            ]))->title(__('appointments.cancel')),

            Layout::modal('noteModal', Layout::rows([
                TextArea::make('note')->title(__('appointments.note_text'))->rows(3)->required(),
            ]))->title(__('appointments.add_note')),

            Layout::tabs([
                __('appointments.tab_overview') => [
                    Layout::view('orchid.cabinet.appointment-header'),
                ],
                __('appointments.tab_products') => [
                    Layout::table('products', [
                        TD::make('name', __('appointments.product'))->render(fn ($p) => e($p->name ?? '—')),
                    ]),
                ],
                __('appointments.tab_diagnostics') => [
                    Layout::table('diagnostics', [
                        TD::make('title', __('appointments.tab_diagnostics'))->render(fn ($d) => e($d->title ?? '—')),
                        TD::make('status', __('appointments.status'))->render(fn ($d) => e($d->status ?? '—')),
                    ]),
                ],
                __('appointments.tab_billing') => [
                    Layout::table('invoices', [
                        TD::make('invoice_number', __('appointments.create_invoice'))->render(fn ($i) => e($i->invoice_number ?? '#'.$i->getKey())),
                        TD::make('status', __('appointments.status'))->render(fn ($i) => e($i->status ?? '—')),
                    ]),
                ],
            ])->activeTab($this->activeTab()),
        ];
    }

    public function startVisit(Appointment $appointment): mixed
    {
        $visit = $this->visits->startFromAppointment($appointment);

        Toast::success(__('appointments.visit_started'));

        return redirect()->route('platform.visits.detail', $visit);
    }

    public function confirm(Appointment $appointment): void
    {
        $this->appointments->transition($appointment, 'confirmed');
        Toast::success(__('appointments.confirmed'));
    }

    public function cancel(Request $request, Appointment $appointment): void
    {
        $request->validate(['cancel_reason' => 'required|string']);
        $this->appointments->transition($appointment, 'cancelled', (string) $request->input('cancel_reason'));
        Toast::success(__('appointments.cancelled'));
    }

    public function markNoShow(Appointment $appointment): void
    {
        $this->appointments->transition($appointment, 'no-show');
        Toast::success(__('appointments.marked_no_show'));
    }

    public function complete(Appointment $appointment): void
    {
        $this->appointments->transition($appointment, 'completed');
        Toast::success(__('appointments.completed'));
    }

    public function addNote(Request $request, Appointment $appointment): void
    {
        $request->validate(['note' => 'required|string']);
        $stamp = now()->format('d/m/Y H:i');
        $existing = (string) ($appointment->notes ?? '');
        $appointment->update(['notes' => trim($existing.($existing !== '' ? "\n\n" : '')."[{$stamp}] {$request->input('note')}")]);
        Toast::success(__('appointments.note_added'));
    }

    /**
     * @return array<string>
     */
    private function tabs(): array
    {
        return [
            __('appointments.tab_overview'),
            __('appointments.tab_products'),
            __('appointments.tab_diagnostics'),
            __('appointments.tab_billing'),
        ];
    }

    private function activeTab(): string
    {
        $tab = (string) request()->query('tab', __('appointments.tab_overview'));

        return in_array($tab, $this->tabs(), true) ? $tab : __('appointments.tab_overview');
    }
}
