<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Symptom;
use App\Orchid\Layouts\Medical\SymptomListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class SymptomListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = Symptom::query()
            ->when(isset($filter['name']) && $filter['name'] !== '',
                fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
            ->when(isset($filter['code']) && $filter['code'] !== '',
                fn ($q) => $q->where('code', 'ilike', '%'.$filter['code'].'%'))
            ->orderBy('name');

        return ['symptoms' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('catalogs.symptoms_title');
    }

    public function description(): ?string
    {
        return __('catalogs.symptoms_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_symptom'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('catalogs.symptom_create'))
                ->icon('bs.plus-circle')
                ->route('platform.catalogs.symptoms.create'),
        ];
    }

    public function layout(): iterable
    {
        return [SymptomListLayout::class];
    }
}
