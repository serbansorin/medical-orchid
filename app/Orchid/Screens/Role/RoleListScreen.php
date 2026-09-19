<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Role;

use App\Models\Role;
use App\Models\RoleHasPermission;
use App\Orchid\Layouts\Role\RoleListLayout;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layout;
use Orchid\Screen\Screen;

class RoleListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * The legacy `roles` table has no slug/permissions columns, so the base
     * Filterable map is bypassed: the name search is applied manually.
     *
     * @return array
     */
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        return [
            'roles' => Role::withCount(['users'])
                ->select('roles.*')
                ->selectSub(
                    RoleHasPermission::selectRaw('count(*)')->whereColumn('role_id', 'roles.id'),
                    'permissions_count'
                )
                ->when(isset($filter['name']) && $filter['name'] !== '', fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
                ->orderByDesc('id')
                ->paginate(25),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return __('roles.list_title');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('roles.list_description');
    }

    public function permission(): ?iterable
    {
        // 'platform.systems.roles' matches nothing in the shared legacy
        // permissions table, so the legacy panel gate is used instead.
        return [
            'access_admin_panel',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->href(route('platform.systems.roles.create')),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|Layout[]
     */
    public function layout(): iterable
    {
        return [
            RoleListLayout::class,
        ];
    }
}
