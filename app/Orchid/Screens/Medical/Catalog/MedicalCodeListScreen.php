<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\MedicalCode;
use App\Orchid\Layouts\Medical\MedicalCodeListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class MedicalCodeListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = MedicalCode::query()
            ->when(isset($filter['code']) && $filter['code'] !== '',
                fn ($q) => $q->where('code', 'ilike', '%'.$filter['code'].'%'))
            ->when(isset($filter['code_system']) && $filter['code_system'] !== '',
                fn ($q) => $q->where('code_system', 'ilike', '%'.$filter['code_system'].'%'))
            ->orderBy('code');

        return ['codes' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('catalogs.codes_title');
    }

    public function description(): ?string
    {
        return __('catalogs.codes_description');
    }

    public function permission(): ?iterable
    {
        // No dedicated view_any_medical_code slug; catalog read gate.
        return ['view_any_condition'];
    }

    public function commandBar(): iterable
    {
        $isSuperAdmin = (bool) request()->user()?->inRole('super_admin');

        return [
            Link::make(__('catalogs.code_create'))
                ->icon('bs.plus-circle')
                ->route('platform.catalogs.codes.create')
                ->canSee($isSuperAdmin),
        ];
    }

    public function layout(): iterable
    {
        return [MedicalCodeListLayout::class];
    }
}
