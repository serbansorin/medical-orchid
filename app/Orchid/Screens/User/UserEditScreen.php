<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\Role;
use App\Models\User;
use App\Models\UserSpecialty;
use App\Models\UserWorkplace;
use App\Orchid\Layouts\Role\RolePermissionLayout;
use App\Orchid\Layouts\User\UserAccountTypeLayout;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserPasswordLayout;
use App\Orchid\Layouts\User\UserRoleLayout;
use App\Orchid\Layouts\User\UserSpecialtyLayout;
use App\Orchid\Layouts\User\UserWorkplaceTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Orchid\Access\Impersonation;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class UserEditScreen extends Screen
{
    /**
     * @var User
     */
    public $user;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(User $user): iterable
    {
        $user->load(['roles']);

        if ($user->exists) {
            // Virtual key for the specialties multi-select (user_specialties pivot).
            $user->setAttribute(
                'specialty_ids',
                UserSpecialty::where('user_id', $user->id)->pluck('specialty_id')->all()
            );
        }

        return [
            'user' => $user,
            'permission' => $user->statusOfPermissions(),
            'workplaces' => $user->exists
                ? UserWorkplace::with(['organization'])->where('user_id', $user->id)->orderByDesc('id')->paginate(10)
                : UserWorkplace::whereRaw('1 = 0')->paginate(10),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->user->exists ? __('users.edit_title') : __('users.create_title');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('users.list_description');
    }

    public function permission(): ?iterable
    {
        // 'platform.systems.users' matches nothing in the shared legacy
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
            Button::make(__('Impersonate user'))
                ->icon('bg.box-arrow-in-right')
                ->confirm(__('You can revert to your original state by logging out.'))
                ->method('loginAs')
                ->canSee($this->user->exists && $this->user->id !== \request()->user()->id),

            Button::make(__('Remove'))
                ->icon('bs.trash3')
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove')
                ->canSee($this->user->exists),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    /**
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [

            Layout::block(UserEditLayout::class)
                ->title(__('users.profile_title'))
                ->description(__('users.profile_description'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserAccountTypeLayout::class)
                ->title(__('users.role_marker'))
                ->description(__('users.list_description'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserPasswordLayout::class)
                ->title(__('users.password_title'))
                ->description(__('users.password_description'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserRoleLayout::class)
                ->title(__('users.roles_title'))
                ->description(__('users.roles_description'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserSpecialtyLayout::class)
                ->title(__('users.specialties_title'))
                ->description(__('users.specialties_description'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

            Layout::block(UserWorkplaceTable::class)
                ->title(__('users.workplaces_title'))
                ->description(__('users.workplaces_description'))
                ->canSee($this->user->exists),

            Layout::block(RolePermissionLayout::class)
                ->title(__('users.permissions_title'))
                ->description(__('users.permissions_description'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->user->exists)
                        ->method('save')
                ),

        ];
    }

    /**
     * @return RedirectResponse
     */
    public function save(User $user, Request $request)
    {
        $validated = $request->validate([
            'user.name' => 'required|string|max:255',
            'user.email' => [
                'required', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
            'user.password' => 'nullable|string|min:8',
            'user.role' => 'nullable|string|max:50',
            'user.roles' => 'nullable|array',
            'user.roles.*' => 'integer|exists:roles,id',
            'user.specialty_ids' => 'nullable|array',
            'user.specialty_ids.*' => 'integer|exists:specialties,id',
        ]);

        $input = $validated['user'];
        $actor = $request->user();

        // Role-escalation guard: only super-admins hand out privileged markers/roles.
        $privilegedMarkers = ['super_admin', 'admin', 'org_admin'];
        if (isset($input['role']) && in_array($input['role'], $privilegedMarkers, true)
            && ! $actor?->isSuperAdmin()
        ) {
            Toast::error(__('users.escalation_denied', ['role' => $input['role']]));

            return redirect()->back();
        }

        $roleIds = array_map('intval', (array) ($input['roles'] ?? []));
        if ($roleIds !== [] && ! $actor?->isSuperAdmin()) {
            $privileged = Role::whereIn('id', $roleIds)
                ->whereIn('name', ['super_admin', 'org_admin'])
                ->pluck('name')->all();
            if ($privileged !== []) {
                Toast::error(__('users.super_admin_roles_denied', ['roles' => implode(', ', $privileged)]));

                return redirect()->back();
            }
        }

        $permissions = collect($request->get('permissions'))
            ->map(fn ($value, $key) => [base64_decode($key) => $value])
            ->collapse()
            ->toArray();

        DB::transaction(function () use ($user, $input, $permissions, $roleIds): void {
            $user->when(filled($input['password'] ?? null), function (Builder $builder) use ($input) {
                $builder->getModel()->password = Hash::make($input['password']);
            });

            $user
                ->fill(collect($input)->except(['password', 'permissions', 'roles', 'role', 'specialty_ids'])->toArray())
                ->forceFill([
                    'permissions' => $permissions,
                    'role' => $input['role'] ?? $user->role,
                ])
                ->save();

            $user->replaceRoles($roleIds);

            UserSpecialty::where('user_id', $user->id)->delete();
            foreach (array_map('intval', (array) ($input['specialty_ids'] ?? [])) as $specialtyId) {
                UserSpecialty::create(['user_id' => $user->id, 'specialty_id' => $specialtyId]);
            }
        });

        Toast::info(__('users.saved'));

        return redirect()->route('platform.systems.users');
    }

    /**
     * @return RedirectResponse
     *
     * @throws \Exception
     */
    public function remove(User $user)
    {
        $user->delete();

        Toast::info(__('users.removed'));

        return redirect()->route('platform.systems.users');
    }

    /**
     * @return RedirectResponse
     */
    public function loginAs(User $user)
    {
        Impersonation::loginAs($user);

        Toast::info(__('You are now impersonating this user'));

        return redirect()->route(config('platform.index'));
    }
}
