<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Role;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class RoleEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('role.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('roles.name'))
                ->placeholder(__('roles.name'))
                ->help(__('roles.form_description')),

            // Legacy `roles` has no slug/permissions columns: the bridged
            // model exposes name as slug, and grants live in
            // role_has_permissions (owned by the legacy app, read-only here).
            Input::make('role.guard_name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('roles.guard'))
                ->placeholder('web'),
        ];
    }
}
