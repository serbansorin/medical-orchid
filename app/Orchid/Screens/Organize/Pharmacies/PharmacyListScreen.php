<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Pharmacies;

use App\Models\Pharmacy;
use App\Orchid\Layouts\Organize\Pharmacies\PharmacyListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class PharmacyListScreen extends Screen
{
    public function query(): iterable
    {
        // The ported Pharmacy model has no Filterable trait, so ?filter[]/?sort=
        // params rendered by the table are applied manually here.
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', 'name');

        $allowedSorts = ['name', '-name', 'created_at', '-created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query = Pharmacy::with(['organization'])
            ->withCount(['pharmacy_staffs as staff_count'])
            ->when(isset($filter['name']) && $filter['name'] !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'ilike', '%'.$filter['name'].'%')->orWhere('slug', 'ilike', '%'.$filter['name'].'%')
            ))
            ->when(isset($filter['is_verified']) && $filter['is_verified'] !== '', fn ($q) => $q->where('is_verified', (bool) $filter['is_verified']))
            ->orderBy($column, $direction);

        return [
            'pharmacies' => $query->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('pharmacies.list_title');
    }

    public function description(): ?string
    {
        return __('pharmacies.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_pharmacy', 'view_pharmacy'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('pharmacies.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.pharmacies.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            PharmacyListLayout::class,
        ];
    }
}
