<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Appointment;
use App\Models\BillingInvoice;
use App\Models\MedicalVisit;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientExamResult;
use App\Models\PatientReport;
use App\Models\Prescription;
use Illuminate\Support\Facades\Route;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

/**
 * ★ Patient detail: header view + commandBar + ?tab= tabs (FR-13).
 * One Screen class; every tab deep-linkable via route(..., ['tab' => 'x']).
 */
class PatientScreen extends Screen
{
    public ?Patient $patient = null;

    public function query(Patient $patient): iterable
    {
        $id = $patient->getKey();

        $alerts = [];
        $abnormal = PatientReport::query()
            ->where('patient_id', $id)
            ->where('is_abnormal', true)
            ->where('created_at', '>=', now()->subDay())
            ->exists();
        if ($abnormal) {
            $alerts[] = __('patients.alerts').': abnormal';
        }

        return [
            'patient' => $patient,
            'alerts' => $alerts,
            'tab' => $this->activeTab(),
            'appointments' => Appointment::query()->with(['doctor', 'clinic'])
                ->where('patient_id', $id)->latest('appointment_date')->take(25)->get(),
            'visits' => MedicalVisit::query()->with(['doctor', 'clinic'])
                ->where('patient_id', $id)->latest('visit_date')->take(25)->get(),
            'prescriptions' => Prescription::query()->with(['doctor'])
                ->where('patient_id', $id)->latest()->take(25)->get(),
            'reports' => PatientReport::query()
                ->where('patient_id', $id)->latest()->take(25)->get(),
            'exams' => PatientExamResult::query()
                ->where('patient_id', $id)->latest('exam_date')->take(25)->get(),
            'documents' => PatientDocument::query()
                ->where('patient_id', $id)->latest()->take(25)->get(),
            'invoices' => BillingInvoice::query()
                ->where('patient_id', $id)->latest('invoice_date')->take(25)->get(),
            'notes' => PatientReport::query()
                ->where('patient_id', $id)->where('type', 'medical_note')->latest()->take(25)->get(),
        ];
    }

    public function name(): ?string
    {
        return $this->patient !== null
            ? trim(($this->patient->first_name ?? '').' '.($this->patient->last_name ?? ''))
            : __('patients.detail_title');
    }

    public function description(): ?string
    {
        return __('patients.detail_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_patient'];
    }

    public function commandBar(): iterable
    {
        $id = $this->patient?->getKey();
        $bar = [
            Link::make(__('patients.edit'))->route('platform.patients.edit', $id)->icon('bs.pencil'),
            Link::make(__('patients.create_appointment'))
                ->route('platform.appointments.create', ['patient' => $id])->icon('bs.calendar-plus'),
        ];

        if (Route::has('platform.prescriptions.create')) {
            $bar[] = Link::make(__('patients.new_prescription'))
                ->route('platform.prescriptions.create', ['patient' => $id])->icon('bs.capsule');
        }

        return $bar;
    }

    public function layout(): iterable
    {
        $tab = $this->activeTab();

        return [
            Layout::view('orchid.cabinet.patient-header'),

            Layout::tabs([
                __('patients.tab_overview') => [
                    Layout::view('orchid.cabinet.patient-header'),
                ],
                __('patients.tab_appointments') => [
                    Layout::table('appointments', [
                        TD::make('appointment_date', __('patients.date'))
                            ->render(fn (Appointment $a) => Link::make(e($a->appointment_date?->format('Y-m-d') ?? '—'))
                                ->route('platform.appointments.detail', $a->getKey())),
                        TD::make('doctor', __('patients.doctor'))
                            ->render(fn (Appointment $a) => e(trim(($a->doctor?->first_name ?? '').' '.($a->doctor?->last_name ?? '')) ?: '—')),
                        TD::make('status', __('patients.status'))->render(fn (Appointment $a) => e($a->status ?? '—')),
                    ]),
                ],
                __('patients.tab_visits') => [
                    Layout::table('visits', [
                        TD::make('visit_date', __('patients.date'))
                            ->render(fn (MedicalVisit $v) => Link::make(e($v->visit_date?->format('Y-m-d') ?? '—'))
                                ->route('platform.visits.detail', $v->getKey())),
                        TD::make('doctor', __('patients.doctor'))
                            ->render(fn (MedicalVisit $v) => e(trim(($v->doctor?->first_name ?? '').' '.($v->doctor?->last_name ?? '')) ?: '—')),
                        TD::make('status', __('patients.status'))->render(fn (MedicalVisit $v) => e($v->status ?? '—')),
                    ]),
                ],
                __('patients.tab_prescriptions') => [
                    Layout::table('prescriptions', [
                        TD::make('prescription_number', __('patients.number'))
                            ->render(fn (Prescription $p) => e($p->prescription_number ?? '#'.$p->getKey())),
                        TD::make('status', __('patients.status'))->render(fn (Prescription $p) => e($p->status ?? '—')),
                        TD::make('created_at', __('patients.date'))
                            ->render(fn (Prescription $p) => e($p->created_at?->format('Y-m-d') ?? '—')),
                    ]),
                ],
                __('patients.tab_reports') => [
                    Layout::table('reports', [
                        TD::make('type', __('patients.type'))->render(fn (PatientReport $r) => e($r->type ?? '—')),
                        TD::make('title', __('patients.title_col'))->render(fn (PatientReport $r) => e($r->title ?? '—')),
                        TD::make('created_at', __('patients.date'))
                            ->render(fn (PatientReport $r) => e($r->created_at?->format('Y-m-d') ?? '—')),
                    ]),
                    Layout::table('exams', [
                        TD::make('exam_type', __('patients.type'))->render(fn (PatientExamResult $e) => e($e->exam_type ?? '—')),
                        TD::make('title', __('patients.title_col'))->render(fn (PatientExamResult $e) => e($e->title ?? '—')),
                        TD::make('exam_date', __('patients.date'))
                            ->render(fn (PatientExamResult $e) => e($e->exam_date?->format('Y-m-d') ?? '—')),
                    ]),
                ],
                __('patients.tab_documents') => [
                    Layout::table('documents', [
                        TD::make('title', __('patients.title_col'))->render(fn (PatientDocument $d) => e($d->title ?? '—')),
                        TD::make('type', __('patients.type'))->render(fn (PatientDocument $d) => e($d->type ?? '—')),
                        TD::make('created_at', __('patients.date'))
                            ->render(fn (PatientDocument $d) => e($d->created_at?->format('Y-m-d') ?? '—')),
                    ]),
                ],
                __('patients.tab_invoices') => [
                    Layout::table('invoices', [
                        TD::make('invoice_number', __('patients.number'))
                            ->render(function (BillingInvoice $i) {
                                if (Route::has('platform.invoices.detail')) {
                                    return Link::make(e($i->invoice_number ?? '#'.$i->getKey()))
                                        ->route('platform.invoices.detail', $i->getKey());
                                }

                                return e($i->invoice_number ?? '#'.$i->getKey());
                            }),
                        TD::make('status', __('patients.status'))->render(fn (BillingInvoice $i) => e($i->status ?? '—')),
                        TD::make('total_amount', __('patients.total'))
                            ->render(fn (BillingInvoice $i) => e((string) ($i->total_amount ?? '—'))),
                    ]),
                ],
                __('patients.tab_notes') => [
                    Layout::table('notes', [
                        TD::make('title', __('patients.title_col'))->render(fn (PatientReport $r) => e($r->title ?? '—')),
                        TD::make('notes', __('patients.tab_notes'))->render(fn (PatientReport $r) => e($r->notes ?? '—')),
                        TD::make('created_at', __('patients.date'))
                            ->render(fn (PatientReport $r) => e($r->created_at?->format('Y-m-d') ?? '—')),
                    ]),
                ],
            ])->activeTab($tab),
        ];
    }

    /**
     * @return array<string>
     */
    private function tabs(): array
    {
        return [
            __('patients.tab_overview'),
            __('patients.tab_appointments'),
            __('patients.tab_visits'),
            __('patients.tab_prescriptions'),
            __('patients.tab_reports'),
            __('patients.tab_documents'),
            __('patients.tab_invoices'),
            __('patients.tab_notes'),
        ];
    }

    private function activeTab(): string
    {
        $tab = (string) request()->query('tab', __('patients.tab_overview'));

        return in_array($tab, $this->tabs(), true) ? $tab : __('patients.tab_overview');
    }
}
