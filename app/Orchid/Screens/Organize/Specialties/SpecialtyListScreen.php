<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Specialties;

use App\Models\Specialty;
use App\Models\UserSpecialty;
use App\Orchid\Layouts\Organize\Specialties\SpecialtyListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class SpecialtyListScreen extends Screen
{
    public function query(): iterable
    {
        // The ported Specialty model has no Filterable trait, so the ?sort=
        // param rendered by the table is applied manually. Name search on a
        // jsonb column needs a locale-aware LIKE over both locales.
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', 'id');

        $query = Specialty::select('specialties.*')
            ->selectSub(
                UserSpecialty::selectRaw('count(distinct user_id)')->whereColumn('specialty_id', 'specialties.id'),
                'doctors_count'
            )
            ->when(isset($filter['name']) && $filter['name'] !== '', function ($q) use ($filter): void {
                $needle = '%'.$filter['name'].'%';
                $q->where(fn ($w) => $w
                    ->where('name->ro', 'ilike', $needle)
                    ->orWhere('name->en', 'ilike', $needle));
            })
            ->orderBy('id', $sort === '-name' ? 'desc' : 'asc');

        return [
            'specialties' => $query->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('specialties.list_title');
    }

    public function description(): ?string
    {
        return __('specialties.list_description');
    }

    public function permission(): ?iterable
    {
        return ['access_admin_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('specialties.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.specialties.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            SpecialtyListLayout::class,
        ];
    }
}
