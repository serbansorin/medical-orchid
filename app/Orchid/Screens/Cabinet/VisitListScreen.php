<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Doctor;
use App\Models\MedicalVisit;
use App\Orchid\Layouts\Cabinet\VisitListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class VisitListScreen extends Screen
{
    public function query(): iterable
    {
        $query = MedicalVisit::query()->with(['patient', 'doctor', 'clinic']);

        if (($from = (string) request()->query('from', '')) !== '') {
            $query->whereDate('visit_date', '>=', $from);
        }
        if (($to = (string) request()->query('to', '')) !== '') {
            $query->whereDate('visit_date', '<=', $to);
        }
        if (($status = (string) request()->query('status', '')) !== '') {
            $query->where('status', $status);
        }
        if (($doctorId = (string) request()->query('doctor_id', '')) !== '') {
            $query->where('doctor_id', $doctorId);
        }

        return [
            'visits' => $query->orderByDesc('visit_date')->paginate(25)->withQueryString(),
        ];
    }

    public function name(): ?string
    {
        return __('visits.list_title');
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
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                DateTimer::make('from')->title(__('visits.filter_date_from'))->format('Y-m-d')
                    ->value(request()->query('from')),
                DateTimer::make('to')->title(__('visits.filter_date_to'))->format('Y-m-d')
                    ->value(request()->query('to')),
                Select::make('status')->title(__('visits.filter_status'))
                    ->options([
                        'scheduled' => __('visits.status_scheduled'),
                        'checked_in' => __('visits.status_checked_in'),
                        'in_progress' => __('visits.status_in_progress'),
                        'completed' => __('visits.status_completed'),
                        'no_show' => __('visits.status_no_show'),
                        'cancelled' => __('visits.status_cancelled'),
                    ])->empty(__('visits.all_statuses'))->value(request()->query('status')),
                Select::make('doctor_id')->title(__('visits.filter_doctor'))
                    ->fromModel(Doctor::class, 'last_name')->empty(__('visits.all_doctors'))
                    ->value(request()->query('doctor_id')),
                Button::make(__('visits.filter'))->method('applyFilter')->icon('bs.funnel'),
            ]),
            VisitListLayout::class,
        ];
    }

    public function applyFilter(Request $request): mixed
    {
        return redirect()->route('platform.visits', $request->only(['from', 'to', 'status', 'doctor_id']));
    }
}
