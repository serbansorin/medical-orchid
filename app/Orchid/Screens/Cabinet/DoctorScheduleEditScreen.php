<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorTimeOff;
use App\Services\Cabinet\ScheduleService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DoctorScheduleEditScreen extends Screen
{
    public ?DoctorSchedule $schedule = null;

    public function __construct(private readonly ScheduleService $service) {}

    public function query(DoctorSchedule $schedule): iterable
    {
        $doctorId = $schedule->exists ? $schedule->doctor_id : (int) request()->query('doctor_id', 0);

        return [
            'schedule' => $schedule,
            'timeOffs' => DoctorTimeOff::query()
                ->when($doctorId > 0, fn ($q) => $q->where('doctor_id', $doctorId))
                ->where('end_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->take(25)->get(),
            'timeOffDoctorId' => $doctorId > 0 ? $doctorId : $schedule->doctor_id,
        ];
    }

    public function name(): ?string
    {
        return $this->schedule?->exists ? __('schedules.edit_title') : __('schedules.create_title');
    }

    public function description(): ?string
    {
        return __('schedules.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_doctor_schedule'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('schedules.save'))->method('save')->type(Color::PRIMARY)->icon('bs.check-circle'),
            ModalToggle::make(__('schedules.add_time_off'))->modal('timeOffModal')
                ->method('addTimeOff')->icon('bs.calendar-x'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('schedule.doctor_id')->title(__('schedules.doctor'))
                    ->fromModel(Doctor::class, 'last_name')->required()->empty(),
                Select::make('schedule.clinic_id')->title(__('schedules.clinic'))
                    ->fromModel(Clinic::class, 'name')->empty(__('schedules.all_clinics')),
                Select::make('schedule.available_day')->title(__('schedules.weekday'))
                    ->options([
                        1 => __('schedules.day_1'),
                        2 => __('schedules.day_2'),
                        3 => __('schedules.day_3'),
                        4 => __('schedules.day_4'),
                        5 => __('schedules.day_5'),
                        6 => __('schedules.day_6'),
                        0 => __('schedules.day_0'),
                    ])->required(),
                Input::make('schedule.from')->type('time')->title(__('schedules.from'))->required(),
                Input::make('schedule.to')->type('time')->title(__('schedules.to'))->required(),
            ]),

            Layout::modal('timeOffModal', Layout::rows([
                Select::make('timeoff.doctor_id')->title(__('schedules.doctor'))
                    ->fromModel(Doctor::class, 'last_name')->required()->empty(),
                Select::make('timeoff.type')->title(__('schedules.time_off_type'))
                    ->options([
                        'vacation' => __('schedules.type_vacation'),
                        'sick' => __('schedules.type_sick'),
                        'conference' => __('schedules.type_conference'),
                        'personal' => __('schedules.type_personal'),
                        'other' => __('schedules.type_other'),
                    ]),
                DateTimer::make('timeoff.start_date')->title(__('schedules.time_off_start'))->format('Y-m-d')->required(),
                DateTimer::make('timeoff.end_date')->title(__('schedules.time_off_end'))->format('Y-m-d')->required(),
                TextArea::make('timeoff.reason')->title(__('schedules.time_off_reason'))->rows(2),
            ]))->title(__('schedules.add_time_off')),

            Layout::block([
                Layout::table('timeOffs', [
                    TD::make('type', __('schedules.time_off_type'))->render(fn (DoctorTimeOff $t) => e($t->type ?? '—')),
                    TD::make('start_date', __('schedules.time_off_start'))
                        ->render(fn (DoctorTimeOff $t) => e($t->start_date?->format('Y-m-d') ?? '—')),
                    TD::make('end_date', __('schedules.time_off_end'))
                        ->render(fn (DoctorTimeOff $t) => e($t->end_date?->format('Y-m-d') ?? '—')),
                    TD::make('reason', __('schedules.time_off_reason'))->render(fn (DoctorTimeOff $t) => e($t->reason ?? '—')),
                    TD::make(__('schedules.actions'))->align(TD::ALIGN_RIGHT)
                        ->render(fn (DoctorTimeOff $t) => Button::make(__('schedules.delete'))
                            ->confirm(__('schedules.time_off_delete_confirm'))
                            ->method('deleteTimeOff', ['id' => $t->getKey()])),
                ]),
            ])->title(__('schedules.time_off_title')),
        ];
    }

    public function save(Request $request, DoctorSchedule $schedule): mixed
    {
        $data = $request->validate([
            'schedule.doctor_id' => 'required|exists:doctors,id',
            'schedule.clinic_id' => 'nullable|exists:clinics,id',
            'schedule.available_day' => 'required|integer|min:0|max:6',
            'schedule.from' => 'required',
            'schedule.to' => 'required|after:schedule.from',
        ])['schedule'];

        try {
            $saved = $schedule->exists
                ? $this->service->update($schedule, $data)
                : $this->service->create($data);
        } catch (InvalidArgumentException $e) {
            Toast::error(__('schedules.overlap_error'));

            return back()->withInput();
        }

        Toast::success(__('schedules.saved'));

        return redirect()->route('platform.schedules.edit', $saved);
    }

    public function addTimeOff(Request $request): void
    {
        $data = $request->validate([
            'timeoff.doctor_id' => 'required|exists:doctors,id',
            'timeoff.type' => 'nullable|string|max:50',
            'timeoff.start_date' => 'required|date',
            'timeoff.end_date' => 'required|date|after_or_equal:timeoff.start_date',
            'timeoff.reason' => 'nullable|string',
        ])['timeoff'];

        $this->service->addTimeOff([
            'doctor_id' => $data['doctor_id'],
            'type' => $data['type'] ?? 'vacation',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
        ]);

        Toast::success(__('schedules.time_off_added'));
    }

    public function deleteTimeOff(Request $request): void
    {
        $timeOff = DoctorTimeOff::query()->whereKey($request->input('id'))->firstOrFail();
        $this->service->deleteTimeOff($timeOff);
        Toast::success(__('schedules.time_off_deleted'));
    }
}
