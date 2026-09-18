---
name: orchid-tables
description: "Build Orchid list screens with TD tables, sorting, pagination, filters and row actions. Activate when working with Layout::table, TD columns, cell components, Filterable models, Selection layouts or custom Filter classes. Do not use for form fields, modals/buttons, chart layouts, or Eloquent model design."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: TD, Layout::table, sortable table, column filter, Selection layout, Filterable, paginate, DateTimeSplit, cell component
  role: specialist
  scope: laravel
  related-skills: orchid-actions, orchid-layouts, orchid-models, orchid-screens
---

# Orchid Tables

List screens are the backbone of every Orchid admin panel. A `Table` layout renders
a server-side paginated table from a `query()` key, and each column is a `TD` cell
definition. This skill covers `TD` basics, sorting, inline column filters, custom `Filter` classes with
`Selection` layouts, cell formatting components, row actions, and empty states. Orchid 14.x, Laravel 10–13.
For action buttons (`Link`, `Button`, `DropDown`, `ModalToggle`) see `orchid-actions`.
For other layouts (`rows`, `tabs`, `legend`, charts) see `orchid-layouts`.
For presenters and platform-wide search see `orchid-models`.
For simple catalogs, full resources can be auto-generated with the `orchid/crud` package — see `orchid-crud`.

## Version notes

Mined from `upgrade.md` — only items that affect tables and filters:

- **Explicit filter types required.** Automatic HTTP filtering used to guess the
  comparison from user input (a comma could turn text into an array). Now every
  `allowedFilters` entry must declare its handler (`Like::class`, `Where::class`, …).
  Undeclared columns are ignored.
- **No dot notation for JSON columns in filters/sorts.** The column name is now
  passed as-is; JSON-path filtering needs a custom `Filter` class.
- **`component()` argument passing changed.** Positional extra args
  (`TD::make('x')->component(Foo::class, 'order', [...])`) are replaced by named
  arguments (`value:`, plus named component params). Use
  `->component(Foo::class, value: fn ($row) => ...)` for row-derived values.
- **`canSee()` is shared.** `TD`, fields, and layouts now share one trait, so
  `TD::make(...)->canSee(false)` hides a whole column — no wrapper needed.

## Creating a table layout

Generate the layout class, then wire it to a screen:

```bash
php artisan orchid:table PatientListLayout
```

A `Table` subclass declares two things: a `$target` key matching `query()`,
and a `columns()` list of `TD` objects.

```php
<?php

declare(strict_types=1);

namespace App\Orchid\Layouts;

use App\Models\Patient;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class PatientListLayout extends Table
{
    /**
     * Data source.
     *
     * The key to fetch from the screen's query().
     * Its value must be paginated (LengthAwarePaginator).
     */
    protected $target = 'patients';

    protected function columns(): iterable
    {
        return [
            TD::make('full_name', __('Name'))->sort()->cantHide()->filter(Input::make())
                ->render(fn (Patient $p) => Link::make(e($p->full_name))
                    ->route('platform.patients.edit', $p)),
            TD::make('cnp', 'CNP')->cantHide(),
            TD::make('created_at', __('Registered'))->sort(),
        ];
    }
}
```

### Feeding the table from query()

The screen returns a paginated Eloquent query under the same key.
Always `paginate()` on the server — never pass a plain collection to a
production list.

```php
use App\Models\Patient;

public function query(): iterable
{
    return [
        'patients' => Patient::filters()
            ->defaultSort('id', 'desc')
            ->paginate(25),
    ];
}
```

| Pattern | When to use |
|---|---|
| `Model::filters()->defaultSort('id', 'desc')->paginate(25)` | Standard list, HTTP sort/filter aware |
| `Model::filters(SelectionClass::class)->paginate(25)` | List with custom `Filter` classes |
| `->simplePaginate()` | Very large tables where total count is expensive |

> `paginate()` with no argument uses the app default (15). Pass the page size
> explicitly. Short syntax `Layout::table('key', [...])` exists for prototypes.

## TD basics

`TD` extends `Orchid\Screen\Cell`. Create columns with `TD::make($name, $title = null)`.
The title defaults to `Str::title($name)`. There are no separate `name()`/`title()`
setters — set both through `make()`.

```php
TD::make('last_name');               // title: "Last Name"
TD::make('last_name', 'Family name'); // explicit title
TD::make(__('Actions'));              // action column, no data key
```

### TD method inventory

Every method below was verified in `src/Screen/Cell.php` or `src/Screen/TD.php`.

| Method | Purpose |
|---|---|
| `TD::make($name, $title = null)` | Create a column bound to `$name` |
| `render(Closure $closure)` | Custom cell output; receives `($row, $loop)` |
| `sort(bool $sort = true)` | Enable header-click sorting (needs `allowedSorts`) |
| `filter($filter, $options = null)` | Inline header filter UI (`Input::make()`, `Select::make()`, …) |
| `filterValue(callable)` | Rewrite the applied filter value for display |
| `filterOptions(iterable)` | Options when the inline filter is a select |
| `align(string)` / `alignLeft()` / `alignRight()` / `alignCenter()` | Cell alignment |
| `width($width)` | CSS width, e.g. `->width('100px')` |
| `style($style)` / `class($class)` | Inline style / CSS class on cells |
| `colspan(int)` | Span in the `total()` summary row |
| `cantHide(bool $hidden = false)` | Forbid the user hiding this column |
| `defaultHidden(bool $hidden = true)` | Hidden until the user enables it |
| `canSee(bool)` | Hide the whole column conditionally (shared trait) |
| `popover(string $text)` | Tooltip on the column header |
| `component(string $class, ...$params)` | Render cell with a Blade component, whole row injected |
| `usingComponent(string $class, ...$params)` | Render cell with a component, cell value injected |
| `asComponent(string $class, array $params = [])` | Alias-style component binding with named params |

There is **no** `sortable()` or `asSortable()` on `TD` — the method is `sort()`.
There is **no** `searchable()` on `TD` — platform-wide search is a
`Searchable` concern on presenters (see `orchid-models`).

```php
TD::make('cnp', 'CNP')->align(TD::ALIGN_LEFT)->width('140px')->cantHide();

TD::make('notes')->defaultHidden() // user opts in via column picker
    ->popover(__('Internal notes, not printed on reports.'));
```

`TD::ALIGN_LEFT`, `TD::ALIGN_CENTER`, `TD::ALIGN_RIGHT` resolve to
`'start'`, `'center'`, `'end'`.

### render() closures

`render()` receives the row model as the first argument and the Blade `$loop`
object as the second. The returned string is **not escaped** — escape it yourself
with `e()` or return a component/action object.

```php
TD::make('full_name', __('Name'))
    ->render(fn (Patient $p) => e($p->first_name.' '.$p->last_name));

TD::make('phone')->render(fn (Patient $p) => e($p->phone ?? '—')); // NULL guard

TD::make('#')->render(fn ($row, object $loop) => $loop->iteration);

TD::make('summary')
    ->render(fn (Visit $v) => view('orchid.cells.visit-summary', ['visit' => $v]));
```

## Sorting

Two halves: the model opts in, the column opts in. Both are required.

**1. Model** — use `Filterable`, whitelist sortable columns:

```php
use Orchid\Filters\Filterable;

class Patient extends Model
{
    use Filterable;

    protected $allowedSorts = ['full_name', 'created_at', 'updated_at'];
}
```

**2. Layout** — call `->sort()` on the column:

```php
TD::make('created_at', __('Registered'))->sort(),
```

Clicking the header toggles `?sort=created_at` / `?sort=-created_at`.
`defaultSort()` sets the order when the request carries no `sort` param:

```php
Patient::filters()->defaultSort('id', 'desc')->paginate(25);
```

> Sorting works on real table columns only. Related-model columns
> (`doctor.name`) cannot use automatic sorting — denormalize, join explicitly,
> or sort in a custom `Filter`.

## Inline column filters

For simple per-column filtering, attach a field instance with `filter()`.
The field name is taken from the column automatically — do not set it.

```php
TD::make('cnp', 'CNP')->filter(Input::make());
TD::make('status')->filter(
    Select::make()->options(['active' => __('Active'), 'archived' => __('Archived')])->empty()
);
```

The built-in string constants (`TD::FILTER_TEXT`, `FILTER_NUMERIC`,
`FILTER_DATE`, `FILTER_DATE_RANGE`, `FILTER_NUMBER_RANGE`, `FILTER_SELECT`)
select the default UI; passing a field instance (`Input::make()`,
`Select::make()`, `DateRange::make()`, `NumberRange::make()`, `DateTimer::make()`)
gives full control. Prefer passing field instances. The filtering logic itself
comes from the model's `allowedFilters` map (see next section).

### Automatic HTTP filtering and sorting

Declare handlers per column on the model. The key is the column, the value is
the handler class:

```php
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\{Like, Where, WhereDate, WhereDateStartEnd, WhereIn, WhereMaxMin};

class Patient extends Model
{
    use Filterable;

    protected $allowedFilters = [
        'id'         => Where::class,
        'doctor_id'  => WhereIn::class,
        'cnp'        => Like::class,
        'full_name'  => Like::class,
        'status'     => Where::class,
        'created_at' => WhereDateStartEnd::class,
        'visit_date' => WhereDate::class,
    ];
}
```

| Handler | SQL | Request example |
|---|---|---|
| `Where` | `where(col, value)` | `?filter[status]=active` |
| `Like` | `where(col, 'like', '%v%')` | `?filter[full_name]=pop` |
| `Ilike` | case-insensitive `LIKE` (PostgreSQL) | `?filter[title]=hello` |
| `WhereIn` | `whereIn(col, [...])` | `?filter[id]=1,2,3` or `?filter[id][]=1&filter[id][]=2` |
| `WhereBetween` | `whereBetween(col, [a, b])` | `?filter[price][]=10&filter[price][]=20` |
| `WhereMaxMin` | `>= min` / `<= max` | `?filter[id][min]=1&filter[id][max]=5` |
| `WhereDate` | `whereDate(col, value)` | `?filter[visit_date]=2026-09-18` |
| `WhereDateStartEnd` | date range | `?filter[created_at][start]=2026-01-01&filter[created_at][end]=2026-09-01` |

`Ilike` is PostgreSQL-only; on MySQL use `Like`. `WhereBetween` needs its own
import — `Orchid\Filters\Types\WhereBetween`.

## Custom filters and Selection layouts

Inline column filters cover single-column cases. For anything relational
(`whereHas`), multi-field, or always-on scoping, write a `Filter` class and
group it in a `Selection` layout.

Generate both:

```bash
php artisan orchid:filter DoctorFilter
php artisan orchid:selection PatientSelection
```### Filter class anatomy

`Orchid\Filters\Filter` requires `parameters()` + `run()`; `display()`,
`name()`, and `value()` shape the UI.

| Member | Purpose |
|---|---|
| `parameters(): ?array` | Request keys this filter reacts to. Return `[]` to run on every request |
| `run(Builder $builder): Builder` | Apply constraints, return the builder |
| `display(): iterable` | Fields shown in the filter panel |
| `name(): string` | Human-readable label |
| `value(): string` | Active-filter badge text (ids → names) |
| `isApply()` / `isDisplay()` / `count()` | Introspection helpers from the base class |

Full example — relational select filter (adapted from the `RoleFilter` stub):

```php
<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class DoctorFilter extends Filter
{
    public function name(): string
    {
        return __('Doctor');
    }

    public function parameters(): array
    {
        return ['doctor'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->whereHas(
            'doctor',
            fn (Builder $q) => $q->where('id', $this->request->get('doctor'))
        );
    }

    public function display(): array
    {
        return [
            Select::make('doctor')
                ->fromModel(Doctor::class, 'full_name')
                ->empty()
                ->value($this->request->get('doctor'))
                ->title(__('Doctor')),
        ];
    }

    public function value(): string
    {
        return $this->name().': '.(Doctor::find($this->request->get('doctor'))?->full_name ?? '');
    }
}
```

Notes: `$this->request` is the current HTTP request. An empty `display()`
makes the filter silent (still applied, hidden from UI). `parameters()`
returning `[]` forces `run()` on every request — use for role-based scoping.

### Selection layout

`Orchid\Screen\Layouts\Selection` groups filter classes and renders them above
the table. Include it in the screen's `layout()` **and** pass it to `filters()`.

```php
<?php

declare(strict_types=1);

namespace App\Orchid\Layouts;

use App\Orchid\Filters\DoctorFilter;
use App\Orchid\Filters\StatusFilter;
use Orchid\Filters\Filter;
use Orchid\Screen\Layouts\Selection;

class PatientSelection extends Selection
{
    public $template = self::TEMPLATE_DROP_DOWN; // or TEMPLATE_LINE

    /** @return Filter[] */
    public function filters(): array
    {
        return [DoctorFilter::class, StatusFilter::class];
    }
}
```

Wire-up in the screen — pass the Selection to `filters()` and list it first
in `layout()`:

```php
public function query(): iterable
{
    return [
        'patients' => Patient::filters(PatientSelection::class)
            ->defaultSort('id', 'desc')
            ->paginate(25),
    ];
}

public function layout(): iterable
{
    return [PatientSelection::class, PatientListLayout::class];
}
```

`$template` accepts `Selection::TEMPLATE_DROP_DOWN` (default) or `TEMPLATE_LINE`, or any custom Blade view path.

### Filter fields cheat sheet

`display()` returns ordinary form fields. The most useful for filters:

| Field | Use |
|---|---|
| `Input::make()->type('text')->placeholder(...)->title(...)` | Free-text search |
| `Select::make()->options([...])->empty()` / `->fromModel(Model::class, 'label')` | Fixed or model-driven choices |
| `DateRange::make()` / `NumberRange::make()` / `DateTimer::make()` | Range and date pickers |

`->empty()` (on `Select`) adds the blank "no choice" entry — required for
clearable filters. `->value($this->request->get('key'))` re-populates the field
from the active request.

## Row actions inside TD

An action column has no data key. Render `Link`, `Button`, or `DropDown`
objects from `render()`. For the full action catalog (modals, confirms,
methods, icons) see `orchid-actions` — this section covers table placement only.

```php
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;

TD::make(__('Actions'))->align(TD::ALIGN_CENTER)->width('100px')
    ->render(fn (Patient $p) => DropDown::make()
        ->icon('bs.three-dots-vertical')
        ->list([
            Link::make(__('View'))->route('platform.patients.view', $p)->icon('bs.eye'),
            Link::make(__('Edit'))->route('platform.patients.edit', $p)->icon('bs.pencil'),
            Button::make(__('Archive'))->icon('bs.archive')
                ->confirm(__('Archive this patient record?'))
                ->method('archive', ['id' => $p->id]),
        ]));
```

Single-link cells (name → edit form) and inline groups follow the same
pattern — see `orchid-actions` for `Link` options and the `Group` wrapper.

## Cell formatting components

`Orchid\Screen\Components\Cells\*` format common types without `render()`
closures. They work in both `TD` columns and `Sight` legend rows
(see `orchid-layouts` for `Sight`).

| Component | Constructor | Renders |
|---|---|---|
| `Cells\DateTimeSplit` | `(mixed $value, string $upperFormat = 'M j, Y', string $lowerFormat = 'D, H:i', $tz = null)` | Two-line date + weekday/time |
| `Cells\DateTime` | `(mixed $value, string $format = 'Y-m-d H:i', $tz = null)` | Single-line date |
| `Cells\Time` | `(mixed $value, $tz = null, ...)` | Relative time |
| `Cells\Number` | `(float $value, int $decimals = 0, ...)` | Grouped number |
| `Cells\Currency` | `(float $value, int $decimals = 2, ?string $before = '', ?string $after = '')` | Money with symbols |
| `Cells\Percentage` | `(float $value, int $decimals = 0, ...)` | Value + `%` |
| `Cells\Boolean` | `(?bool $value, ?string $true = null, ?string $false = null)` | True/false labels |
| `Cells\Text` | `(mixed $value, ?int $words = 30, ?int $clamp = 5, ...)` | Truncated text |

```php
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Components\Cells\Currency;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Components\Cells\Percentage;

TD::make('created_at', __('Registered'))
    ->usingComponent(DateTimeSplit::class)->align(TD::ALIGN_RIGHT)->sort();
TD::make('total', __('Total'))->usingComponent(Currency::class, before: 'lei ')
    ->align(TD::ALIGN_RIGHT);
TD::make('active', __('Active'))
    ->usingComponent(Boolean::class, true: __('Yes'), false: __('No'));
TD::make('coverage', __('Coverage'))
    ->usingComponent(Percentage::class, decimals: 1);
```

Three ways to bind a component — pick by what the component needs:

| Method | Receives | Use when |
|---|---|---|
| `->component(Foo::class)` | Whole row model | Component reads several attributes |
| `->component(Foo::class, value: fn ($row) => ...)` | Closure result | Nullable dates, computed fallbacks |
| `->usingComponent(Foo::class, ...)` | Cell value only | Simple formatting (dates, numbers, currency) |

Nullable dates need care: Carbon parses `null` as "now", so a nullable
`finished_at` would show today. Compute the fallback explicitly:

```php
use App\Models\Visit;

TD::make('finished_at', __('Finished'))
    ->component(DateTimeSplit::class,
        value: fn (Visit $v) => $v->finished_at ?? $v->started_at);
```

Return a blank yourself when no fallback makes sense — guard inside `render()`
instead of using a component.

## Empty states and table options

Override these protected methods on the `Table` subclass (`striped()`,
`compact()`, `hoverable()` return bool; `onEachSide()` sets paginator link
density; `iconNotFound()` / `textNotFound()` / `subNotFound()` shape the
empty state):

```php
protected function striped(): bool
{
    return true;
}

protected function textNotFound(): string
{
    return __('There are no records in this view');
}

protected function onEachSide(): int
{
    return 3;
}
```

| Option | Effect |
|---|---|
| `iconNotFound()` / `textNotFound()` / `subNotFound()` | Empty-table icon, heading, subtext |
| `striped()` | Zebra-striping |
| `compact()` | Reduced row padding |
| `hoverable()` | Row highlight on hover |
| `onEachSide()` | Paginator link density |

To hide the column-visibility picker entirely, mark **every** column
`->cantHide()`… in practice: if all columns forbid hiding, the picker button
has nothing to offer. Conversely, `->defaultHidden()` on rarely used columns
(thumbnails, timestamps) keeps wide tables usable.

### Summary row

`total()` appends a bottom row. Values come from `query()` keys, not from
`$target` — return them alongside the paginated data. Use `colspan()` to span
label cells.

## This project (medical-orchid)

- **25 rows per list page.** Every list `query()` ends with `->paginate(25)`;
  detail-tab tables use `->paginate(10)` with a "view all" link to the full list.
- **Scope before paginate.** Apply role scoping (`organization_id`, assigned
  clinics/pharmacy, own patients) inside `query()` or an always-on `Filter`
  (`parameters(): []`) before `->filters()->paginate()` so counts and pages match
  what the user may see.
- **Whitelist every sortable/filterable column.** Legacy tables have dozens of
  columns; only expose curated `allowedSorts` / `allowedFilters` entries used by
  an actual `TD`. Unlisted columns silently ignore `?sort=` / `?filter=`.
- **Guard nullable legacy columns in `render()`.** Legacy rows contain many
  `NULL`s — use `?? '—'` and `e()` in every closure, and compute explicit
  fallbacks for `DateTimeSplit` (Carbon renders `null` as now).
- **Filters in en + ro.** `name()`, `title()`, `placeholder()`, and `value()`
  strings go through `__()`; `Select` options map to translated labels.
- **Detail screens paginate tabs independently.** Patient/Appointment/Visit/Invoice
  detail tabs each run their own `->paginate(10)` query behind `?tab=`; heavy
  tab tables load via `async` screen methods.
- **Row actions respect roles.** Receptionists see Cabinet + Facturare rows only;
  hide or disable `Button::make(...)->method('delete')` columns with `canSee()`
  or role checks rather than relying on menu gating alone.

## Anti-patterns

- Calling `->sortable()` or `->searchable()` on `TD` — neither method exists;
  use `->sort()` plus `allowedSorts`, and presenter search for global search.
- Passing a non-paginated collection (`->get()`, `->all()`) as table target —
  breaks pagination, sorting links, and memory on large legacy tables.
- Returning unescaped strings from `render()` — output is raw HTML by design;
  always `e()` model attributes or return action/component objects.
- Rendering nullable legacy dates with `usingComponent(DateTimeSplit::class)`
  directly — `null` displays as the current time; use `component(..., value:)`
  with an explicit fallback.
- Declaring `allowedFilters` without handler classes, or expecting related-model
  columns to sort/filter automatically — both silently do nothing.
- Setting the field name inside `TD::filter(Input::make('email'))` — the column
  name is injected automatically; a hardcoded name binds the wrong key.
- Building filter UIs with Livewire/Filament patterns (reactive properties,
  `->searchable()` selects) instead of `Filter` + `Selection` + `Filterable`.

## Reference

- Table layout: `https://orchid.software/en/docs/table`
- Cell types: `https://orchid.software/en/docs/cell-types`
- Eloquent filters: `https://orchid.software/en/docs/filters`
- Sort/filter quickstart: `https://orchid.software/en/docs/quickstart-sort-filter-table`
- Global search (presenters): `https://orchid.software/en/docs/global-search`
- Source in a real app (`vendor/orchid/platform/src/...`):
  `Screen/TD.php`, `Screen/Cell.php`, `Screen/Layouts/Table.php`,
  `Screen/Layouts/Selection.php`, `Screen/Components/Cells/*.php`,
  `Filters/Filter.php`, `Filters/Filterable.php`, `Filters/Types/*.php`
