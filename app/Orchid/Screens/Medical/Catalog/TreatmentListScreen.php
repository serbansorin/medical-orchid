<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Treatment;
use App\Orchid\Layouts\Medical\TreatmentListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class TreatmentListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = Treatment::query()
            ->when(isset($filter['name']) && $filter['name'] !== '',
                fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
            ->orderBy('name');

        return ['treatments' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('catalogs.treatments_title');
    }

    public function description(): ?string
    {
        return __('catalogs.treatments_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_treatment'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('catalogs.treatment_create'))
                ->icon('bs.plus-circle')
                ->route('platform.catalogs.treatments.create'),
        ];
    }

    public function layout(): iterable
    {
        return [TreatmentListLayout::class];
    }
}
