<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Clinics;

use App\Models\Clinic;
use App\Models\ClinicDoctorAssignment;
use App\Orchid\Layouts\Organize\Clinics\ClinicListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class ClinicListScreen extends Screen
{
    public function query(): iterable
    {
        // The ported Clinic model has no Filterable trait, so ?filter[]/?sort=
        // params rendered by the table are applied manually here.
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', 'name');

        $allowedSorts = ['name', '-name', 'code', '-code', 'created_at', '-created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query = Clinic::with(['organization'])
            ->withCount(['patients'])
            ->select('clinics.*')
            ->selectSub(
                ClinicDoctorAssignment::selectRaw('count(*)')->whereColumn('clinic_id', 'clinics.id'),
                'doctors_count'
            )
            ->when(isset($filter['name']) && $filter['name'] !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('name', 'ilike', '%'.$filter['name'].'%')->orWhere('code', 'ilike', '%'.$filter['name'].'%')
            ))
            ->when(isset($filter['code']) && $filter['code'] !== '', fn ($q) => $q->where('code', 'ilike', '%'.$filter['code'].'%'))
            ->when(isset($filter['type']) && $filter['type'] !== '', fn ($q) => $q->where('type', $filter['type']))
            ->when(isset($filter['is_active']) && $filter['is_active'] !== '', fn ($q) => $q->where('is_active', (bool) $filter['is_active']))
            ->orderBy($column, $direction);

        return [
            'clinics' => $query->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('clinics.list_title');
    }

    public function description(): ?string
    {
        return __('clinics.list_description');
    }

    public function permission(): ?iterable
    {
        return ['access_admin_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('clinics.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.clinics.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            ClinicListLayout::class,
        ];
    }
}
