---
name: orchid-access
description: "Manage Orchid permissions, roles, and access control: register permission groups, check hasAccess/inRole, gate screens/menus/routes, assign roles, configure guards. Activate when working with permissions, roles, access checks, menu gating, or auth guards. Do not use for screen layout, fields, tables, or actions."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: permission, role, hasAccess, inRole, guard, menu permission, orchid:admin, access middleware
  role: specialist
  scope: laravel
  related-skills: orchid-screens, orchid-actions, orchid-models
---

# Orchid Access Control

Permissions and roles gate everything in an Orchid 14.x panel (Laravel 10–13):
screens, menus, routes, and individual buttons or fields. Check access by
**permission slug**, never by role name. Roles are just named bundles of
permission flags assigned to users.

## Version notes

- `Orchid\Platform\ItemPermission` is **deprecated**. Use
  `Orchid\Access\PermissionGroup::make($name)->add($slug, $description)` instead.
  The old `ItemPermission::group()->addPermission()` still works as a
  backward-compatible alias but is marked deprecated in source.
- `OrchidServiceProvider::registerPermissions()` is **deprecated** ("use config
  instead"). Override `permissions()` in your `PlatformProvider`.
- `User::getRoles()` and `Role::getUsers()` appear in older docs but **do not
  exist** in current source. Use the `roles()` / `users()` relations instead.
- Docs reference `config/platform.php`; current source publishes
  `config/orchid.php`. **Source wins**: use `config('orchid.guard')`.
- `Orchid\Access\Impersonation` handles user switching (`loginAs`/`logout`).
  Profile-screen logout/quit-impersonation buttons must be defined explicitly by
  the developer (see upgrade guide example with `Impersonation::isSwitch()`).

## Permission system overview

Three tables, all prefixed per your install (default names below):

| Table | Columns | Purpose |
|---|---|---|
| `users` | `permissions` (jsonb, nullable) | Direct per-user permission flags |
| `roles` | `id` (uuid PK), `name` (unique), `permissions` (jsonb, nullable) | Named bundles of flags |
| `role_users` | `user_id`, `role_id` (composite PK, cascading FKs) | User-to-role assignments |

Verified in `database/migrations/2015_*.php`. A user's **effective** permissions
are the union of their own `permissions` JSON and every assigned role's
`permissions` JSON. Both columns are cast to `Orchid\Access\Permissions`:

| Class | Path | Key members |
|---|---|---|
| `User` | `Orchid\Platform\Models\User` | `UserAccess` trait; extend it, never use it directly |
| `Role` | `Orchid\Platform\Models\Role` | `RoleAccess` trait; `users()` relation |
| `Permissions` | `Orchid\Access\Permissions` | `allows($permit)`, `isActive($slug)`, `count()`, `isEmpty()`, `fromEncodedForm()` |
| `PermissionGroup` | `Orchid\Access\PermissionGroup` | `make($name)`, `add($slug, $description)`, `definitions()` |

`Permissions::allows()` supports `*` wildcards (`user.*` matches `user.edit`).
The `permissions` attribute is in `User::$hidden`, so it never leaks into JSON.

## Checking access in code

Methods from the `Orchid\Access\UserAccess` trait (available on any model using
it, including the default `User`):

| Method | Signature | Behavior |
|---|---|---|
| `hasAccess` | `hasAccess(string $permit, bool $cache = true): bool` | True only if the exact slug (or wildcard match) is granted via user flags or any role |
| `hasAnyAccess` | `hasAnyAccess(string\|iterable $permissions, bool $cache = true): bool` | True if any listed slug passes; empty input returns true |
| `inRole` | `inRole(Role\|int\|string $role): bool` | Accepts a model, id, or `name`; avoid for access decisions |
| `addRole` / `removeRole` / `removeRoleBySlug` / `replaceRoles` | various | Mutate the `role_users` pivot; each clears the permission cache |
| `scopeByAccess` / `scopeByAnyAccess` | query scopes | `User::byAccess('slug')->get()`; wildcards NOT supported in scopes |

```php
use Illuminate\Support\Facades\Auth;

// Preferred: check a permission slug.
if (Auth::user()->hasAccess('cabinet.patients.view')) {
    // ...
}

// Any-of check.
if ($user->hasAnyAccess(['billing.invoices.view', 'billing.invoices.edit'])) {
    // ...
}

// Wildcard check.
if ($user->hasAccess('cabinet.*')) {
    // ...
}

// Users holding a permission (for notification recipients, assignee lists).
$doctors = User::byAccess('cabinet.visits.create')->get();
$staff = User::byAnyAccess(['cabinet.patients.view', 'billing.invoices.view'])->get();
```

> Prefer `hasAccess`/`hasAnyAccess` in application logic. Use `inRole()` only
> for display concerns (labels, role badges), never to grant access.

### Gating fields, buttons, and layouts with `canSee()`

`canSee()` (from `Orchid\Screen\Concerns\CanSee`) takes a plain `bool`, so wrap
the access check yourself. This applies to any field, action, layout, or menu
item. See orchid-actions for the full action catalog.

```php
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;

Button::make('Delete invoice')
    ->method('delete')
    ->canSee(Auth::user()->hasAccess('billing.invoices.delete'));

Input::make('user.discount_percent')
    ->canSee(Auth::user()->hasAccess('billing.discounts.edit'));
```

## Defining custom permissions

Register groups in `app/Orchid/PlatformProvider.php` (extends
`Orchid\Platform\OrchidServiceProvider`) via `permissions()`. Each
`PermissionGroup` becomes one collapsible group on the user/role edit screens.

```php
namespace App\Orchid;

use Orchid\Access\PermissionGroup;
use Orchid\Platform\OrchidServiceProvider;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * @return PermissionGroup[]
     */
    public function permissions(): array
    {
        return [
            PermissionGroup::make('Cabinet')
                ->add('cabinet.patients.view', 'View patients')
                ->add('cabinet.patients.edit', 'Create and edit patients')
                ->add('cabinet.visits.create', 'Record medical visits'),

            PermissionGroup::make('Facturare')
                ->add('billing.invoices.view', 'View invoices')
                ->add('billing.invoices.edit', 'Create and edit invoices')
                ->add('billing.discounts.edit', 'Apply discounts'),
        ];
    }
}
```

Rules:

- Slug convention: `<module>.<resource>.<verb>` in lowercase.
- Keep slugs stable forever; they are stored as JSON keys in the database.
- A permission that exists in the DB but is no longer registered still grants
  access wherever `hasAccess()` checks it. Removing a slug from code does not
  revoke already-stored flags; write a data migration if you rename one.
- The deprecated equivalent (`ItemPermission::group('...')->addPermission($slug,
  $description)`) behaves identically but emits deprecation notices. Migrate any
  copied examples to `PermissionGroup::make()->add()`.

## Screen-level access

`Orchid\Screen\Screen` ships the whole flow: `permission()` declares required
slugs, `handle()` aborts via `checkAccess()`, and `unaccessed()` renders the
denial (403 by default). See orchid-screens for the rest of the lifecycle.

```php
namespace App\Orchid\Screens;

use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Screen;

class InvoiceListScreen extends Screen
{
    /**
     * Access is granted when the user holds ANY listed slug.
     * Return null (default) for no restriction beyond login.
     */
    public function permission(): ?iterable
    {
        return [
            'billing.invoices.view',
        ];
    }

    /**
     * Optional: replace the default 403 response.
     */
    public static function unaccessed(): RedirectResponse
    {
        return redirect()->route('platform.main');
    }

    // query(), commandBar(), layout() ...
}
```

Verified behavior in `src/Screen/Screen.php`:

- `permission()` wraps a `$permission` property or your override with
  `Arr::wrap()`; multiple slugs are OR-ed via `hasAnyAccess()`.
- `checkAccess()` returns `true` for guests (`$request->user() === null`);
  authentication itself is enforced by route middleware, not the screen.
- To deny with a redirect or a payment upsell, override `unaccessed()`.

## Route middleware

`Orchid\Platform\Http\Middleware\Access` protects any route by permission slug.
Its `handle()` defaults to `'orchid.index'` when no slug is given, redirects
guests to the login route, and aborts with `Screen::unaccessed()` (403) for
signed-in users lacking the slug.

Register the alias once (Laravel 10 style shown; on Laravel 11+ use the
`withMiddleware` callback in `bootstrap/app.php` instead):

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    // ...
    'access' => \Orchid\Platform\Http\Middleware\Access::class,
];
```

```php
use Illuminate\Support\Facades\Route;

Route::screen('/invoices', InvoiceListScreen::class)
    ->middleware('access:billing.invoices.view');

Route::middleware(['access:billing.invoices.view'])->group(function () {
    Route::screen('/invoices', InvoiceListScreen::class);
    Route::screen('/invoice/{invoice}', InvoiceEditScreen::class)
        ->middleware('access:billing.invoices.edit');
});
```

The middleware resolves its guard via `config('orchid.guard')`, so custom
guards apply automatically.

## Menu gating

`Orchid\Screen\Actions\Menu::permission()` hides the item unless the current
user holds any of the given slugs (evaluated eagerly against `Auth::user()`).
Combine with `canSee()` for non-permission conditions. Items with no
`permission()` call render for every authenticated user.

```php
use Orchid\Screen\Actions\Menu;

public function menu(): array
{
    return [
        Menu::make('Cabinet')
            ->icon('bs.heart-pulse')
            ->route('platform.cabinet.patients')
            ->permission('cabinet.patients.view'),

        Menu::make('Facturare')
            ->icon('bs.receipt')
            ->route('platform.billing.invoices')
            ->permission('billing.invoices.view'),

        // Multiple slugs are OR-ed.
        Menu::make('Administrare')
            ->icon('bs.gear')
            ->route('platform.systems.users')
            ->permission(['platform.systems.users', 'platform.systems.roles']),

        // Non-permission condition on top of (or instead of) permission().
        Menu::make('Rapoarte')
            ->icon('bs.bar-chart')
            ->route('platform.reports')
            ->permission('reports.view')
            ->canSee(config('features.reports_enabled')),
    ];
}
```

Nested menus: permission is per item, not inherited. Gate every child entry
explicitly, otherwise a child stays visible under a hidden parent's slug or
vice versa.

## Assigning roles to users

The stock admin UI (stubs in `stubs/app/Orchid/`) manages roles with a
multi-select plus a direct-permissions matrix:

```php
// stubs/app/Orchid/Layouts/User/UserRoleLayout.php
use Orchid\Platform\Models\Role;
use Orchid\Screen\Fields\Select;

Select::make('user.roles.')
    ->fromModel(Role::class, 'name')
    ->multiple()
    ->title(__('Name role'))
    ->help('Specify which roles this account should belong to'),
```

```php
// stubs/app/Orchid/Screens/User/UserEditScreen.php — save()
$user
    ->fill($request->collect('user')->except(['password', 'permissions', 'roles'])->toArray())
    ->forceFill(['permissions' => Permissions::fromEncodedForm($request->input('permissions'))])
    ->save();

$user->replaceRoles($request->input('user.roles'));
```

Programmatic equivalents:

```php
$user->addRole($role);              // attach one role (accepts a Role model)
$user->removeRole($role);           // detach one role
$user->removeRoleBySlug('doctor');  // detach by role name
$user->replaceRoles([$roleId1, $roleId2]); // sync full set (what the admin UI uses)
```

### The `orchid:admin` command

`Orchid\Platform\Commands\AdminCommand` (`php artisan orchid:admin`, signature
`orchid:admin {name?} {email?} {password?} {--id=}`):

- Without `--id`: calls `createAdmin($name, $email, $password)` on the
  configured user model. The base implementation refuses duplicate emails and
  grants `Orchid::allowedPermissions()` (every registered slug).
- With `--id=`: force-fills the existing user's `permissions` with
  `Orchid::allowedPermissions()`. It does **not** touch roles.

To support extra required user columns, point Orchid at your model and override
`createAdmin()`:

```php
use Orchid\Support\Facades\Dashboard;

// In a service provider boot():
Dashboard::useModel(
    \Orchid\Platform\Models\User::class,
    \App\Models\User::class
);
```

```php
// In App\Models\User:
use Illuminate\Support\Facades\Hash;
use Orchid\Support\Facades\Dashboard;

public static function createAdmin(string $name, string $email, string $password): void
{
    throw_if(static::where('email', $email)->exists(), 'User already exists');

    static::create([
        'name'        => $name,
        'email'       => $email,
        'password'    => Hash::make($password),
        'permissions' => Dashboard::allowedPermissions(),
    ]);
}
```

## Authentication and guards

Orchid reuses Laravel's auth system; it only picks **which guard** protects the
panel. Verified keys in `config/orchid.php` (not `config/platform.php`, which
older docs cite):

```php
// config/orchid.php
'guard' => env('AUTH_GUARD', 'web'),
'auth'  => true,
```

| Task | How |
|---|---|
| Use a dedicated guard (e.g. `admins`, `sellers`) | Set `'guard' => 'admins'`; the value must exist in `config/auth.php`. Add `auth:admins` to the panel middleware list so sessions resolve against the right provider |
| Custom user model or table | Register it with `Dashboard::useModel(Orchid\Platform\Models\User::class, App\Models\User::class)`; the model must use the `Orchid\Access\UserAccess` trait for `hasAccess()` to exist |
| Fully custom login (OTP, phone, 2FA) | Set `'auth' => false` to disable Orchid's built-in login routes, then implement your own controllers per the Laravel auth docs. Consider the Fortify-based theme for password recovery/registration/2FA instead of hand-rolling |
| Login flow implication | The `Access` middleware calls `$auth->shouldUse(config('orchid.guard'))` in its constructor, so every `access:`-protected route authenticates against the configured guard automatically |

## Blade checks

Define a custom conditional once (e.g. in `AppServiceProvider::boot()`) for
permission-gated fragments in classic Blade views:

```php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::if('hasAccess', function (string $value): bool {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        return $user->hasAccess($value);
    });
}
```

```blade
@hasAccess('billing.invoices.edit')
    <a href="{{ route('platform.billing.invoice.edit', $invoice) }}">Edit</a>
@endhasAccess
```

## This project (medical-orchid)

- Roles are fixed: `super-admin`, `org-admin`, `doctor`, `pharmacist`,
  `receptionist`. Model them as `roles` rows (UUID PKs); the slug lives in the
  `name` column, matched by `inRole('doctor')`.
- Receptionist sees Cabinet + Facturare menus only: gate every other menu item
  with `->permission(...)` and mirror the same slugs in each screen's
  `permission()` so deep links 403 as well.
- Pick the landing route by role after login (e.g. pharmacist to the pharmacy
  dashboard, receptionist to Cabinet); implement as a small redirect controller
  or route closure reading `inRole()`, not as permission slugs.
- `super-admin` bypass: grant it `Orchid::allowedPermissions()` (as
  `orchid:admin` does) rather than hard-coding `inRole('super-admin')` checks
  in screens. New slugs then work with zero code changes.
- Scope every list query by role (`organization_id`, assigned clinics, assigned
  pharmacy); see `openspec/changes/ems-orchid-rebuild/design.md` for the exact
  constraint map. Permissions decide *whether* a screen opens; scopes decide
  *which rows* it shows.
- Legacy permission rows are mirrored read-only: never write to legacy auth
  tables (additive-only migrations). Sync display values from the legacy source
  on read.
- User-facing denials and role labels need `en` + `ro` strings; keep
  `permission()` slugs themselves locale-free ASCII.

## Anti-patterns

- Checking `inRole('doctor')` to grant access. Roles get renamed and combined;
  check `hasAccess('cabinet.visits.create')` instead.
- Importing Spatie Laravel-Permission or Filament Shield. The legacy app used
  Shield; this rebuild uses only Orchid's `roles` / `role_users` / JSON-permission
  system. Do not add either package.
- Calling `getRoles()` / `getUsers()`. They do not exist in Orchid 14 source;
  use `$user->roles`, `$user->roles()->...`, or `$role->users`.
- Using `ItemPermission::group()->addPermission()` in new code. It is
  deprecated; write `PermissionGroup::make()->add()`.
- Overriding `registerPermissions()` in `PlatformProvider`. Deprecated; override
  `permissions()`.
- Reading `config('platform.guard')` or editing `config/platform.php`. The file
  is `config/orchid.php` (`orchid.guard`, `orchid.auth`).
- Passing a slug string to `canSee()` (`->canSee('slug')` is wrong, it takes
  `bool`). For menus, `->permission('slug')` takes the slug.
- Gating only the menu item but leaving the screen's `permission()` open (or
  vice versa). Always set both; menus hide navigation, screens enforce access.
- Expecting `User::byAccess('cabinet.*')` to expand wildcards. Scopes match
  literal JSON keys only; use `byAnyAccess()` with explicit slugs.

## Reference

- Docs: `https://orchid.software/en/docs/permissions` (canonical; the old
  `access` page redirects here), `https://orchid.software/en/docs/authentication`,
  `https://orchid.software/en/docs/menu`
- Source (`vendor/orchid/platform/...`):
  `src/Access/UserAccess.php`, `src/Access/RoleAccess.php`,
  `src/Access/StatusAccess.php`, `src/Access/Permissions.php`,
  `src/Access/PermissionGroup.php`, `src/Platform/ItemPermission.php`
  (deprecated alias), `src/Screen/Screen.php` (`permission`, `checkAccess`,
  `unaccessed`), `src/Screen/Actions/Menu.php` (`permission`, `isSee`),
  `src/Screen/Concerns/CanSee.php`, `src/Platform/Http/Middleware/Access.php`,
  `src/Platform/OrchidServiceProvider.php` (`permissions()`),
  `src/Platform/Configuration/ManagesPermissions.php`,
  `src/Platform/Commands/AdminCommand.php`, `src/Platform/Models/User.php`,
  `src/Platform/Models/Role.php`, `config/orchid.php`,
  `database/migrations/2015_*_create_orchid_*_table.php`,
  `stubs/app/Orchid/Screens/User/UserEditScreen.php`,
  `stubs/app/Orchid/Layouts/User/UserRoleLayout.php`
- Sibling skills: orchid-screens (screen lifecycle, `permission()` hosting
  screen), orchid-actions (full `canSee()` action catalog).
