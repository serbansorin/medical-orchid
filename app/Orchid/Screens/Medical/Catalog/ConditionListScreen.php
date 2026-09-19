<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Condition;
use App\Orchid\Layouts\Medical\ConditionListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class ConditionListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = Condition::query()->withCount('symptoms')
            ->when(isset($filter['name']) && $filter['name'] !== '',
                fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
            ->when(isset($filter['icd_code']) && $filter['icd_code'] !== '',
                fn ($q) => $q->where('icd_code', 'ilike', '%'.$filter['icd_code'].'%'))
            ->orderBy('name');

        return ['conditions' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('catalogs.conditions_title');
    }

    public function description(): ?string
    {
        return __('catalogs.conditions_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_condition'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('catalogs.condition_create'))
                ->icon('bs.plus-circle')
                ->route('platform.catalogs.conditions.create'),
        ];
    }

    public function layout(): iterable
    {
        return [ConditionListLayout::class];
    }
}
