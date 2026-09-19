<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Record;

use App\Models\MedicalRecord;
use App\Orchid\Layouts\Medical\MedicalRecordListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class MedicalRecordListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', '-id');

        $allowedSorts = ['id', '-id', 'created_date', '-created_date'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = '-id';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        if ($column === 'id') {
            $column = 'medical_records.id';
        }

        $query = MedicalRecord::with(['patient', 'doctor'])
            ->when(isset($filter['title']) && $filter['title'] !== '',
                fn ($q) => $q->where('title', 'ilike', '%'.$filter['title'].'%'))
            ->when(isset($filter['record_type']) && $filter['record_type'] !== '',
                fn ($q) => $q->where('record_type', $filter['record_type']))
            ->orderBy($column, $direction);

        if (isset($filter['patient']) && $filter['patient'] !== '') {
            $term = '%'.$filter['patient'].'%';
            $query->whereHas('patient', fn ($q) => $q->where('first_name', 'ilike', $term)->orWhere('last_name', 'ilike', $term));
        }

        return ['records' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('records.list_title');
    }

    public function description(): ?string
    {
        return __('records.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_record'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('records.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.records.create'),
        ];
    }

    public function layout(): iterable
    {
        return [MedicalRecordListLayout::class];
    }
}
