<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Diagnostic;

use App\Models\PatientDiagnostic;
use App\Orchid\Layouts\Medical\PatientDiagnosticListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class PatientDiagnosticListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', '-id');

        $allowedSorts = ['id', '-id', 'start_date', '-start_date'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = '-id';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        if ($column === 'id') {
            $column = 'patient_diagnostics.id';
        }

        $query = PatientDiagnostic::with(['patient', 'doctor'])->withCount('conditions')
            ->when(isset($filter['title']) && $filter['title'] !== '',
                fn ($q) => $q->where('title', 'ilike', '%'.$filter['title'].'%'))
            ->when(isset($filter['status']) && $filter['status'] !== '',
                fn ($q) => $q->where('status', $filter['status']))
            ->orderBy($column, $direction);

        if (isset($filter['patient']) && $filter['patient'] !== '') {
            $term = '%'.$filter['patient'].'%';
            $query->whereHas('patient', fn ($q) => $q->where('first_name', 'ilike', $term)->orWhere('last_name', 'ilike', $term));
        }

        return ['diagnostics' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('diagnostics.list_title');
    }

    public function description(): ?string
    {
        return __('diagnostics.list_description');
    }

    public function permission(): ?iterable
    {
        // No dedicated view_any_patient_diagnostic slug; clinical read gate.
        return ['view_any_medical_record'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('diagnostics.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.diagnostics.create'),
        ];
    }

    public function layout(): iterable
    {
        return [PatientDiagnosticListLayout::class];
    }
}
