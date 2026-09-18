---
name: orchid-crud
description: "Rapid CRUD resources on top of Orchid Platform: one static Resource class per model generating List/Create/Edit/View screens. Activate when working with orchid:resource, Resource classes, ResourceRequest, CRUD actions, filters, or SoftDeletes restores. Do not use for hand-written Screens, field/table internals, or permissions design."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: orchid:resource, Resource, Arbitrator, ResourceRequest, orchid:action, DefaultSorted, WithTrashed
  role: specialist
  scope: laravel
  related-skills: orchid-screens, orchid-fields, orchid-tables, orchid-models
---

# Orchid CRUD Resources

The `orchid/crud` package generates a full admin UI (list, create, edit, view screens plus routes, menu entries, and permissions) from a single static Resource class per Eloquent model. One file declares the model, columns, fields, legend, rules, filters, and actions; the package renders standard Orchid screens from it. Orchid 14.x, Laravel 10–13. When a resource outgrows the declarative API, drop down to hand-written platform Screens — the package is designed for that migration.

## Version notes

Mined from `CHANGELOG.md` of `orchid/crud` (no `upgrade.md` entries apply to this package):

- `4.4.0` (2025-03-01): added `canShowTableActions()` to hide table actions; Laravel 12 support.
- `4.3.0` (2024-09-08): fixed duplicate menu registration under Octane; `WithTrashed` no longer sets an active status filter.
- `4.1.0` (2023-10-06): actions can run against soft-deleted models.
- `4.0.0` (2023-05-28): support for Orchid 14 (current major).
- Compatibility: `orchid/platform ^14.0` required, conflict with `<14.34`; requires `orchid/blade-icons >= 4.2`. Check `composer.json` before installing.

## Installation

Install on top of an existing Laravel + Orchid application:

```bash
composer require orchid/crud
```

The package auto-registers `Orchid\Crud\CrudServiceProvider` via Composer `extra.laravel.providers` (verified in `composer.json`). The provider registers the `orchid:resource` and `orchid:action` commands, loads JSON translations from `resources/lang/`, mounts routes from `routes/crud.php` under the platform prefix with `platform.` name prefix, and auto-discovers resources in `app/Orchid/Resources`.

Upstream quirk (verified by grep): `CrudServiceProvider::boot()` reads `config('platform.domain')` and `config('platform.middleware.private')` (`src/CrudServiceProvider.php` lines 46–49), while the current platform package publishes its config as `config/orchid.php`. If resource routes fail to register or resolve, check which config key your installed platform version actually populates before debugging further.

## When to use CRUD resources vs hand-written Screens

| Situation | Use a CRUD Resource | Use a hand-written Screen |
|---|---|---|
| Simple catalog (lookup tables, taxonomies, reference data) | Yes — one file, zero routes/menu code | Overkill |
| Standard create/edit/view with one model, no cross-entity logic | Yes | Only if you need custom layout control |
| Labeled list + form + detail legend | Yes (`columns()`, `fields()`, `legend()`) | Only for multi-section detail pages |
| Custom queries (joins, aggregates, multi-model dashboards) | No — `modelQuery()`/`paginationQuery()` cover only single-model tweaks | Yes |
| ★ Detail screens (Patient, Appointment, Medical Visit, Billing Invoice): header view + `commandBar()` + `Layout::tabs` with `?tab=` | No | Yes, always |
| Complex permissions (per-row, per-tab, multi-guard flows) | No — resource `permission()` is one key plus model policies | Yes (see `orchid-access`) |
| Async/lazy tab tables, modal wizards, listeners, custom state | No | Yes (see `orchid-screens`, `orchid-actions`) |
| Heavy table customization (custom cell renderers per column, bulk UX beyond row actions) | Partial — columns are platform `TD`, but layout is fixed | Yes |

Rule of thumb: if the page fits "list rows, click to view/edit, one form, one legend", write a Resource. The moment you need tabs, multiple models on one screen, or custom `query()` logic beyond a scope, write a Screen. Both coexist in one panel — resources register their own menu entries and routes alongside hand-written screens.

## Defining a Resource

Generate the stub:

```bash
php artisan orchid:resource PostResource
```

Resources live in `app/Orchid/Resources` and are **completely static** — no instance state. Custom helper methods must be static. Minimal resource:

```php
<?php

declare(strict_types=1);

namespace App\Orchid\Resources;

use App\Models\Post;
use Orchid\Crud\Resource;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

class PostResource extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Post::class;

    public function columns(): array
    {
        return [
            TD::make('id')->sort(),
            TD::make('title')->sort()->filter(),
        ];
    }

    public function fields(): array
    {
        return [
            Input::make('title')
                ->title('Title')
                ->placeholder('Enter title here')
                ->required(),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('id', 'ID'),
            Sight::make('title', 'Title'),
        ];
    }
}
```

### Resource API inventory

Verified against `src/Resource.php` (only documented members are listed — there is no `screen()` override method; screen output is derived from the members below):

| Member | Kind | Purpose |
|---|---|---|
| `$model` | `public static string` | Eloquent model class. The only required member. |
| `columns(): array` | abstract | `TD[]` for the list table. Same `TD` API as hand-written tables (see `orchid-tables`). |
| `fields(): array` | abstract | `Field[]` for create/edit forms. Same field API as the platform (see `orchid-fields`). |
| `legend(): array` | abstract | `Sight[]` for the view screen (see `orchid-layouts`). |
| `rules(Model $model): array` | method | Validation rules for create/update. Receives the bound model so `Rule::unique(...)->ignore($model)` works. |
| `messages(): array` | method | Custom validation messages. |
| `attributes(): array` | method | Custom validation attribute names. |
| `filters(): array` | method | Filter classes (platform `Filter` subclasses or crud built-ins). |
| `with(): array` | method | Relationships to eager-load on the index query. |
| `actions(): array` | method | Custom action classes (subclasses of `Orchid\Crud\Action`). |
| `modelQuery(ResourceRequest $request, Model $model): Builder` | method | Single-model query for view/edit screens. Override to constrain (e.g. org scoping). |
| `paginationQuery(ResourceRequest $request, Model $model): Builder` | method | List query. Override to constrain or order. |
| `save(ResourceRequest $request, Model $model): void` | method | Create/update persistence. Default is `$model->forceFill($request->all())->save()`. Legacy `onSave()` hook still honored if defined. |
| `delete(Model $model): void` | method | Soft- or hard-delete. Legacy `onDelete()` hook still honored if defined. |
| `restore(Model $model): void` | method | Restore a soft-deleted model. `onRestore()` hook honored if defined. |
| `forceDelete(Model $model): void` | method | Permanent delete. `onForceDelete()` hook honored if defined. |
| `canShowTableActions(): bool` | method | Return `false` to hide the per-row actions column. Added in 4.4.0. |
| `label(): string` | static | Plural display name. Override with `__('Posts')` for localization. |
| `singularLabel(): string` | static | Singular display name. |
| `icon(): string` | static | Menu icon key (Orchid icons, see `orchid-layouts`). |
| `sort(): string` | static | Menu sort weight. |
| `permission(): ?string` | static | Permission key guarding the resource; `null` (default) means open to all panel users. Auto-registers the permission. |
| `displayInNavigation(): bool` | static | Return `false` to hide from the menu while keeping direct route access via `route('platform.resource.list', Resource::uriKey())`. |
| `perPage(): int` | static | Rows per page. |
| `description(): ?string` | static | Extra description rendered on resource pages. |
| `trafficCop(): bool` | static | Enable last-change conflict check between viewing and updating. Default `false`. |
| `softDeletes(): bool` | static | Auto-detected from the model (`SoftDeletes` trait present). Do not override unless you know why. |
| `uriKey()` | static | Route key derived from the class name. |
| Button/toast labels | static | `createButtonLabel()`, `createToastMessage()`, `updateButtonLabel()`, `updateToastMessage()`, `deleteButtonLabel()`, `deleteToastMessage()`, `saveButtonLabel()`, `restoreButtonLabel()`, `restoreToastMessage()`, breadcrumb messages, `trafficCopMessage()` — all overridable for localization. |

Note: `ResourceRequest` (used by `save()`, `rules()`, `modelQuery()`) proxies `rules()`, `attributes()`, and `messages()` to the resource and strips unknown keys during validation (`prepareForValidation` + `withValidator` in `src/ResourceRequest.php`). Only keys declared in `fields()` survive to `save()`.

## Registering Resources

Automatic: every class in `app/Orchid/Resources` is discovered by `ResourceFinder` and booted by `Arbitrator` — no manual step. Verified in `CrudServiceProvider::boot()`.

Manual (packages, modules, non-standard paths). Verified signature in `src/Arbitrator.php`:

```php
use App\Orchid\Resources\UserResource;
use Illuminate\Support\ServiceProvider;
use Orchid\Crud\Arbitrator;

class CrudServiceProvider extends ServiceProvider
{
    public function boot(Arbitrator $arbitrator): void
    {
        $arbitrator->resources([
            UserResource::class,
        ]);
    }
}
```

`Arbitrator::resources(array $resources): Arbitrator` accepts class names, sorts them, registers menu entries and permissions, and wires routes. Menu entries are added via a dashboard view composer; duplicate registration under Octane was fixed in 4.3.0.

## Model requirements

Add the three platform traits to any model behind a resource:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Post extends Model
{
    use AsSource;
    use Filterable;
    use Attachable;
}
```

| Trait | Why the resource needs it |
|---|---|
| `Orchid\Screen\AsSource` | Exposes model attributes to screens/fields (`getContent`). Without it, fields and legends render empty. |
| `Orchid\Filters\Filterable` | Enables HTTP-driven filtering/sorting on the list query. Required for column filters and `filters()` to take effect. |
| `Orchid\Attachment\Attachable` | Enables file attachments on the model. Required only if any `Attach`/`Cropper`/`Picture` field or attachment UI is used (see `orchid-models`). |

## Generated screens

Four screens are generated from each resource (`src/Screens/`). They are package-internal classes (`ListScreen`, `CreateScreen`, `EditScreen`, `ViewScreen` extending `CrudScreen`) — you do not subclass or register them:

| Screen | Route (under `platform.` prefix) | Provides |
|---|---|---|
| `ListScreen` | `resource.list` | Paginated table from `columns()` + `filters()` + `paginationQuery()`; per-row View/Edit/Delete group (hidden when `canShowTableActions()` is `false`); create button; bulk actions when `actions()` is non-empty. |
| `CreateScreen` | `resource.create` | Form from `fields()`, validated by `rules()`, persisted by `save()`. |
| `EditScreen` | `resource.edit` | Same form bound to the model via `modelQuery()`; conflict error when `trafficCop()` is enabled and the record changed. |
| `ViewScreen` | `resource.view` | `Legend` from `legend()` plus restore/force-delete buttons for soft-deleted models. |

Customization path (all inside the Resource class — there is no per-screen override API):

1. Change `columns()`, `fields()`, `legend()` — covers 90% of needs.
2. Override `rules()` / `messages()` / `attributes()` for validation.
3. Override `modelQuery()` / `paginationQuery()` for scoping (org, clinic, pharmacy).
4. Override `save()` / `delete()` / `restore()` / `forceDelete()` for side effects (audit logs, sync jobs). Prefer calling `parent`-equivalent default logic first, then your side effect.
5. Hide the row-actions column with `canShowTableActions(): bool` returning `false`.
6. If none of this suffices, stop using a Resource for that model and write hand-written Screens (see "Escalation" below).

## Fields, columns, and legends

The package reuses the platform field/table APIs with zero wrappers — anything valid in a Screen is valid here:

```php
use Orchid\Screen\Fields\Input;
use Orchid\Screen\TD;

public function fields(): array
{
    return [
        Input::make('title')
            ->title('Title')
            ->placeholder('Enter title here')
            ->required(),
    ];
}

public function columns(): array
{
    return [
        TD::make('id')->sort(),
        TD::make('title')->sort()->filter(),
    ];
}
```

- Field catalog, shared `Field` API, and save/validation flow: see `orchid-fields`.
- `TD` columns, sorting, cell rendering, `filter()` variants: see `orchid-tables`.
- `Sight` rows for `legend()`: see `orchid-layouts`.

## Filters

`filters()` returns platform filter classes. Two are bundled (`src/Filters/`):

| Filter | Purpose | Usage |
|---|---|---|
| `Orchid\Crud\Filters\DefaultSorted` | Hidden default sort (no UI, `$display = false`) | `new DefaultSorted('id', 'desc')` |
| `Orchid\Crud\Filters\WithTrashed` | Checkbox "Show deleted entries"; switches the list query to `onlyTrashed()` | `WithTrashed::class` |

Generate a custom filter with the platform command, then reference it:

```bash
php artisan orchid:filter QueryFilter
```

```php
use App\Orchid\Filters\QueryFilter;
use Orchid\Crud\Filters\DefaultSorted;
use Orchid\Crud\Filters\WithTrashed;

public function filters(): array
{
    return [
        QueryFilter::class,
        WithTrashed::class,
        new DefaultSorted('id', 'desc'),
    ];
}
```

Custom filters are plain platform `Filter` subclasses (`name()`, `run()`, `display()`); filter authoring, filter layouts, and `HttpFilter` details live in `orchid-tables`. `WithTrashed` pairs with `softDeletes()`: it renders only when the model uses `SoftDeletes`, and since 4.3.0 it no longer forces an active status filter.

## Actions

Row and bulk actions share one API. `handle()` always receives a `Collection`, even for a single row. Generate the stub (verified: `orchid:action` via `ActionCommand`, default namespace `App\Orchid\Actions`):

```bash
php artisan orchid:action PublishAction
```

```php
<?php

declare(strict_types=1);

namespace App\Orchid\Actions;

use Illuminate\Support\Collection;
use Orchid\Crud\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;

class PublishAction extends Action
{
    public function button(): Button
    {
        return Button::make('Publish')->icon('bs.check-circle');
    }

    public function handle(Collection $models): void
    {
        $models->each(fn ($model) => $model->update(['status' => 'published']));

        Toast::message('Published.');
    }
}
```

Attach to the resource:

```php
public function actions(): array
{
    return [
        PublishAction::class,
    ];
}
```

- Bulk support (selecting multiple rows and running one action) is shipped — the roadmap checkbox is done, and the list screen renders the actions dropdown only when `actions()` is non-empty (`CrudScreen` checks `$actions->isNotEmpty()` for `canSee`).
- `Action::name()` derives a display name from the class basename. Actions run against soft-deleted models too (since 4.1.0).
- Modal confirmations, `DropDown` buttons, toasts: see `orchid-actions`. Authorization inside `handle()` (policies, roles): see `orchid-access`.

## SoftDeletes

Detection is automatic: `Resource::softDeletes()` returns `true` when the model uses `Illuminate\Database\Eloquent\SoftDeletes`. Behavior then:

- List gains restore/force-delete row actions; `WithTrashed` filter shows only trashed records when checked.
- `restore()` / `forceDelete()` on the resource handle each path; override them (or define legacy `onRestore()` / `onForceDelete()`) for side effects.
- View screen exposes restore and permanent-delete buttons.
- Validation caveat: `Rule::unique` in `rules()` ignores only the bound model, not trashed duplicates — add `->whereNull('deleted_at')` when a unique column must ignore trashed rows.

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

public function rules(Model $model): array
{
    return [
        'code' => [
            'required',
            Rule::unique(self::$model, 'code')->ignore($model)->whereNull('deleted_at'),
        ],
    ];
}
```

## Navigation, permissions, and policies

- `displayInNavigation(): bool` hides the menu entry; the list route stays reachable at `route('platform.resource.list', Resource::uriKey())`.
- `icon()` / `sort()` control the menu item appearance and position.
- `permission(): ?string` returns a key (e.g. `'private-post-resource'`) that the `Arbitrator` auto-registers as a platform permission; grant it via roles/users. `null` (default) leaves the resource open.
- Laravel model policies are honored automatically with no extra configuration: if a policy is registered for the model, the package checks `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`. If a policy exists but omits a method, that action is denied — define all relevant methods or a `before()` gate. Pattern details live in `orchid-access`.

## Traffic cop, eager loading, pagination

```php
public function with(): array
{
    return ['author'];
}

public static function perPage(): int
{
    return 25;
}

public static function trafficCop(): bool
{
    return true;
}
```

- `with()` eager-loads relationships on the index query — use it whenever a column or legend cell touches a relation (avoids N+1).
- Keep `perPage()` at 25 to match project list conventions.
- `trafficCop()` guards long edit sessions: when `true`, saving a record modified after the form opened throws a validation error (message customizable via `trafficCopMessage()`).

## Translations

- Package strings (buttons, toasts, breadcrumbs, `WithTrashed` labels) go through Laravel `__()` and ship as JSON locale files loaded via `loadJsonTranslationsFrom()` (verified in `CrudServiceProvider::register()`).
- Shipped locales (verified `resources/lang/` listing): `de, es, es_AR, es_CO, es_MX, fr, it, ja, nl, pl, pt, pt_BR, ru, th, tk, uk, zh_CN`. **There is no `en.json`** (English strings are the source-language defaults in code) **and no `ro.json`**.
- Project impact: user-facing strings must be en + ro. If CRUD resources are adopted, add a `ro.json` (and optionally `en.json` overrides) under the app's `lang/` directory with the package's message keys translated — Laravel merges app JSON translations over vendor ones. Localize resource names per resource via `label()` / `singularLabel()` / button/toast/breadcrumb overrides using `__()`.

## Escalation: from Resource to hand-written Screen

The docs promise "it's easy to switch to using the platform". Concretely:

1. Scaffold a Screen (`php artisan orchid:screen ...`), route, and menu entry manually (see `orchid-screens`).
2. Copy `columns()` into a `Layout::table()`, `fields()` into a `Layout::rows()`, `legend()` into a `Layout::legend()` — the objects are identical platform classes, no rewrite needed.
3. Move `rules()` into the Screen's save method validation, `filters()` into the table's filter configuration, `actions()` into `commandBar()` buttons.
4. Move `modelQuery()`/`paginationQuery()` constraints into the Screen's `query()`, and `save()`/`delete()` side effects into Screen methods.
5. Delete the Resource class (routes/menu for it unregister automatically since discovery no longer finds it) or keep both: a Resource for the simple catalog view and Screens for the detail workflows can coexist.

## This project (medical-orchid)

- Current rule is **Standard Orchid Screens only** (`AGENTS.md`): CRUD resources are NOT in use today. Adopt them only for simple catalogs (diagnosis codes, tariff lists, document types) after team agreement — never for the four ★ detail screens.
- ★ Detail screens (Patient, Appointment, Medical Visit, Billing Invoice) stay hand-written: header `Layout::view` + `commandBar()` + `Layout::tabs` with `?tab=` cannot be expressed in a Resource.
- If adopted: keep resources in `app/Orchid/Resources`, set `perPage()` to 25, stack form fields for single-column `Layout::rows()` equivalence, and scope `paginationQuery()`/`modelQuery()` by role (org → clinic/pharmacy) mirroring the legacy `OrgScopeMiddleware`.
- Models are flat in `app/Models/` copied from legacy — add `AsSource`, `Filterable` (and `Attachable` when files are involved) at copy time so a future Resource works without model edits. Respect additive-only migrations; resources must never need schema changes.
- Plan the missing `ro.json` translation overrides before exposing any resource to users; verify both locales in a browser pass at `https://med-orch.test`.
- Permission story must match role-gated menus: give every adopted resource a `permission()` key and wire it to super-admin/org-admin/doctor/pharmacist/receptionist roles (see `orchid-access`).

## Anti-patterns

- Adding instance state or non-static helpers to a Resource — classes are declarative and static-only; store nothing on `$this`.
- Writing a Resource for a ★ detail screen (tabs, multi-model header, async tables) instead of a hand-written Screen.
- Skipping `AsSource`/`Filterable` on the model, then debugging empty fields or dead column filters.
- Forgetting `with()` when columns touch relations — classic N+1 on every list page.
- Defining a model policy with only `update()` and wondering why view/create/delete are denied — missing policy methods deny by default.
- Reaching for `onSave()`/`onDelete()` legacy hooks in new code — override `save()`/`delete()` directly (hooks remain only for backward compatibility).
- Inventing a `screen()` override or custom screen classes under `Screens/` — the four generated screens are package-internal; customization happens inside the Resource.
- Mixing Filament/Livewire idioms (form schemas, reactive components) into resources — use platform `Field`/`TD`/`Sight` APIs only.

## Reference

- Docs: `https://orchid.software/en/docs/packages/crud` (primary; covers all sections above)
- Filters background: `https://orchid.software/en/docs/filters`
- Table layer: `https://orchid.software/en/docs/table`
- Legend layer: `https://orchid.software/en/docs/legend`
- Fields catalog: `https://orchid.software/en/docs/field`
- Package repo: `https://github.com/orchidsoftware/crud`
- Source in a real app: `vendor/orchid/crud/src/Resource.php`, `vendor/orchid/crud/src/Arbitrator.php`, `vendor/orchid/crud/src/CrudScreen.php`, `vendor/orchid/crud/src/Screens/`, `vendor/orchid/crud/src/Filters/`, `vendor/orchid/crud/src/ResourceRequest.php`, `vendor/orchid/crud/src/Action.php`
