<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Prescription;

use App\Models\Prescription;
use App\Orchid\Layouts\Medical\PrescriptionListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class PrescriptionListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', '-id');

        $allowedSorts = ['id', '-id', 'issue_date', '-issue_date', 'status', '-status'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = '-id';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query = Prescription::with(['patient', 'doctor'])
            ->when(isset($filter['prescription_number']) && $filter['prescription_number'] !== '',
                fn ($q) => $q->where('prescription_number', 'ilike', '%'.$filter['prescription_number'].'%'))
            ->when(isset($filter['status']) && $filter['status'] !== '',
                fn ($q) => $q->where('status', $filter['status']))
            ->orderBy($column, $direction);

        if (isset($filter['patient']) && $filter['patient'] !== '') {
            $term = '%'.$filter['patient'].'%';
            $query->whereHas('patient', fn ($q) => $q->where('first_name', 'ilike', $term)->orWhere('last_name', 'ilike', $term));
        }

        return ['prescriptions' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('prescriptions.list_title');
    }

    public function description(): ?string
    {
        return __('prescriptions.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_prescription'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('prescriptions.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.prescriptions.create'),
        ];
    }

    public function layout(): iterable
    {
        return [PrescriptionListLayout::class];
    }
}
