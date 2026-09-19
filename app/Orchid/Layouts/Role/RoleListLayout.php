<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Role;

use App\Models\Role;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class RoleListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'roles';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('name', __('roles.name'))
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (Role $role) => Link::make(e((string) $role->name))
                    ->route('platform.systems.roles.edit', $role->id)),

            TD::make('guard_name', __('roles.guard'))
                ->render(fn (Role $role): string => e((string) ($role->guard_name ?? '—'))),

            TD::make('team_id', __('roles.team'))
                ->render(fn (Role $role): string => e((string) ($role->team_id ?? '—'))),

            TD::make('users_count', __('roles.users'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Role $role): int => (int) ($role->users_count ?? 0)),

            TD::make('permissions_count', __('roles.permissions_count'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Role $role): int => (int) ($role->permissions_count ?? 0)),

            TD::make('created_at', __('roles.created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden(),

            TD::make('updated_at', __('roles.last_edit'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT),
        ];
    }
}
