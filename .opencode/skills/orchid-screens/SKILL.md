---
name: orchid-screens
description: "Build Orchid 14 screens end to end: Screen lifecycle, query/layout/commandBar, Route::screen registration, panel config, role-gated menus, install commands, stubs, screen testing. Activate when working with screens, routes/platform.php, PlatformProvider, menus, or config/orchid.php. Do not use for field catalogs, TD columns, filters, modals, or chart layouts."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: Screen, query, layout, commandBar, Route::screen, PlatformProvider, menu, orchid.php, ScreenTesting, orchid:screen
  role: specialist
  scope: laravel
  related-skills: orchid-layouts, orchid-fields, orchid-tables, orchid-access
---

# Orchid Screens

A Screen is the Orchid equivalent of a controller-plus-view for one admin page: a single PHP class that fetches data (`query()`), declares header actions (`commandBar()`), and declares page structure (`layout()`). Every Screen renders inside one HTML `<form>`, so button clicks POST back to public methods on the same class. This skill covers Orchid 14.x on Laravel 10–13: the Screen lifecycle, route registration, panel configuration, navigation menus, install commands, stubs, and screen testing.

## Version notes

Mined from `upgrade.md`. Only items affecting screens, routes, or menus are listed.

- Public properties on a Screen are auto-filled from `query()` return keys of the same name (e.g. `query()` returns `['message' => 'Hi']` and `public $message` is set before `name()`/`layout()` run). Use this instead of re-querying in multiple methods.
- `Route::screen()` supports implicit route-model binding placeholders (e.g. `/posts/{post:slug}`). Query-string values are merged into route parameters before method resolution.
- Listener layouts changed signature: the layout's handler receives the screen state (`Repository`) as the first argument and the `Request` as the second. Screens that override listener behavior must match this order.
- `OrchidServiceProvider::menu()` is the current menu hook. `registerMenu()`, `registerMainMenu()`, and `registerProfileMenu()` are marked `@deprecated` in source — do not use them in new code.
- `registerPermissions()` and `registerSearchModels()` are marked `@deprecated`; permissions and search models now live in config. (Details belong to the orchid-access and orchid-models skills.)
- Turbo/Stimulus is the frontend layer: `frontendController()` returns the Stimulus controller name (default `'base'`). jQuery is no longer bundled — add it via `config/orchid.php` `resource.scripts` only if legacy code needs it.

## What a Screen is

A Screen separates data fetching from presentation:

- `query()` loads data from any source (Eloquent, API, service) and returns an array. Keys become available to layouts and Blade views by name.
- `layout()` returns an array of `Layout` objects (or layout class names) that render the page body.
- `commandBar()` returns an array of `Action` objects (buttons, links, modal toggles) rendered in the page header.
- Any other public method on the class is callable by POST-ing to the screen route with a `{method?}` segment. Orchid resolves the target via `getAvailableMethods()` and Laravel's container (`App::call`), so method parameters support dependency injection and route-model binding.

The base class is `Orchid\Screen\Screen` (abstract, extends the platform HTTP controller). Generated screens live in `App\Orchid\Screens`.

## Directory structure

After `php artisan orchid:install`, the app gains this tree (see `structure.md`):

```bash
app
└── Orchid
    ├── Filters        # Custom data filters (see orchid-tables)
    ├── Layouts        # Reusable page fragments (see orchid-layouts)
    ├── Presenters     # Display formatting for models (see orchid-models)
    ├── Screens        # One class per admin page (this skill)
    └── PlatformProvider.php  # Menu, permissions, bootstrap (this skill)
```

Plus `routes/platform.php` (screen routes; see the note under Route registration about the published `routes/orchid.php` stub) and `config/orchid.php` (panel settings). You may add custom directories (e.g. `app/Orchid/Fields`); Composer autoloading is the only constraint.

| Path | Purpose |
|---|---|
| `app/Orchid/Screens/` | Screen classes, namespace `App\Orchid\Screens` |
| `app/Orchid/Layouts/` | Reusable `Rows`/`Table`/listener subclasses |
| `app/Orchid/Filters/` | `Filter` subclasses for tables |
| `app/Orchid/Presenters/` | `Presenter` subclasses for models |
| `app/Orchid/PlatformProvider.php` | Extends `Orchid\Platform\OrchidServiceProvider`; `menu()` and `permissions()` live here |
| `routes/platform.php` | `Route::screen(...)` registrations, wrapped in platform middleware |
| `config/orchid.php` | Prefix, domain, middleware, guard, menu-adjacent settings |

## Install commands

Brief reference. Full walkthrough is in `installation.md`.

```bash
composer require orchid/platform
php artisan orchid:install
php artisan orchid:admin            # interactive: creates a max-permission user
php artisan orchid:admin Ada ada@example.com secret
```

- `orchid:install` publishes config, migrations, and the `App\Orchid` tree. Source: `Orchid\Platform\Commands\InstallCommand` (`signature: orchid:install`).
- `orchid:admin` signature is `orchid:admin {name?} {email?} {password?} {--id=}`. Pass all three to run non-interactively.
- After updating the package, refresh compiled assets: `php artisan orchid:publish && php artisan view:clear`. Consider a `post-update-cmd` Composer hook that runs `orchid:publish`.
- Serve and open the panel at `{APP_URL}{prefix}` (default `/admin`, e.g. `http://localhost:8000/admin`).

## The Screen class API

Base class: `Orchid\Screen\Screen`. Verified method inventory (source wins over docs):

| Method | Signature | Purpose |
|---|---|---|
| `query` | `public function query(): array` (convention; not on base, invoked via `callMethod`) | Load page data; return key => value array |
| `name` | `public function name(): ?string` | Page title in the header |
| `description` | `public function description(): ?string` | Subtitle under the header |
| `layout` | `abstract public function layout(): iterable` | Page body layouts (required) |
| `commandBar` | `public function commandBar(): iterable` (from `HasCommandBar`) | Header actions |
| `permission` | `public function permission(): ?iterable` | Permission slugs required to view; `null` = open to any authenticated user |
| `handle` | `public function handle(Request $request, ...$arguments)` | Dispatcher: GET renders `view()`, POST calls the `{method?}` segment |
| `view` | `public function view(array\|Repository $httpQueryArguments = [])` | Build the full page response |
| `build` | `public function build(Repository $repository)` | Render `layout()` against the repository |
| `asyncBuild` | `public function asyncBuild(string $method, string $slug)` | Turbo-stream response for one async method + layout fragment |
| `asyncPartialLayout` | `public function asyncPartialLayout(Listener $layout, Request $request): Response` | Turbo-stream response for a `Listener` refresh |
| `checkAccess` | `protected function checkAccess(Request $request): bool` | Returns `true` for guests; otherwise `$user->hasAnyAccess($this->permission())` |
| `formValidateMessage` | `public function formValidateMessage(): string` | Message shown when browser-side form validation blocks submit |
| `needPreventsAbandonment` | `public function needPreventsAbandonment(): bool` | Warn on navigation with unsaved changes; defaults to `config('orchid.prevents_abandonment', true)` |
| `frontendController` | `public function frontendController(): string` | Stimulus controller name; default `'base'` |
| `getAvailableMethods` | `public static function getAvailableMethods(): Collection` | Public methods callable via `{method?}`, minus base-class and `query` methods |
| `unaccessed` | `public static function unaccessed()` | Returned on denied access (HTTP 403 by default) |

State helpers (from concerns; used implicitly, rarely overridden):

| Concern | Members | Role |
|---|---|---|
| `HasCommandBar` | `commandBar()`, `buildCommandBar(Repository)` | Default empty command bar; builds actions against current data |
| `HasFillablePublicProperties` | `fillPublicProperty(Repository)`, `getPublicPropertyNames()` | Copies `query()` keys into matching public properties |
| `InteractsWithEncryptedState` | `extractState()`, `serializableState()` | Round-trips public-property state through POSTs via an encrypted `_state` payload |
| `ModelStateRetrievable` | `__serialize()`, `__unserialize()` | Serializes Eloquent models held in public properties between requests |

### Minimal screen

```php
namespace App\Orchid\Screens;

use Orchid\Screen\Screen;

class TaskScreen extends Screen
{
    public function query(): array
    {
        return [];
    }

    public function name(): ?string
    {
        return 'Simple To-Do List';
    }

    public function description(): ?string
    {
        return 'Orchid Quickstart';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [];
    }
}
```

Generate it with:

```bash
php artisan orchid:screen TaskScreen
```

The generator (`Orchid\Platform\Commands\ScreenCommand`, stub `screen.stub`) places the class in namespace `App\Orchid\Screens`.

### Querying data

Return plain values, Eloquent models, paginators, or `Repository` wrappers. Models that use the `Orchid\Screen\AsSource` trait support dot-notation access in fields and layouts.

```php
namespace App\Orchid\Screens;

use App\Models\Order;
use Orchid\Screen\Repository;
use Orchid\Screen\Screen;

class OrderScreen extends Screen
{
    public function query(): array
    {
        return [
            'order'  => Order::find(1),
            'orders' => Order::paginate(),
            'meta'   => new Repository([
                'currency' => 'RON',
            ]),
        ];
    }

    public function name(): ?string
    {
        return 'Orders';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [];
    }
}
```

Public properties with the same name as `query()` keys are auto-filled before `name()`, `commandBar()`, and `layout()` run:

```php
class IdeaScreen extends Screen
{
    public ?string $message = null;

    public function query(): array
    {
        return ['message' => 'Hello World!'];
    }

    public function name(): ?string
    {
        return $this->message;
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [];
    }
}
```

### Handling actions (method dispatch)

Every public method except base-class methods and `query()` is POST-callable. Buttons declare the target with `->method('name', $params)`; the `{method?}` route segment carries it. Request data, route parameters, and container injection are all resolved per call.

```php
use App\Models\Task;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;

class TaskScreen extends Screen
{
    public function commandBar(): iterable
    {
        return [
            Button::make('Go print')->method('print'),
        ];
    }

    public function print(Request $request): void
    {
        Toast::warning('Hello, world! This is a toast message.');
    }

    public function create(Request $request): void
    {
        $request->validate(['task.name' => 'required|max:255']);
        Task::create(['name' => $request->input('task.name')]);
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
```

Rules:

- GET requests always render the screen; a non-`view` method on GET redirects to the canonical URL.
- POST requests invoke the `{method?}` segment (defaults to `view`). Unknown methods abort with 400 in async paths.
- After a POST method returns `null`/`void`, Orchid redirects back, preserving public-property state.
- To call a method from raw Blade/JS, POST to `route('platform.screens.users', ['method' => 'hello'])` with a CSRF token.

### Async methods

Two paths exist; both return `text/vnd.turbo-stream.html` fragments, not full pages:

- `asyncBuild($method, $slug)` — call any public screen method, merge its return array over current state, and re-render the layout fragment identified by `$slug` (plus the `screen-state` fragment). Used by async selects, async loads, and `refresh-method` behaviors.
- `asyncPartialLayout(Listener $layout, Request $request)` — re-run a `Listener` layout's handler with current state and return its rebuilt HTML.

You rarely call these directly; layouts and fields trigger them. Design async-safe methods accordingly: accept state explicitly, return arrays, and avoid redirect responses (see orchid-actions for modal, toast, and listener patterns).

### Permission gating per screen

```php
class BillingScreen extends Screen
{
    public function permission(): ?iterable
    {
        return ['platform.billing'];
    }
}
```

Return `null` (the default) for no screen-level check. `checkAccess()` grants guests (`$request->user() === null` returns `true` — the platform `auth` middleware stack handles guest rejection separately) and otherwise delegates to `$user->hasAnyAccess($this->permission())`. Combine with `->permission()` / `->canSee()` on menu items so denied screens are also hidden from navigation. Full role/permission design belongs to the orchid-access skill.

## Route registration

Screens are registered in the panel routes file with the `Route::screen()` macro (defined in `FoundationServiceProvider::registerScreenMacro()`):

> The docs call this file `routes/platform.php`, but the current package publishes the stub as `routes/orchid.php` (`stubs/app/routes/orchid.php`, wired in `ConsoleServiceProvider.php:132`). Either filename works once loaded — check `RouteServiceProvider` for which file your app actually requires.

```php
use App\Orchid\Screens\IdeaEditScreen;
use App\Orchid\Screens\IdeaScreen;

Route::screen('/idea/edit', IdeaEditScreen::class)->name('platform.idea.edit');
Route::screen('/idea', IdeaScreen::class)->name('platform.idea');
```

Macro behavior (verified in source):

```php
// Equivalent expansion:
Route::match(['GET', 'HEAD', 'POST'], $url.'/{method?}', $screen)
    ->where('method', $screen::getAvailableMethods()->implode('|'));
```

Consequences:

- One registration creates both the GET page route and every POST action route. Inspect with `php artisan route:list`.
- Routes in this file inherit the middleware groups from `config/orchid.php` (`middleware.private` for authenticated routes).
- Order matters: `/idea` registered before `/idea/edit` swallows `edit` into `{method?}` and redirects to `/idea`. Always register longer (more specific) paths first.
- Route parameters participate in method injection: `Route::screen('/posts/{post:slug}', PostScreen::class)` resolves `Post $post` in `query(Post $post)` and action methods via implicit binding. `->withTrashed()` is supported on these routes.
- Breadcrumbs attach fluently:

```php
use Tabuna\Breadcrumbs\Trail;

Route::screen('task', TaskScreen::class)
    ->name('platform.task')
    ->breadcrumbs(fn (Trail $trail) => $trail->parent('platform.index')->push('Task'));
```

For plain controllers rendered inside the panel chrome, register a normal `Route::get()` in the same file and extend the `platform::dashboard` Blade layout (see `controllers.md`). Prefer Screens for anything new.

## Panel configuration

File: `config/orchid.php`. Every key below is verified against `configuration.md` and the published `config/orchid.php` source. Read values with `config('orchid.key')`; never call `env()` in app code.

> Naming note: the current package publishes `config/orchid.php`; older docs and guides may still say `config/platform.php` — they mean this same file.

| Key | Default | Effect |
|---|---|---|
| `prefix` | `'/admin'` | URL prefix for the whole panel (`/admin/login`, …). Include the leading slash. |
| `domain` | `env('PLATFORM_DOMAIN', null)` | Serve the panel from a dedicated domain (e.g. `admin.example.com`); requires web-server vhost work |
| `middleware.public` | `['web', 'cache.headers:…']` | Stack for guest routes (login) |
| `middleware.private` | `['web', 'orchid', 'cache.headers:private;must-revalidate;etag']` | Stack for authenticated routes, including all screens |
| `guard` | `env('AUTH_GUARD', 'web')` | Laravel auth guard used by the dashboard |
| `auth` | `true` | Use the built-in login screen; set `false` when delegating to Jetstream or a custom login |
| `index` | `'orchid.main'` | Route name users land on after login and when clicking the logo |
| `profile` | `'platform.profile'` | Route name opened from the user avatar menu |
| `resource.stylesheets` / `resource.scripts` | `[]` | Global CSS/JS added to every panel page (paths under `public/` or CDN URLs) |
| `vite` | `[]` | Vite entry paths included on Orchid pages |
| `template.header` / `template.footer` | `null` | Override partial branding templates (see branding docs) |
| `attachment.disk` | `env('PLATFORM_FILESYSTEM_DISK', 'public')` | Filesystem disk for uploads |
| `attachment.generator` | `\Orchid\Attachment\Engines\Generator::class` | Unique-filename generator for uploads |
| `icons` | `['bs' => BootstrapIconsPath]` | SVG icon sets; reference as `->icon('bs.heart')` |
| `notifications.enabled` / `notifications.interval` | `true` / `60` | Nav bell polling toggle and interval in seconds |
| `search` | `[]` | Scout-searchable model classes shown in sidebar search |
| `turbo.cache` / `turbo.prefetch` / `turbo.refresh-method` / `turbo.refresh-scroll` | `true` / `true` / `'replace'` / `'preserve'` | Hotwire navigation tuning; async-heavy screens often prefer `refresh-method: morph` |
| `workspace` | `'platform::workspace.compact'` | Content wrapper; alternative `'platform::workspace.full'` |
| `fallback` | `true` | Render Orchid's 404 for unmatched panel URLs; set `false` when mounting custom routes under the same prefix |
| `prevents_abandonment` | `true` | Global unsaved-changes guard; per-screen override via `needPreventsAbandonment()` |
| `provider` | `\App\Orchid\PlatformProvider::class` | Service provider that boots menu/permissions; change only if you move that class |

Custom user models are wired in code, not config, via `Dashboard::useModel()` in a service provider's `boot()`.

## Navigation menu

Menus are declared in code in `PlatformProvider::menu()`. The current API is `menu(): array`; the older `registerMenu()`, `registerMainMenu()`, and `registerProfileMenu()` are deprecated in source.

```php
namespace App\Orchid;

use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            Menu::make('Patients')
                ->icon('bs.people')
                ->route('platform.patients')
                ->permission('platform.patients')
                ->sort(10),

            Menu::make('Cabinet')
                ->icon('bs.calendar')
                ->slug('cabinet')
                ->list([
                    Menu::make('Appointments')->icon('bs.clock')->route('platform.appointments'),
                    Menu::make('Billing')->icon('bs.receipt')->route('platform.billing'),
                ]),
        ];
    }
}
```

`Menu` (`Orchid\Screen\Actions\Menu`) cheat sheet:

| Method | Purpose |
|---|---|
| `Menu::make('Label')` | Create an item |
| `->route('name')` / `->url('https://…')` | Link target; active state is auto-detected |
| `->icon('bs.heart')` | Leading icon from a configured set |
| `->title('Group')` | Section heading shown above the item |
| `->permission('slug')` / `->canSee(bool)` | Hide unless permitted; any boolean check works |
| `->badge(fn () => 10)` | Numeric counter bubble |
| `->sort(10)` | Ordering weight (ascending) |
| `->active('route.*')` / `->active([...])` | Override active highlighting |
| `->slug('key')` + `->list([...])` | Dropdown submenu (one nesting level supported) |

Dynamic sub-items from anywhere (e.g. a package service provider):

```php
use Orchid\Support\Facades\Dashboard;

Dashboard::addMenuSubElements('cabinet', [
    Menu::make('Prescriptions')->icon('bs.capsule')->route('platform.prescriptions'),
]);
```

Verified: `addMenuSubElements(string $slug, array $list)` lives on the `ManagesMenu` configuration trait used by the dashboard. The parent item must declare the same `->slug()`.

## Screen testing

Trait: `Orchid\Support\Testing\ScreenTesting`. It adds a `screen()` helper returning `Orchid\Support\Testing\DynamicTestScreen`. Assertions flow through Laravel's `TestResponse`, so `assertSee`, `assertOk`, `assertRedirect`, and friends all work.

```php
namespace Tests\Feature;

use App\Models\User;
use Orchid\Support\Testing\ScreenTesting;
use Tests\TestCase;

class PatientScreenTest extends TestCase
{
    use ScreenTesting;

    public function testListScreenRenders(): void
    {
        $user = User::factory()->create();

        $this->screen('platform.patients')
            ->actingAs($user)
            ->display()
            ->assertOk()
            ->assertSee('Patients');
    }

    public function testCreateAction(): void
    {
        $user = User::factory()->create();

        $this->screen('platform.patients')
            ->actingAs($user)
            ->method('save', ['patient' => ['name' => 'Ada']])
            ->assertOk();
    }

    public function testEditScreenWithRouteParameter(): void
    {
        $user = User::factory()->create();

        $this->screen('platform.patients.edit')
            ->parameters(['patient' => 1])
            ->actingAs($user)
            ->display()
            ->assertOk();
    }
}
```

`DynamicTestScreen` driver methods (verified in source):

| Method | Purpose |
|---|---|
| `screen($name, $params)` / `->parameters([...])` | Target route name; route + GET params (GET-reliant actions need their query values here too) |
| `->actingAs($user)` / `->be($user, $guard)` | Authenticate as a user (use a factory state that grants the screen's permission slugs) |
| `->display($headers)` | GET the screen; returns `TestResponse` |
| `->method($method, $params, $headers)` | POST to a screen action; returns `TestResponse` |
| `->call(...)` | Alias path for invoking actions |
| `->session([...])` / `->from($url)` | Seed session / set referer for redirect assertions |
| `->followingRedirects()` / `->withoutFollowingRedirects()` | Control redirect following |
| `->register($screen, $route, $middleware)` | Mount a screen under a throwaway route for isolated tests |

A `401/403` during tests means the user lacks the screen's `permission()` slugs — grant them in the factory rather than weakening the screen.

## Stubs

`php artisan orchid:stubs` copies the built-in generator stubs into `./stubs/orchid` for project-wide customization. Shipped stubs include `screen.stub`, `rows.stub`, `table.stub`, `field.stub`, `field.blade.stub`, `filters.stub`, `selection.stub`, `listener.stub`, `chart.stub`, `presenter.stub`, and `tabMenu.stub`. Delete any stub you do not customize; Orchid falls back to its bundled default.

Related generators (all land under `App\Orchid\…` by default):

```bash
php artisan orchid:screen PatientScreen
php artisan orchid:rows PatientRows
php artisan orchid:table PatientTable
php artisan orchid:filter PatientFilter
php artisan orchid:presenter PatientPresenter
php artisan orchid:listener PatientListener
```

Generated screens live in `app/Orchid/Screens` under namespace `App\Orchid\Screens` (verified in `ScreenCommand::getDefaultNamespace`).

## Cross-skill pointers

- Layout catalog (tabs, split, block, legend, cards, views, wrappers, charts, icons): see orchid-layouts.
- Form fields, `Layout::rows()` conventions, validation, and custom fields: see orchid-fields.
- `TD` columns, sorting, pagination, filters, and row actions: see orchid-tables.
- Buttons, modals, toast/alert, listeners, and Turbo behavior: see orchid-actions.
- Presenters, attachments, and global search: see orchid-models.
- Permissions, roles, guards, and menu gating details: see orchid-access.
- For simple model catalogs, full CRUD resources (list + edit screens, routes, menu) can be auto-generated with the orchid/crud package — see the orchid-crud skill.

## This project (medical-orchid)

- Single panel at `/admin` (`https://med-orch.test`); keep `prefix` at `/admin` and point `index` at the role-picked dashboard route.
- Menus are role-gated in `PlatformProvider::menu()`: super-admin sees everything; org-admin, doctor, and pharmacist see their scoped sections; receptionist sees Cabinet + Facturare items only. Gate every item with `->permission()` or `->canSee()`.
- Star detail screens (Patient, Appointment, Medical Visit, Billing Invoice) share one pattern: header `Layout::view` + `commandBar()` actions + `Layout::tabs` keyed by a `?tab=` query param; heavy tab tables load via `async` methods returning 10 rows per page with "view all" links.
- List screens paginate server-side at 25 rows per page; pass the paginator straight from `query()` to the table layout.
- Forms stack vertically with `Layout::rows()` (single column) per project rule; see orchid-fields for the save/validation flow.
- Scope every `query()` by role: super-admin unscoped, org-admin by `organization_id`, doctor by assigned clinics plus own patients, pharmacist by assigned pharmacy (mirror the legacy `OrgScopeMiddleware` as a reusable query constraint).
- User-facing `name()`, `description()`, and validation messages ship in both `en` and `ro` via `__()`.
- Before building any page, read `AGENTS.md`, `openspec/changes/ems-orchid-rebuild/design.md`, and the page's `designs/*.md`; verify each step with Pint and a browser pass at `https://med-orch.test`.

## Anti-patterns

- Registering `/idea` before `/idea/edit` so `edit` is swallowed by `{method?}` — always order specific routes first.
- Using deprecated `registerMenu()` / `registerMainMenu()` / `registerProfileMenu()` instead of `menu()` in `PlatformProvider`.
- Re-querying the same model in `name()`, `commandBar()`, and `layout()` instead of loading once in `query()` and reading public properties.
- Putting `query()` in the callable set: `query` is excluded from `getAvailableMethods()` and can never be a POST target — do not point buttons at it.
- Returning redirects or views from `async` methods; async paths expect data arrays merged into state and re-rendered fragments.
- Leaving `permission()` as `null` on sensitive screens, or gating the screen but forgetting `->permission()`/`->canSee()` on its menu item (or vice versa).
- Calling `env()` directly in screens or providers instead of `config('orchid.*')`, which breaks under `config:cache`.
- Mixing foreign component idioms (Filament resources/forms, Livewire properties/events) into Screens — Orchid has its own `query()`/`layout()`/`method()` lifecycle; do not cross the streams.

## Reference

- Docs: `https://orchid.software/en/docs/screens`
- Docs: `https://orchid.software/en/docs/quickstart`
- Docs: `https://orchid.software/en/docs/controllers`
- Docs: `https://orchid.software/en/docs/structure`
- Docs: `https://orchid.software/en/docs/configuration`
- Docs: `https://orchid.software/en/docs/menu`
- Docs: `https://orchid.software/en/docs/installation`
- Docs: `https://orchid.software/en/docs/testing`
- Docs: `https://orchid.software/en/docs/stubs`
- Source (in a real app under `vendor/orchid/platform/src/`): `Screen/Screen.php`, `Screen/Concerns/HasCommandBar.php`, `Screen/Concerns/HasFillablePublicProperties.php`, `Screen/Concerns/InteractsWithEncryptedState.php`, `Screen/Concerns/ModelStateRetrievable.php`, `Platform/Providers/FoundationServiceProvider.php` (`registerScreenMacro`), `Platform/OrchidServiceProvider.php` (`menu()`, `permissions()`), `Platform/Configuration/ManagesMenu.php` (`addMenuSubElements`), `Platform/Commands/ScreenCommand.php`, `Platform/Commands/InstallCommand.php`, `Platform/Commands/AdminCommand.php`, `Platform/Commands/StubPublishCommand.php`, `Support/Testing/ScreenTesting.php`, `Support/Testing/DynamicTestScreen.php`
