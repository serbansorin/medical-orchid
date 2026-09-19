<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\BillingInvoice;
use App\Models\MedicalAttachment;
use App\Models\MedicalLetter;
use App\Models\MedicalVisit;
use App\Models\PatientDiagnostic;
use App\Models\PatientExamResult;
use App\Models\PatientReport;
use App\Models\Prescription;
use App\Models\Referral;
use App\Models\VisitServiceLine;
use App\Services\Visits\VisitService;
use Illuminate\Support\Facades\Route;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

/**
 * ★ Medical visit detail: header view + commandBar + ?tab= tabs (FR-15).
 */
class VisitScreen extends Screen
{
    public ?MedicalVisit $visit = null;

    public function __construct(private readonly VisitService $visits) {}

    public function query(MedicalVisit $visit): iterable
    {
        $visit->loadMissing(['patient', 'doctor', 'clinic', 'appointment', 'episode', 'serviceLines.product', 'prescriptions', 'invoices']);

        $patientName = trim(($visit->patient?->first_name ?? '').' '.($visit->patient?->last_name ?? '')) ?: '—';
        $doctorName = trim(($visit->doctor?->first_name ?? '').' '.($visit->doctor?->last_name ?? '')) ?: '—';

        $reportQuery = PatientReport::query()->where('medical_visit_id', $visit->getKey());

        return [
            'visit' => $visit,
            'patientName' => $patientName,
            'doctorName' => $doctorName,
            'clinicName' => $visit->clinic?->name ?? '—',
            'visitDate' => $visit->visit_date?->format('Y-m-d H:i') ?? '—',
            'appointmentId' => $visit->appointment_id,
            'statusLabel' => __('visits.status_'.((string) ($visit->status ?? 'scheduled'))),
            'episodeLabel' => $visit->episode_id !== null ? __('visits.episode').' #'.$visit->episode_id : null,
            'allergyAlert' => null,
            'tab' => $this->activeTab(),
            'diagnostics' => PatientDiagnostic::query()
                ->where('appointment_id', $visit->appointment_id)->latest()->take(25)->get(),
            'prescriptions' => $visit->prescriptions,
            'services' => $visit->serviceLines,
            'notes' => (clone $reportQuery)->latest()->take(25)->get(),
            'labs' => PatientExamResult::query()->where('patient_id', $visit->patient_id)
                ->whereHas('patientReport', fn ($q) => $q->where('medical_visit_id', $visit->getKey()))
                ->latest('exam_date')->take(25)->get(),
            'letters' => MedicalLetter::query()->where('appointment_id', $visit->appointment_id)->latest()->take(25)->get(),
            'referrals' => Referral::query()->where('appointment_id', $visit->appointment_id)->latest()->take(25)->get(),
            'attachments' => MedicalAttachment::query()->where('appointment_id', $visit->appointment_id)->latest()->take(25)->get(),
            'invoices' => $visit->invoices,
        ];
    }

    public function name(): ?string
    {
        return __('visits.detail_title');
    }

    public function description(): ?string
    {
        return __('visits.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_appointment'];
    }

    public function commandBar(): iterable
    {
        $id = $this->visit?->getKey();
        $locked = ($this->visit?->status ?? null) === 'completed';
        $bar = [];

        if (Route::has('platform.prescriptions.create')) {
            $bar[] = Link::make(__('visits.create_prescription'))
                ->route('platform.prescriptions.create', ['visit' => $id])->icon('bs.capsule')
                ->canSee(! $locked);
        }
        if (Route::has('platform.invoices.create')) {
            $bar[] = Link::make(__('visits.create_invoice'))
                ->route('platform.invoices.create', ['visit' => $id])->icon('bs.receipt')
                ->canSee(! $locked);
        }

        $bar[] = Button::make(__('visits.finalize'))->method('finalize')
            ->type(Color::PRIMARY)->icon('bs.flag')
            ->confirm(__('visits.finalize_confirm'))
            ->canSee(! $locked);

        return $bar;
    }

    public function layout(): iterable
    {
        return [
            Layout::view('orchid.cabinet.visit-header'),

            Layout::tabs([
                __('visits.tab_overview') => [
                    Layout::view('orchid.cabinet.visit-header'),
                ],
                __('visits.tab_diagnostics') => [
                    Layout::table('diagnostics', [
                        TD::make('title', __('visits.title_col'))->render(fn ($d) => e($d->title ?? '—')),
                        TD::make('status', __('visits.status'))->render(fn ($d) => e($d->status ?? '—')),
                    ]),
                ],
                __('visits.tab_prescriptions') => [
                    Layout::table('prescriptions', [
                        TD::make('prescription_number', __('visits.number'))
                            ->render(fn (Prescription $p) => e($p->prescription_number ?? '#'.$p->getKey())),
                        TD::make('status', __('visits.status'))->render(fn (Prescription $p) => e($p->status ?? '—')),
                    ]),
                ],
                __('visits.tab_services') => [
                    Layout::table('services', [
                        TD::make('product', __('visits.title_col'))
                            ->render(fn (VisitServiceLine $s) => e($s->product?->name ?? '#'.$s->product_id)),
                        TD::make('quantity', __('visits.quantity'))->render(fn (VisitServiceLine $s) => e((string) $s->quantity)),
                        TD::make('unit_price', __('visits.unit_price'))->render(fn (VisitServiceLine $s) => e((string) $s->unit_price)),
                        TD::make('status', __('visits.status'))->render(fn (VisitServiceLine $s) => e($s->status ?? '—')),
                    ]),
                ],
                __('visits.tab_notes') => [
                    Layout::table('notes', [
                        TD::make('type', __('visits.type'))->render(fn (PatientReport $r) => e($r->type ?? '—')),
                        TD::make('title', __('visits.title_col'))->render(fn (PatientReport $r) => e($r->title ?? '—')),
                    ]),
                ],
                __('visits.tab_labs') => [
                    Layout::table('labs', [
                        TD::make('exam_type', __('visits.type'))->render(fn (PatientExamResult $e) => e($e->exam_type ?? '—')),
                        TD::make('title', __('visits.title_col'))->render(fn (PatientExamResult $e) => e($e->title ?? '—')),
                        TD::make('status', __('visits.status'))->render(fn (PatientExamResult $e) => e($e->status ?? '—')),
                    ]),
                ],
                __('visits.tab_letters') => [
                    Layout::table('letters', [
                        TD::make('title', __('visits.title_col'))->render(fn (MedicalLetter $l) => e($l->title ?? '—')),
                    ]),
                    Layout::table('referrals', [
                        TD::make('referral_number', __('visits.number'))->render(fn (Referral $r) => e($r->referral_number ?? '#'.$r->getKey())),
                        TD::make('status', __('visits.status'))->render(fn (Referral $r) => e($r->status ?? '—')),
                    ]),
                ],
                __('visits.tab_attachments') => [
                    Layout::table('attachments', [
                        TD::make('title', __('visits.title_col'))->render(fn (MedicalAttachment $a) => e($a->title ?? '—')),
                    ]),
                ],
                __('visits.tab_invoice') => [
                    Layout::table('invoices', [
                        TD::make('invoice_number', __('visits.number'))
                            ->render(function (BillingInvoice $i) {
                                if (Route::has('platform.invoices.detail')) {
                                    return Link::make(e($i->invoice_number ?? '#'.$i->getKey()))
                                        ->route('platform.invoices.detail', $i->getKey());
                                }

                                return e($i->invoice_number ?? '#'.$i->getKey());
                            }),
                        TD::make('status', __('visits.status'))->render(fn (BillingInvoice $i) => e($i->status ?? '—')),
                        TD::make('total_amount', __('visits.total'))->render(fn (BillingInvoice $i) => e((string) ($i->total_amount ?? '—'))),
                    ]),
                ],
            ])->activeTab($this->activeTab()),
        ];
    }

    public function finalize(MedicalVisit $visit): void
    {
        $this->visits->finalize($visit);
        Toast::success(__('visits.finalized'));
    }

    /**
     * @return array<string>
     */
    private function tabs(): array
    {
        return [
            __('visits.tab_overview'),
            __('visits.tab_diagnostics'),
            __('visits.tab_prescriptions'),
            __('visits.tab_services'),
            __('visits.tab_notes'),
            __('visits.tab_labs'),
            __('visits.tab_letters'),
            __('visits.tab_attachments'),
            __('visits.tab_invoice'),
        ];
    }

    private function activeTab(): string
    {
        $tab = (string) request()->query('tab', __('visits.tab_overview'));

        return in_array($tab, $this->tabs(), true) ? $tab : __('visits.tab_overview');
    }
}
