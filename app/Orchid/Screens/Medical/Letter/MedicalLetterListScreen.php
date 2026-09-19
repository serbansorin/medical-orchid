<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Letter;

use App\Models\MedicalLetter;
use App\Orchid\Layouts\Medical\MedicalLetterListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class MedicalLetterListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', '-id');

        $allowedSorts = ['id', '-id', 'letter_date', '-letter_date'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = '-id';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');
        if ($column === 'id') {
            $column = 'medical_letters.id';
        }

        $query = MedicalLetter::with(['patient', 'doctor', 'template'])
            ->when(isset($filter['letter_type']) && $filter['letter_type'] !== '',
                fn ($q) => $q->where('letter_type', $filter['letter_type']))
            ->orderBy($column, $direction);

        if (isset($filter['patient']) && $filter['patient'] !== '') {
            $term = '%'.$filter['patient'].'%';
            $query->whereHas('patient', fn ($q) => $q->where('first_name', 'ilike', $term)->orWhere('last_name', 'ilike', $term));
        }

        return ['letters' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('letters.list_title');
    }

    public function description(): ?string
    {
        return __('letters.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_letter'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('letters.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.letters.create'),
        ];
    }

    public function layout(): iterable
    {
        return [MedicalLetterListLayout::class];
    }
}
