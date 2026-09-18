---
name: orchid-layouts
description: "Compose Orchid screens with split/columns/tabs/blocks, legends, cards, metrics, charts and view templates. Activate when working with Layout classes, Sight lists, Chartable metrics, Blade view layouts, or icons. Do not use for form fields, table columns, or screen routing."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: layout, split, tabs, legend, sight, card, metric, chart, view template, wrapper, block, columns, accordion, icons
  role: specialist
  scope: laravel
  related-skills: orchid-screens, orchid-fields, orchid-tables, orchid-actions
---

# Orchid Layouts, Charts, Metrics and Icons

Orchid 14.x on Laravel 10–13. A Screen's `layout()` returns `Layout` objects —
anonymous instances from the `Orchid\Support\Facades\Layout` factory or small
subclasses you write. This skill covers everything that is *not* a form field,
table column, or command-bar action: composition, legends, display elements,
Blade templates, charts, and icons.

## Version notes

Mined from `upgrade.md` (grep `layout|chart|legend|metric|icon`):

- **Icons replaced in v14.** Built-in set is now Bootstrap Icons (`bs.` prefix,
  e.g. `->icon('bs.gear')`). The old set is opt-in: `"orchid/icons": "^2.0"` +
  `'orc' => \Orchid\IconPack\Path::getFolder()` in `config/orchid.php`.
- **Metrics short syntax (v10+).** `Layout::metrics(['Label' => 'query.key'])`
  with `value`/`diff` arrays replaced a dedicated metric class.
- **Component params.** `Cell::component(Class::class, [...])` takes an
  associative params array; shared by `TD` and `Sight`.

## Layout inventory

All classes live in `Orchid\Screen\Layouts` (`src/Screen/Layouts/`). The factory
is `Orchid\Support\Facades\Layout`, backed by `Orchid\Screen\LayoutFactory`.

| Class | Factory | Purpose + key API |
|---|---|---|
| `Rows` | `Layout::rows([...fields])` | Vertical stack of form fields. See orchid-fields. |
| `Table` | `Layout::table($target, [...TD])` | Data table. See orchid-tables. |
| `Split` | `Layout::split([...])` | Two sections side by side; `ratio()`, `reverseOnPhone()` |
| `Columns` | `Layout::columns([...])` | N equal-width columns side by side |
| `Tabs` | `Layout::tabs([...])` | Tabbed groups; `activeTab($name)` |
| `Accordion` | `Layout::accordion([...])` | Collapsible panels, same array shape as tabs |
| `Block` | `Layout::block(...)` | Titled card section; `title()`, `description()`, `commands()`, `vertical()` |
| `Legend` | `Layout::legend($target, [...Sight])` | Attribute list for one record |
| `Sortable` | `Layout::sortable($target, [...Sight])` | Drag-and-drop ordering of a collection |
| `Card` | *(none — `new Card($target, [...actions])`)* | Small content card from a `Cardable` object |
| `Persona` | *(none — `new Persona($target)`)* | Avatar + name + position from a `Personable` object |
| `Facepile` | *(none — `new Facepile($target)`)* | Horizontal avatar stack from `Personable[]` |
| `Metric` | `Layout::metrics([...labels])` | KPI numbers; `->title()` |
| `Chart` | `Layout::chart($t, $title)` or subclass `::make()` | Bar/line/pie charts; see Charts section |
| `View` | `Layout::view('blade.name', [...])` | Render any Blade template with query data |
| `Component` | `Layout::component(Hello::class)` | Render a Blade component with query data |
| `Wrapper` | `Layout::wrapper('tpl', [...slots])` | Custom Blade shell with layout slots |
| `Browsing` | `Layout::browsing($url)` | Iframe embed; chainable iframe attributes |
| `Blank` | `Layout::blank([...])` | Unstyled passthrough container |
| `Modal` | `Layout::modal($key, ...)` | Modal dialog. See orchid-actions. |
| `Listener` | *(special constructor)* | Reactive zone. See orchid-actions. |
| `Selection` | `Layout::selection([...filters])` | Filter bar. See orchid-tables. |
| `TabMenu` | *(subclass + artisan `orchid:tab-menu`)* | Cross-screen tab-style navigation |

Verify the facade before trusting memory:

```bash
grep -n "@method static" /tmp/orchid-platform/src/Support/Facades/Layout.php
```

> No `Layout::card()`, no `LineChart`/`BarChart` classes (charts are `Chart`
> subclasses with `$type`), no badge/title/description layouts — section
> headings come from `Block::title()/description()`.

## Composition layouts

### Split: two sections with a ratio

```php
use Orchid\Support\Facades\Layout;

public function layout(): iterable
{
    return [
        Layout::split([
            Layout::legend('patient', [/* ... */]),
            Layout::view('admin.patient-sidebar'),
        ])->ratio('70/30')->reverseOnPhone(),
    ];
}
```

- `Layout::split()` takes (normally two) layouts. `ratio()` accepts exactly
  `20/80` through `80/20` (`50/50` default) — anything else throws.
  `reverseOnPhone()` flips order on small screens. Unlike `Columns` (N equal
  sections), split has two tunable sections.

### Columns: N equal sections

`Layout::columns([UserProfileLayout::class, ContactLayout::class])` divides the
row into N equal-width sections (class names or layout objects). For unequal
widths use `Split` with `ratio()`.

### Block: titled card section

`Block` renders children inside a card with header, description, and action
buttons (`Block.php`): `Layout::block([PersonalDataRows::class])
->title('Personal data')->description('...')->commands([...])`.

- `title(string)`, `description($textOrView)`, `vertical()`,
  `commands($actions)` for section-local header buttons (same `Action` objects
  as screen `commandBar()`).

### Tabs: switchable groups

Keys are the tab headers; values are layout arrays or layout class names.

```php
use Orchid\Support\Facades\Layout;

public function layout(): iterable
{
    return [
        Layout::tabs([
            'Profile' => ProfileRows::class,
            'Visits'  => [Layout::table('visits', [/* TD columns */])],
        ])->activeTab('Profile'),
    ];
}
```

- Source: `LayoutFactory::tabs(array $layouts)`, `Tabs::activeTab($name)` —
  the value must match a tab key exactly.
- **Deep-linkable tabs (`?tab=`) are a project-level pattern, not built-in
  Orchid behavior.** The tabs Blade partial keeps state client-side. To honor
  `?tab=Visits`, read the query parameter in the Screen and pass it through:

```php
use Orchid\Support\Facades\Layout;

public function layout(): iterable
{
    return [
        Layout::tabs([
            'Profile' => ProfileRows::class,
            'Visits'  => VisitsTableLayout::class,
        ])->activeTab(request()->query('tab', 'Profile')),
    ];
}
```

Validate `$tab` against the known keys and fall back to the first tab when it
does not match; never pass raw user input anywhere else.

`Layout::accordion()` takes the identical key → layouts array but renders
collapsible panels instead of switchable tabs — use it for long read-only
sections (allergies, chronic conditions) where seeing headers at a glance
matters more than switching.

### Grouping layers: TabMenu and Sortable

`TabMenu` navigates *between screens* (looks like tabs, behaves like a menu).
Generate with `php artisan orchid:tab-menu ClinicSubnav`, implement
`navigations(): iterable` returning `Menu` objects (same API as the main menu —
see orchid-screens), then return the class from `layout()`.

`Sortable` gives drag-and-drop ordering for a collection. The model uses the
`Orchid\Platform\Concerns\Sortable` trait (default column `order`, overridable
via `getSortColumnName()`), the query returns `Model::sorted()->get()`, and the
layout is `Layout::sortable('key', [Sight::make('title')])` — same `Sight`
columns as a legend.

## Legend and Sight

`Layout::legend($target, [...Sight])` renders one record from `query()` as a
definition list (`Legend.php`; `Sight` extends `Cell`).

```php
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

public function query(): iterable
{
    return [
        'patient' => Patient::with(['organization'])->findOrFail($this->patientId),
    ];
}

public function layout(): iterable
{
    return [
        Layout::legend('patient', [
            Sight::make('full_name', 'Patient'),
            Sight::make('cnp', 'CNP'),
            Sight::make('organization', 'Organization')
                ->render(fn ($patient) => $patient->organization?->name ?? '—'),
        ])->title('Patient file'),
    ];
}
```

`Sight` API (inherited from `Cell`, verified in `src/Screen/Cell.php`):

| Method | Purpose |
|---|---|
| `Sight::make($name, $title = null)` | `$title` defaults to `Str::title($name)` |
| `->popover($text)` | Help icon with tooltip in the `<dt>` |
| `->render(Closure $fn)` | Full control; closure receives the whole record (`$source`), return HTML string |
| `->component($class, ...$params)` | Render a Blade component for the value |
| `->asComponent($class, [...])` / `->usingComponent(...)` | Pass only the cell value to the component |
| `->canSee($bool)` | Conditionally hide the row (from `CanSee` trait) |

Rules:

- The legend `$target` must match a `query()` key.
- Inside `render()`, the closure receives the full record — guard nullable
  relations with `?->` and a `?? '—'` fallback.
- For repeated formatting across screens, prefer a Blade component via
  `->component()` over copy-pasted closures.

## Display elements: cards, persona, metrics

### Cards (`Cardable` + `new Card`)

Cards render one `Cardable` object (title, HTML description, image, accent
color). No `Layout::card()` factory — instantiate directly
(`Card.php`, `Contracts/Cardable.php`).

```php
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Card;

public function query(): iterable
{
    return [
        // Presenter implements Orchid\Screen\Contracts\Cardable
        // (title(), description(), image(), color()) — see orchid-models.
        'notice' => StockAlert::first()->presenter(),
    ];
}

public function layout(): iterable
{
    return [
        new Card('notice', [
            Button::make('Review')->method('reviewStock')->icon('bs.box'),
        ]),
    ];
}
```

- The second constructor argument is an array of actions rendered like a
  `commandBar()`. `description()` may return rendered Blade; `image()` is an
  absolute URL or `null`; `color()` returns `?Color` (`INFO()`, `SUCCESS()`,
  `WARNING()`, `DANGER()`) or `null` for no accent.

### Persona and Facepile (`Personable`)

For people: `new Persona($target)` renders avatar + title + subtitle + link;
`new Facepile($target)` renders an avatar stack from a collection. Both consume
`Contracts\Personable` (`title()`, `subTitle()`, `url()`, `image()`), normally
via a presenter:

```php
use Orchid\Screen\Layouts\Facepile;
use Orchid\Screen\Layouts\Persona;

// Presenters implement Personable (title(), subTitle(), url(), image()).
public function query(): iterable
{
    return [
        'doctor'   => Doctor::find(1)->presenter(),
        'careTeam' => Doctor::limit(8)->get()->map->presenter(),
    ];
}

public function layout(): iterable
{
    return [new Persona('doctor'), new Facepile('careTeam')];
}
```

Use Persona for "attending doctor" headers, Facepile for care-team strips.

### Metrics (`Layout::metrics`)

`Layout::metrics()` renders KPI numbers (short syntax only — no per-metric
class; `Metric.php`).

```php
use Orchid\Support\Facades\Layout;

public function query(): iterable
{
    return [
        'metrics' => [
            'appointments' => ['value' => number_format(128), 'diff' => 4.2],
            'revenue'      => number_format(45210),
        ],
    ];
}

public function layout(): iterable
{
    return [
        Layout::metrics([
            'Appointments today' => 'metrics.appointments',
            'Revenue (RON)'      => 'metrics.revenue',
        ])->title('Clinic overview'),
    ];
}
```

Keys are displayed labels, values are dot-notation query paths; each value is
a scalar or `['value' => ..., 'diff' => ...]` (trend %). Metrics show numbers —
pair with a `Chart` for trends.

## Custom templates: view, component, wrapper, browsing

### `Layout::view()` — arbitrary Blade

Source: `LayoutFactory::view(string $view, $data = [])`. The layout merges its
`$data` with the full query repository — everything from `query()` is a Blade
variable. Extra static data via the second argument (query wins on collision).

```php
use Orchid\Support\Facades\Layout;

public function query(): iterable
{
    return ['patient' => Patient::findOrFail($this->patientId)];
}

public function layout(): iterable
{
    // resources/views/admin/patients/header.blade.php; $patient available.
    return [Layout::view('admin.patients.header')];
}
```

```blade
{{-- resources/views/admin/patients/header.blade.php --}}
<div class="d-flex align-items-center gap-3">
    <h4 class="mb-0">{{ $patient->full_name }}</h4>
    <span class="text-muted">{{ $patient->cnp }}</span>
</div>
```

- Use for detail-screen headers, empty states, and read-only summaries.
  Templates live in `resources/views/...` (dot notation). Keep business logic
  out; prepare data in `query()`.

### `Layout::component()` — Blade component

```php
use App\View\Components\StatusBadge;
use Orchid\Support\Facades\Layout;

public function layout(): iterable
{
    // query() key 'status' is matched to __construct(string $status) by name.
    return [Layout::component(StatusBadge::class)];
}
```

Create components with `php artisan make:component StatusBadge`.

### `Layout::wrapper()` — custom shell with layout slots

Wrapper renders your Blade template and injects *built layouts* as variables
(`LayoutFactory::wrapper(string $template, array $layouts)`).

```php
use Orchid\Support\Facades\Layout;

// resources/views/admin/patients/two-pane.blade.php receives $main/$side.
public function layout(): iterable
{
    return [
        Layout::wrapper('admin.patients.two-pane', [
            'main' => [PatientRows::class, VisitsTableLayout::class],
            'side' => PatientLegendLayout::class,
        ]),
    ];
}
```

Slot shape is fixed by `Wrapper::build()`: a slot holding **one** layout
arrives as a renderable **string** (`{!! $side !!}`); a slot holding an
**array** of layouts arrives as an **array of strings** — loop it, never echo
it (and never foreach a single slot); query data merges in like `Layout::view()`.

```blade
<div class="row">
    <div class="col-md-8">@foreach ($main as $row){!! $row !!}@endforeach</div>
    <div class="col-md-4">{!! $side !!}</div>
</div>
```

### `Layout::browsing()` — iframe embed

`Layout::browsing('https://telemedicine.example/room/42')` embeds an external
page. Chainable iframe attributes: `->allow()`, `->loading()`, `->csp()`,
`->name()`, `->referrerpolicy()`, `->sandbox()`, `->src()`, `->srcdoc()`.
Reserve for genuinely external pages; internal content belongs in
`Layout::view()`.

## Charts and the Chartable trait

Charts are `Screen\Layouts\Chart` subclasses with a `$type` property — no
separate `LineChart`/`BarChart` classes. Generate with
`php artisan orchid:chart AppointmentsChart` (lands in `app/Orchid/Layouts/`).

### Declaring a chart

```php
namespace App\Orchid\Layouts;

use Orchid\Screen\Layouts\Chart;

class AppointmentsChart extends Chart
{
    protected $type = 'bar';   // bar | line | pie | percentage | axis-mixed
    protected $height = 250;
    protected $export = true;

    protected function markers(): ?array
    {
        return [['label' => 'Daily target', 'value' => 40]];
    }
}
```

- `$type` options (also as `TYPE_BAR`, `TYPE_LINE`, `TYPE_PIE`,
  `TYPE_PERCENTAGE`, `TYPE_AXIS_MIXED` constants): `bar`, `line`, `pie`,
  `percentage`, `axis-mixed`.
- `$height` is pixels. `$export = true` shows an SVG-export button.
- `markers()` draws dashed Y-axis lines — use for targets/averages.
  Rarely-touched tuning: `$colors`, `$maxSlices`, `$valuesOverPoints`,
  `$barOptions`, `$lineOptions`, `$axisOptions`.

### Feeding data: series format

A chart target is an array of series, each with `labels`, `name`, `values`:

```php
use App\Orchid\Layouts\AppointmentsChart;

public function query(): iterable
{
    return [
        'appointments' => [
            [
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                'name'   => 'Confirmed',
                'values' => [22, 31, 27, 35, 29],
            ],
        ],
    ];
}

public function layout(): iterable
{
    return [
        AppointmentsChart::make('appointments', 'Appointments this week')
            ->description('Confirmed per weekday.'),
    ];
}
```

`::make($target, $title)` is the standard entry point; `->description()`,
`->height()`, `->type()`, `->export()` override class defaults per usage
(`Layout::chart($target, $title)` is the anonymous equivalent).

### Feeding data: Eloquent via Chartable

Add the `Orchid\Metrics\Chartable` trait to the model. Verified scopes:
`countByDays()`, `averageByDays($col)`, `sumByDays($col)`, `maxByDays($col)`,
`minByDays($col)`, `countForGroup($col)`. (`valuesByDays()` is deprecated.)

```php
use App\Models\Appointment;
use App\Models\MedicalVisit;
use App\Orchid\Layouts\AppointmentsChart;

// Models use the Orchid\Metrics\Chartable trait.
public function query(): iterable
{
    return [
        'workload' => [
            Appointment::countByDays()->toChart('Appointments'),
            MedicalVisit::countByDays()->toChart('Visits'),
        ],
        'byStatus' => Appointment::countForGroup('status')->toChart(),
    ];
}

public function layout(): iterable
{
    return [
        AppointmentsChart::make('workload', 'New workload'),
        StatusPieChart::make('byStatus', 'Appointments by status'),
    ];
}
```

- `countByDays($start = null, $stop = null, $dateColumn = null)` defaults to the
  last month on `created_at`, filling missing days with zero.
- `countForGroup('status')->toChart()` builds pie-ready series; relabel with a
  closure: `->toChart(fn (bool $v) => $v ? 'Yes' : 'No')`.
- `TimeCollection::toChart($name)` / `GroupCollection::toChart()` return the
  `labels/name/values` shape; `showDaysOfWeek()` relabels with weekday names.
- Scope chart queries by organization/clinic/pharmacy first — unscoped
  aggregates leak cross-tenant totals onto dashboards.

## Icons

Orchid 14 uses Bootstrap Icons with the `bs.` prefix, resolved from
`config/orchid.php` `'icons'` map. Source docs: `icons.md`, `orchid-icons.md`.

```php
use Orchid\Screen\Actions\Menu;

Menu::make('Patients')->icon('bs.people')->route('platform.patients');
```

```blade
<x-orchid-icon path="bs.heart-pulse" class="me-1" width="1.2em" height="1.2em" />
```

Rules:

- `->icon('bs.<kebab-name>')` on any `Action`. Browse names at
  `https://icons.getbootstrap.com/`.
- `<x-orchid-icon path="..." />` (per `icons.md`, docs-sourced — not separately
  source-verified) in any Blade view, including `Layout::view()` templates;
  `class`/`width`/`height` pass through to the SVG.
- Custom sets: drop SVGs under `resources/icons/<set>/`, register the prefix
  in `config/orchid.php` (`'fa' => resource_path('icons/fontawesome')`).
- Legacy `orc.` set: requires `"orchid/icons": "^2.0"` plus
  `'orc' => \Orchid\IconPack\Path::getFolder()`. Unmaintained — use only while
  migrating old screens to `bs.`.

## This project (medical-orchid)

- **Detail screens** use the header + tabs recipe: `Layout::view()` for the
  patient/appointment/visit/invoice header, `commandBar()` for primary actions,
  `Layout::tabs()` with `->activeTab(request()->query('tab', $default))` for
  deep-linkable sections (10/page tables inside tabs with "view all" links).
- **Dashboards** (admin, doctor, pharmacist) compose `Layout::metrics()` KPI
  strips + `Chart` subclasses fed by `Chartable` scopes + `Card`/`Persona`
  highlights — always scope queries (`organization_id`, assigned clinics or
  pharmacy) before aggregating.
- **Forms stay in `Layout::rows()`** stacked vertically per project rule; use
  `Split`/`Columns` only for read-only detail layouts, never to build
  multi-column forms.
- **Legends for "Profile" tabs**: `Sight` rows for identifiers (CNP), relations
  (`organization?->name`), status partials. Guard every nullable with
  `?->` / `?? '—'`.
- **Romanian + English strings**: wrap tab keys, block titles, metric labels,
  and chart titles/descriptions in `__()`; add both `lang/en` and `lang/ro`
  entries.
- **Blade locations**: headers in `resources/views/admin/<entity>/header.blade.php`;
  charts in `app/Orchid/Layouts/`; dashboards in `app/Orchid/Screens/`.
- Verify each layout visually at `https://med-orch.test` after implementation;
  keep code Pint-clean.

## Anti-patterns

- Calling `Layout::card()` — it does not exist. Use `new Card($target, [...])`.
- Importing `LineChart`/`BarChart` components — they do not exist. Extend
  `Screen\Layouts\Chart` and set `$type`.
- Writing `Layout::badge()`/`Layout::title()` — none exist. Headings come from
  `Block::title()/description()`.
- Passing an unvalidated `request()->query('tab')` straight into `activeTab()`
  without checking it against the known tab keys.
- Returning unscoped aggregates (`Model::countByDays()` without an
  organization/clinic constraint) on role dashboards — leaks cross-tenant data.
- Accessing `$patient->organization->name` in a `Sight::render()` closure
  without `?->`; missing relations must render `—`, not throw.
- Reaching for Filament widgets or Livewire components for tabs/charts —
  Orchid layouts, `Chartable`, and `Listener` (see orchid-actions) cover these.
- Putting write logic or queries with side effects inside `Layout::view()`
  Blade templates — prepare all data in `query()`.

## Reference

- Docs: `https://orchid.software/en/docs/grouping`,
  `/en/docs/legend`, `/en/docs/display-elements`, `/en/docs/custom-template`,
  `/en/docs/charts`, `/en/docs/icons`, `/en/docs/orchid-icons`
- Source in a real app: `vendor/orchid/platform/src/Screen/Layouts/`,
  `.../Screen/LayoutFactory.php`, `.../Screen/Sight.php`, `.../Screen/Cell.php`,
  `.../Screen/Contracts/{Cardable,Personable}.php`,
  `.../Metrics/Chartable.php`, `.../Support/Facades/Layout.php`
- Sibling skills: rows/form composition → **orchid-fields**; `TD` tables,
  sorting, filters → **orchid-tables**; `commandBar()`, modals, listeners,
  alerts → **orchid-actions**; presenters, attachments → **orchid-models**.
