<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Orchid\Layouts\Cabinet\AppointmentListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class AppointmentListScreen extends Screen
{
    public function query(): iterable
    {
        $query = Appointment::query()->with(['patient', 'doctor', 'clinic']);

        if (($from = (string) request()->query('from', '')) !== '') {
            $query->whereDate('appointment_date', '>=', $from);
        }
        if (($to = (string) request()->query('to', '')) !== '') {
            $query->whereDate('appointment_date', '<=', $to);
        }
        if (($status = (string) request()->query('status', '')) !== '') {
            $query->where('status', $status);
        }
        if (($doctorId = (string) request()->query('doctor_id', '')) !== '') {
            $query->where('doctor_id', $doctorId);
        }

        return [
            'appointments' => $query->orderBy('appointment_date')->orderBy('appointment_time')->paginate(25)->withQueryString(),
            'doctors' => Doctor::query()->orderBy('last_name')->take(100)->get(),
        ];
    }

    public function name(): ?string
    {
        return __('appointments.list_title');
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
        return [
            Link::make(__('appointments.create_title'))
                ->route('platform.appointments.create')
                ->icon('bs.plus-circle'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                DateTimer::make('from')->title(__('appointments.filter_date_from'))->format('Y-m-d')
                    ->value(request()->query('from')),
                DateTimer::make('to')->title(__('appointments.filter_date_to'))->format('Y-m-d')
                    ->value(request()->query('to')),
                Select::make('status')->title(__('appointments.filter_status'))
                    ->options([
                        'scheduled' => __('appointments.status_scheduled'),
                        'confirmed' => __('appointments.status_confirmed'),
                        'completed' => __('appointments.status_completed'),
                        'cancelled' => __('appointments.status_cancelled'),
                        'no-show' => __('appointments.status_no_show'),
                    ])->empty(__('appointments.all_statuses'))->value(request()->query('status')),
                Select::make('doctor_id')->title(__('appointments.filter_doctor'))
                    ->fromModel(Doctor::class, 'last_name')->empty(__('appointments.all_doctors'))
                    ->value(request()->query('doctor_id')),
                Button::make(__('appointments.filter'))->method('applyFilter')->icon('bs.funnel'),
            ]),
            AppointmentListLayout::class,
        ];
    }

    public function applyFilter(Request $request): mixed
    {
        return redirect()->route('platform.appointments', $request->only(['from', 'to', 'status', 'doctor_id']));
    }

    public function delete(Request $request): void
    {
        Appointment::query()->whereKey($request->input('id'))->firstOrFail()->delete();
        Toast::success(__('appointments.deleted'));
    }
}
