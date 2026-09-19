<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Organizations;

use App\Models\Organization;
use App\Orchid\Layouts\Organize\Organizations\OrganizationListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class OrganizationListScreen extends Screen
{
    public function query(): iterable
    {
        // The ported Organization model has no Filterable trait, so the
        // ?filter[]/?sort= params rendered by the table are applied manually.
        $filter = (array) request()->get('filter', []);
        $sort = (string) request()->get('sort', 'name');

        $allowedSorts = ['name', '-name', 'created_at', '-created_at', 'type', '-type'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query = Organization::withCount(['clinics', 'pharmacies'])
            ->when(isset($filter['name']) && $filter['name'] !== '', fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
            ->when(isset($filter['type']) && $filter['type'] !== '', fn ($q) => $q->where('type', $filter['type']))
            ->when(isset($filter['is_active']) && $filter['is_active'] !== '', fn ($q) => $q->where('is_active', (bool) $filter['is_active']))
            ->orderBy($column, $direction);

        return [
            'organizations' => $query->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return __('organizations.list_title');
    }

    public function description(): ?string
    {
        return __('organizations.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_organization', 'view_organization'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('organizations.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.organizations.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            OrganizationListLayout::class,
        ];
    }
}
