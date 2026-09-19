<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Role;

use App\Models\Role;
use App\Orchid\Layouts\Role\RoleEditLayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class RoleEditScreen extends Screen
{
    /**
     * @var Role
     */
    public $role;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Role $role): iterable
    {
        return [
            'role' => $role,
            // Read-only grant list from the legacy pivot (owned by legacy app).
            'rolePermissions' => $role->exists ? $role->permissionNames()->all() : [],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->role->exists ? __('roles.edit_title') : __('roles.create_title');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('roles.list_description');
    }

    /**
     * The permissions required to access this screen.
     */
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
            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),

            Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->method('remove')
                ->canSee($this->role->exists),
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::block([
                RoleEditLayout::class,
            ])
                ->title(__('roles.form_title'))
                ->description(__('roles.form_description')),

            Layout::block(Layout::rows([
                Label::make('rolePermissionsLabel')
                    ->title(__('roles.permissions_title'))
                    ->value($this->rolePermissionsText()),
            ]))
                ->title(__('roles.permissions_title'))
                ->description(__('roles.permissions_description')),
        ];
    }

    private function rolePermissionsText(): string
    {
        if ($this->role instanceof Role && $this->role->exists) {
            $names = $this->role->permissionNames()->all();

            return $names !== [] ? implode(', ', $names) : __('roles.no_permissions');
        }

        return __('roles.no_permissions');
    }

    /**
     * Only name + guard_name are writable: the legacy table has no
     * slug/permissions columns and its grants are owned by the legacy app.
     *
     * @return RedirectResponse
     */
    public function save(Request $request, Role $role)
    {
        $data = $request->validate([
            'role.name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')->ignore($role->getKey()),
            ],
            'role.guard_name' => 'required|string|max:255',
        ])['role'];

        try {
            $role->fill($data)->save();
        } catch (\Throwable $e) {
            report($e);
            Toast::error(__('roles.save_failed'));

            return redirect()->back();
        }

        Toast::info(__('roles.saved'));

        return redirect()->route('platform.systems.roles');
    }

    /**
     * @return RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(Role $role)
    {
        $role->delete();

        Toast::info(__('roles.removed'));

        return redirect()->route('platform.systems.roles');
    }
}
