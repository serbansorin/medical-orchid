<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Clinics;

use App\Models\Clinic;
use App\Models\ClinicDepartment;
use App\Models\ClinicDoctorAssignment;
use App\Models\Doctor;
use App\Models\Organization;
use App\Models\Patient;
use App\Orchid\Layouts\Organize\Clinics\ClinicAssignmentTable;
use App\Orchid\Layouts\Organize\Clinics\ClinicDepartmentTable;
use App\Orchid\Layouts\Organize\Clinics\ClinicPatientTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ClinicEditScreen extends Screen
{
    public ?Clinic $clinic = null;

    public function query(Clinic $clinic): iterable
    {
        $exists = $clinic->exists;

        return [
            'clinic' => $clinic,
            'departments' => $exists
                ? ClinicDepartment::where('clinic_id', $clinic->id)->orderBy('name')->paginate(10)
                : ClinicDepartment::whereRaw('1 = 0')->paginate(10),
            'assignments' => $exists
                ? ClinicDoctorAssignment::with(['doctor', 'clinic_department'])
                    ->where('clinic_id', $clinic->id)->orderByDesc('id')->paginate(10)
                : ClinicDoctorAssignment::whereRaw('1 = 0')->paginate(10),
            'clinicPatients' => $exists
                ? $clinic->patients()->orderBy('patients.first_name')->paginate(10)
                : Patient::whereRaw('1 = 0')->paginate(10),
        ];
    }

    public function name(): ?string
    {
        return $this->clinic && $this->clinic->exists
            ? __('clinics.edit_title')
            : __('clinics.create_title');
    }

    public function description(): ?string
    {
        return __('clinics.form_description');
    }

    public function permission(): ?iterable
    {
        return ['access_admin_panel'];
    }

    public function commandBar(): iterable
    {
        $exists = (bool) ($this->clinic && $this->clinic->exists);

        return [
            Link::make(__('clinics.back'))
                ->icon('bs.arrow-left')
                ->route('platform.clinics'),

            Button::make(__('clinics.remove'))
                ->icon('bs.trash3')
                ->confirm(__('clinics.removed'))
                ->method('remove')
                ->canSee($exists),

            ModalToggle::make(__('clinics.department_add'))
                ->modal('departmentModal')
                ->method('saveDepartment')
                ->icon('bs.plus-circle')
                ->canSee($exists),

            ModalToggle::make(__('clinics.assignment_attach'))
                ->modal('assignmentModal')
                ->method('attachDoctor')
                ->icon('bs.plus-circle')
                ->canSee($exists),

            ModalToggle::make(__('clinics.patient_attach'))
                ->modal('patientModal')
                ->method('attachPatient')
                ->icon('bs.plus-circle')
                ->canSee($exists),

            Button::make(__('clinics.save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $exists = (bool) ($this->clinic && $this->clinic->exists);
        $orgOptions = Organization::orderBy('name')->limit(200)->pluck('name', 'id')->toArray();
        $doctorOptions = Doctor::orderBy('last_name')->orderBy('first_name')->limit(200)->get()
            ->mapWithKeys(fn (Doctor $d): array => [$d->id => trim($d->first_name.' '.$d->last_name) ?: '#'.$d->id])
            ->toArray();
        $departmentOptions = $exists
            ? ClinicDepartment::where('clinic_id', $this->clinic->id)->orderBy('name')->pluck('name', 'id')->toArray()
            : [];
        $patientOptions = $exists
            ? Patient::whereNotIn('id', fn ($q) => $q->select('patient_id')->from('clinic_patient')->where('clinic_id', $this->clinic->id))
                ->orderBy('first_name')->limit(200)->get()
                ->mapWithKeys(fn (Patient $p): array => [$p->id => $p->full_name ?: '#'.$p->id])
                ->toArray()
            : [];

        $infoTab = Layout::rows([
            Select::make('clinic.organization_id')
                ->title(__('clinics.organization'))
                ->options($orgOptions)
                ->empty()
                ->required(),

            Input::make('clinic.name')
                ->title(__('clinics.name'))
                ->required()
                ->maxlength(255),

            Input::make('clinic.code')
                ->title(__('clinics.code'))
                ->help(__('clinics.code_locked'))
                ->maxlength(50)
                ->readonly($exists),

            Select::make('clinic.type')
                ->title(__('clinics.type'))
                ->options([
                    'main' => __('clinics.type_main'),
                    'branch' => __('clinics.type_branch'),
                ])
                ->empty(),

            Input::make('clinic.phone')
                ->title(__('clinics.phone'))
                ->maxlength(50),

            Input::make('clinic.email')
                ->type('email')
                ->title(__('clinics.email'))
                ->maxlength(255),

            Input::make('clinic.website')
                ->type('url')
                ->title(__('clinics.website'))
                ->maxlength(255),

            Input::make('clinic.address_line1')
                ->title(__('clinics.address_line1'))
                ->maxlength(255),

            Input::make('clinic.address_line2')
                ->title(__('clinics.address_line2'))
                ->maxlength(255),

            Input::make('clinic.city')
                ->title(__('clinics.city'))
                ->maxlength(100),

            Input::make('clinic.state')
                ->title(__('clinics.state'))
                ->maxlength(100),

            Input::make('clinic.postal_code')
                ->title(__('clinics.postal_code'))
                ->maxlength(20),

            Input::make('clinic.country')
                ->title(__('clinics.country'))
                ->maxlength(10),

            Input::make('clinic.timezone')
                ->title(__('clinics.timezone'))
                ->maxlength(50),

            Switcher::make('clinic.is_active')
                ->title(__('clinics.is_active'))
                ->sendTrueOrFalse(),
        ]);

        if (! $exists) {
            return [$infoTab];
        }

        return [
            Layout::tabs([
                __('clinics.tab_info') => [$infoTab],
                __('clinics.tab_departments') => [ClinicDepartmentTable::class],
                __('clinics.tab_doctors') => [ClinicAssignmentTable::class],
                __('clinics.tab_patients') => [ClinicPatientTable::class],
            ]),

            Layout::modal('departmentModal', Layout::rows([
                Input::make('department.id')->type('hidden'),
                Input::make('department.name')
                    ->title(__('clinics.department_name'))
                    ->required()
                    ->maxlength(255),
                TextArea::make('department.description')
                    ->title(__('clinics.department_description'))
                    ->rows(3),
                Switcher::make('department.is_active')
                    ->title(__('clinics.department_active'))
                    ->sendTrueOrFalse(),
            ]))->title(__('clinics.department_add'))->applyButton(__('clinics.apply'))->deferred('loadDepartment'),

            Layout::modal('assignmentModal', Layout::rows([
                Select::make('assignment.doctor_id')
                    ->title(__('clinics.assignment_doctor'))
                    ->options($doctorOptions)
                    ->empty()
                    ->required(),
                Select::make('assignment.department_id')
                    ->title(__('clinics.assignment_department'))
                    ->options($departmentOptions)
                    ->empty(),
                Select::make('assignment.role')
                    ->title(__('clinics.assignment_role'))
                    ->options([
                        'primary' => __('clinics.role_primary'),
                        'secondary' => __('clinics.role_secondary'),
                        'consultant' => __('clinics.role_consultant'),
                    ])
                    ->empty(),
                Select::make('assignment.status')
                    ->title(__('clinics.assignment_status'))
                    ->options([
                        'active' => __('clinics.status_active'),
                        'inactive' => __('clinics.status_inactive'),
                        'suspended' => __('clinics.status_suspended'),
                    ])
                    ->empty(),
                DateTimer::make('assignment.start_date')
                    ->title(__('clinics.assignment_start'))
                    ->format('Y-m-d')
                    ->allowInput(),
                DateTimer::make('assignment.end_date')
                    ->title(__('clinics.assignment_end'))
                    ->format('Y-m-d')
                    ->allowInput(),
            ]))->title(__('clinics.assignment_attach'))->applyButton(__('clinics.apply')),

            Layout::modal('patientModal', Layout::rows([
                Select::make('patient_id')
                    ->title(__('clinics.patient_name'))
                    ->options($patientOptions)
                    ->empty()
                    ->required(),
            ]))->title(__('clinics.patient_attach'))->applyButton(__('clinics.apply')),
        ];
    }

    public function save(Request $request, Clinic $clinic): RedirectResponse
    {
        $data = $request->validate([
            'clinic.organization_id' => 'required|integer|exists:organizations,id',
            'clinic.name' => 'required|string|max:255',
            'clinic.code' => 'nullable|string|max:50',
            'clinic.type' => 'nullable|string|max:50',
            'clinic.phone' => 'nullable|string|max:50',
            'clinic.email' => 'nullable|email|max:255',
            'clinic.website' => 'nullable|url|max:255',
            'clinic.address_line1' => 'nullable|string|max:255',
            'clinic.address_line2' => 'nullable|string|max:255',
            'clinic.city' => 'nullable|string|max:100',
            'clinic.state' => 'nullable|string|max:100',
            'clinic.postal_code' => 'nullable|string|max:20',
            'clinic.country' => 'nullable|string|max:10',
            'clinic.timezone' => 'nullable|string|max:50',
            'clinic.is_active' => 'nullable|boolean',
        ])['clinic'];

        // Code is immutable after create (model hooks generate it when blank).
        if ($clinic->exists) {
            unset($data['code']);
        }

        // forceFill: the ported model $fillable omits several legacy columns.
        $clinic->forceFill($data)->save();

        Toast::info(__('clinics.saved'));

        return redirect()->route('platform.clinics');
    }

    public function remove(Clinic $clinic): RedirectResponse
    {
        $clinic->delete();

        Toast::info(__('clinics.removed'));

        return redirect()->route('platform.clinics');
    }

    public function loadDepartment(ClinicDepartment $department): iterable
    {
        return ['department' => $department];
    }

    public function saveDepartment(Request $request, Clinic $clinic): void
    {
        $data = $request->validate([
            'department.id' => 'nullable|integer|exists:clinic_departments,id',
            'department.name' => 'required|string|max:255',
            'department.description' => 'nullable|string',
            'department.is_active' => 'nullable|boolean',
        ])['department'];

        DB::transaction(function () use ($data, $clinic): void {
            $department = isset($data['id'])
                ? ClinicDepartment::where('clinic_id', $clinic->id)->findOrFail($data['id'])
                : new ClinicDepartment(['clinic_id' => $clinic->id]);

            $department->forceFill([
                'clinic_id' => $clinic->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();
        });

        Toast::info(__('clinics.department_saved'));
    }

    public function removeDepartment(Request $request, Clinic $clinic): void
    {
        ClinicDepartment::where('clinic_id', $clinic->id)->findOrFail($request->integer('id'))->delete();

        Toast::info(__('clinics.department_removed'));
    }

    public function attachDoctor(Request $request, Clinic $clinic): void
    {
        $data = $request->validate([
            'assignment.doctor_id' => 'required|integer|exists:doctors,id',
            'assignment.department_id' => 'nullable|integer|exists:clinic_departments,id',
            'assignment.role' => 'nullable|string|max:50',
            'assignment.status' => 'nullable|string|max:50',
            'assignment.start_date' => 'nullable|date',
            'assignment.end_date' => 'nullable|date|after_or_equal:assignment.start_date',
        ])['assignment'];

        ClinicDoctorAssignment::create([
            'clinic_id' => $clinic->id,
            'doctor_id' => $data['doctor_id'],
            'department_id' => $data['department_id'] ?? null,
            'role' => $data['role'] ?? 'primary',
            'status' => $data['status'] ?? 'active',
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        Toast::info(__('clinics.assignment_attached'));
    }

    public function detachDoctor(Request $request, Clinic $clinic): void
    {
        ClinicDoctorAssignment::where('clinic_id', $clinic->id)->findOrFail($request->integer('id'))->delete();

        Toast::info(__('clinics.assignment_detached'));
    }

    public function attachPatient(Request $request, Clinic $clinic): void
    {
        $data = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
        ]);

        $clinic->patients()->syncWithoutDetaching([$data['patient_id']]);

        Toast::info(__('clinics.patient_attached'));
    }

    public function detachPatient(Request $request, Clinic $clinic): void
    {
        $clinic->patients()->detach($request->integer('id'));

        Toast::info(__('clinics.patient_detached'));
    }
}
