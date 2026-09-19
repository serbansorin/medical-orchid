<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Orchid\Layouts\Cabinet\ScheduleListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DoctorScheduleListScreen extends Screen
{
    public function query(): iterable
    {
        $query = DoctorSchedule::query()->with(['doctor', 'clinic']);

        if (($doctorId = (string) request()->query('doctor_id', '')) !== '') {
            $query->where('doctor_id', $doctorId);
        }
        if (($clinicId = (string) request()->query('clinic_id', '')) !== '') {
            $query->where('clinic_id', $clinicId);
        }
        if (($day = (string) request()->query('weekday', '')) !== '') {
            $query->where('available_day', $day);
        }

        return [
            'schedules' => $query->orderBy('available_day')->orderBy('from')->paginate(25)->withQueryString(),
        ];
    }

    public function name(): ?string
    {
        return __('schedules.list_title');
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
            Link::make(__('schedules.create_title'))
                ->route('platform.schedules.create')
                ->icon('bs.plus-circle'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('doctor_id')->title(__('schedules.filter_doctor'))
                    ->fromModel(Doctor::class, 'last_name')->empty(__('schedules.all_doctors'))
                    ->value(request()->query('doctor_id')),
                Select::make('clinic_id')->title(__('schedules.filter_clinic'))
                    ->fromModel(Clinic::class, 'name')->empty(__('schedules.all_clinics'))
                    ->value(request()->query('clinic_id')),
                Select::make('weekday')->title(__('schedules.filter_weekday'))
                    ->options([
                        0 => __('schedules.day_0'),
                        1 => __('schedules.day_1'),
                        2 => __('schedules.day_2'),
                        3 => __('schedules.day_3'),
                        4 => __('schedules.day_4'),
                        5 => __('schedules.day_5'),
                        6 => __('schedules.day_6'),
                    ])->empty(__('schedules.all_weekdays'))->value(request()->query('weekday')),
                Button::make(__('schedules.filter'))->method('applyFilter')->icon('bs.funnel'),
            ]),
            ScheduleListLayout::class,
        ];
    }

    public function applyFilter(Request $request): mixed
    {
        return redirect()->route('platform.schedules', $request->only(['doctor_id', 'clinic_id', 'weekday']));
    }

    public function delete(Request $request): void
    {
        DoctorSchedule::query()->whereKey($request->input('id'))->firstOrFail()->delete();
        Toast::success(__('schedules.deleted'));
    }
}
