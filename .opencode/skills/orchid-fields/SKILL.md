---
name: orchid-fields
description: "Build Orchid 14.x admin forms with the field catalog (Input, Select, DateTimer, Attach, Matrix) and the shared field API. Activate when working with form fields, Layout::rows() forms, save/validation flow, custom fields, or the form Builder. Do not use for table columns, row actions, modals, or permissions."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: fields, form, input, select, validation, rows, custom field, builder, attach, matrix, datepicker
  role: specialist
  scope: laravel
  related-skills: orchid-screens, orchid-layouts, orchid-models, orchid-actions
---

# Orchid Fields

Form fields are the building blocks of every Orchid create/edit screen. Each field is a PHP class under `Orchid\Screen\Fields\*` extending `Orchid\Screen\Field`, rendered inside `Layout::rows()`. This skill covers the verified field catalog, the shared field API, the canonical save/validation flow, custom fields, and the standalone form `Builder`. Orchid 14.x, Laravel 10–13.

## Version notes

Mined from `upgrade.md` (field-relevant only):

- `select2` was replaced with the `tom-select` package (jQuery dependency removed). Custom JS listening to select2-specific events no longer works.
- Menus are now `Field` classes with unified syntax alongside form fields (shared `Field` base and traits).
- `Fields`/`Layouts`/`TD` share a common visibility trait — `canSee()` works uniformly across all three.

## Field class inventory

Source: `ls src/Screen/Fields/` in `orchid/platform`. Every class below was verified to exist. Namespace for all: `Orchid\Screen\Fields\*`.

| Class | Purpose |
|---|---|
| `Input` | Single-line input plus HTML5 types (`text`, `email`, `number`, `url`, `color`, `range`, `file`, `hidden`); masks via `mask()` |
| `TextArea` | Multi-line text input with configurable `rows()` |
| `Password` | Masked input for secrets; `revealable()` toggles a show/hide eye icon |
| `Select` | Dropdown / multi-select; static `options()` or model/query sources (`fromModel`, `fromQuery`, `fromEnum`) |
| `CheckBox` | Single boolean checkbox; `sendTrueOrFalse()` forces a `false` payload when unchecked |
| `Switcher` | Toggle-style boolean switch; same `sendTrueOrFalse()` semantics as `CheckBox` |
| `Radio` | Single-choice radio group from an `options()` array |
| `RadioButtons` | Segmented single-choice button group from an `options()` array |
| `DateTimer` | Date and/or time picker (flatpickr); `enableTime()`, `noCalendar()`, `range()`, `format()`, `multiple()` |
| `DateRange` | Date-range picker returning `['start' => ..., 'end' => ...]` |
| `Range` | Numeric slider (`min` / `max` / `step`) |
| `NumberRange` | Min/max numeric pair, primarily for table filters; result is `['min' => ..., 'max' => ...]` |
| `Attach` | File upload field with grouping, sorting, `multiple()`, storage backends |
| `Picture` | Single-image upload; `targetId()` / `targetUrl()` / `targetRelativeUrl()` control the stored value |
| `Cropper` | `Picture` plus client-side crop (`width` / `height`, `minCanvas` / `maxCanvas`, `maxFileSize`) |
| `Code` | Code editor with highlighting (`Code::JS`, `Code::CSS`, `Code::MARKUP`, `Code::CLIKE`); `lineNumbers()`, `height()` |
| `Quill` | Quill rich-text editor; `toolbar([...])` selects control groups |
| `Markdown` | Markdown editor; `SimpleMDE` is a deprecated alias — use `Markdown` |
| `Matrix` | Editable key/column grid stored as JSON; `columns()`, `fields()`, `maxRows()` |
| `Group` | Lays out several fields on one line; `autoWidth()`, `fullWidth()`, `widthColumns()`, alignment methods |
| `Map` | Location picker; `zoom()`, `height()` |
| `TimeZone` | Time-zone dropdown; `listIdentifiers()` narrows the PHP `DateTimeZone` set |
| `UTM` | Captures UTM campaign/source/medium marketing parameters |
| `Label` | Read-only text or heading; no editable value |
| `ViewField` | Renders an arbitrary Blade view via `view('path.to.view')` |
| `SimpleMDE` | Deprecated alias of `Markdown`; do not use in new code |

Helper (not a field): `Fields\Support\ChoicePayload` supports select-choice payloads.

> Omitted as unverifiable in this source tree: `Relation`, `Upload`, `Repeatable`, `DatePicker`/`TimePicker` as standalone classes, `MultiSelect`. The docs site references `Relation`/`Upload`, but no such classes exist under `src/Screen/Fields/` here. Use `Select::fromModel()` for model-driven dropdowns, `DateTimer` for date/time picking, and `Attach`/`Picture` for uploads.

## Shared field API

All fields extend `Orchid\Screen\Field` (`src/Screen/Field.php`). Most per-field setters (`title()`, `required()`, `placeholder()`, …) are magic setters handled by `Field::__call()` → `set($method, $value)`; each field declares its supported setters in `@method` annotations — only use setters annotated on that class.

| Method | Source | Effect |
|---|---|---|
| `::make(?string $name)` | `Concerns\Makeable` | Creates the field; `$name` is the form key, dot-notation allowed (`post.title`) |
| `title(string $v)` | magic via `set()` | Label shown above/beside the field |
| `help(string $v)` | magic via `set()` | Hint text under the field |
| `popover(string $v)` | magic via `set()` | Tooltip text opened by the user |
| `placeholder(string $v)` | magic via `set()` | Placeholder inside the input |
| `value(mixed $v)` | `Field::value()` | Default/display value; screen query data overrides it at render |
| `required(bool $v = true)` | magic via `set()` | Marks required (asterisk + HTML attribute); pair with server-side `$request->validate()` |
| `readonly($v = true)` | magic via `set()` | Renders read-only |
| `disabled($v = true)` | magic via `set()` | Renders disabled (value not submitted) |
| `canSee(bool $v)` | `Concerns\CanSee` | Hides the field entirely when `false` |
| `set(string $key, $v)` | `Field::set()` | Low-level attribute setter backing every magic method |
| `get(string $key)` / `has(string $key)` | `Field` | Read back an attribute (useful in reusable row layouts) |
| `vertical()` / `horizontal()` | `Field` | Label above field vs beside it |
| `translatable($v)` | `Concerns\HasTranslations` | Enables per-locale input for the field |
| `multiple()` | `Concerns\Multipliable` (Select, Attach) / `DateTimer::multiple()` | Multi-value mode; appends `[]` to the HTML name |
| `addBeforeRender(Closure)` | `Field` | Hook mutating attributes just before rendering |
| `requiredWithoutAsterisk(bool)` | `Field` | Required behavior without the visual asterisk |
| `hr()` | `Field` | Renders a separator after the field |

Data binding: at render time the screen query (`Repository`) fills each field by its `name`. Explicit `->value()` is only a fallback — query data wins. Old input repopulates automatically via `getOldValue()`.

## Input

```php
use Orchid\Screen\Fields\Input;

Input::make('patient.first_name')
    ->title('First name')
    ->placeholder('e.g. Maria')
    ->help('As printed on the ID card.')
    ->required()
    ->maxlength(100);

Input::make('patient.email')->type('email');
Input::make('patient.phone')
    ->mask('(999) 999-9999')
    ->title('Phone number');
```

`type()` accepts standard HTML input types (`text`, `email`, `number`, `url`, `color`, `range`, `file`, `hidden`, `search`, `tel`). `mask()` takes a string pattern or an options array (`mask`, `numericInput`, `alias`, `prefix`, `groupSeparator`, `digitsOptional`).

## TextArea and Password

```php
use Orchid\Screen\Fields\Password;
use Orchid\Screen\Fields\TextArea;

TextArea::make('visit.notes')
    ->title('Doctor notes')
    ->rows(5)
    ->required()
    ->help('What did the patient complain about?');

Password::make('user.password')
    ->title('Password')
    ->placeholder('Minimum 8 characters.')
    ->required()
    ->revealable();
```

## Select

Static options, model sources, and enum sources (all verified on `Select`):

```php
use App\Models\Doctor;
use Orchid\Screen\Fields\Select;

Select::make('appointment.status')
    ->options(['scheduled' => 'Scheduled', 'done' => 'Done', 'cancelled' => 'Cancelled'])
    ->empty('Not selected')
    ->title('Status');

Select::make('appointment.doctor_id')
    ->fromModel(Doctor::class, 'name')
    ->empty('Select a doctor')
    ->title('Doctor');

Select::make('appointment.doctor_id')
    ->fromQuery(Doctor::where('active', 1), 'name')
    ->title('Active doctor');

Select::make('appointment.doctor_id')
    ->fromModel(Doctor::class, 'name')
    ->searchColumns('name', 'email')
    ->applyScope('active')
    ->title('Doctor');

Select::make('appointment.tag_ids')
    ->fromModel(Tag::class, 'name')
    ->multiple()
    ->title('Tags');
```

Key option methods: `empty($label, $key = '')`, `fromEnum(Enum::class)`, `allowAdd()` / `allowCreate()` (user-typed values), `allowEmpty()`, `lazy($chunk)` (HTTP-chunked loading with `fromModel`), `max($number)`, `displayAppend('accessor')` for computed labels, `taggable()`.

## DateTimer and DateRange

`DateTimer` is the single date/time field (no separate `DatePicker` class in source):

```php
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\DateTimer;

DateTimer::make('appointment.starts_at')
    ->title('Starts at')
    ->format('Y-m-d H:i')
    ->enableTime()
    ->format24hr()
    ->required();

DateTimer::make('appointment.visit_date')
    ->title('Visit date')
    ->format('Y-m-d')
    ->allowInput();

DateTimer::make('appointment.slot')
    ->title('Slot')
    ->noCalendar()
    ->format('H:i');

DateTimer::make('report.period')
    ->title('Period')
    ->format('Y-m-d H:i')
    ->enableTime()
    ->range();

DateRange::make('report.period')
    ->title('Period')
    ->value(['start' => now()->subDays(30), 'end' => now()]);
```

`DateRange` always returns `['start' => ..., 'end' => ...]`. Also verified on `DateTimer`: `serverFormat()`, `allowEmpty()`, `multiple()`, `min()`/`max()`, `available()`/`unavailable()`, `minuteIncrement()`, `hourIncrement()`, `inline()`, `disableMobile()`, `withQuickDates([...])`.

## Uploads: Attach, Picture, Cropper

Upload handling is split across `Attach` (generic files), `Picture` (single image), and `Cropper` (image + crop). Deep attachment behavior (relations, groups) belongs to `orchid-models`.

```php
use Orchid\Screen\Fields\Attach;
use Orchid\Screen\Fields\Cropper;
use Orchid\Screen\Fields\Picture;

Attach::make('visit.documents')
    ->multiple()
    ->set('maxCount', 5)
    ->maxSize(10)
    ->accept('image/*,application/pdf')
    ->group('documents')
    ->storage('public')
    ->title('Documents');

Picture::make('patient.photo')
    ->targetId()
    ->acceptedFiles('.jpg,.png')
    ->title('Photo');

Cropper::make('patient.photo')
    ->width(500)
    ->height(300)
    ->maxFileSize(2)
    ->targetRelativeUrl()
    ->title('Photo');
```

`Picture` value modes: `targetId()` (attachment id), `targetUrl()` (absolute URL), `targetRelativeUrl()` (relative path). `Attach` also supports `path()`, `uploadUrl()`/`sortUrl()` for custom endpoints, and `errorMaxSizeMessage()`/`errorTypeMessage()` for UX copy. PHP `upload_max_filesize`/`post_max_size` still cap uploads server-side.

## Switcher and CheckBox

```php
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Switcher;

Switcher::make('clinic.is_active')
    ->title('Accepting patients')
    ->sendTrueOrFalse()
    ->help('Unchecked clinics are hidden from scheduling.');

CheckBox::make('patient.consent')
    ->title('Consent')
    ->placeholder('I agree to data processing')
    ->value(1)
    ->sendTrueOrFalse();
```

Unchecked boxes submit nothing by default — `sendTrueOrFalse()` makes them submit `true`/`false`. `CheckBox` also supports `indeterminate()`.

## Radio and RadioButtons

```php
use Orchid\Screen\Fields\Radio;
use Orchid\Screen\Fields\RadioButtons;

Radio::make('appointment.status')
    ->options(['scheduled' => 'Scheduled', 'done' => 'Done', 'cancelled' => 'Cancelled'])
    ->value('scheduled')
    ->title('Status');

RadioButtons::make('appointment.priority')
    ->options(['low' => 'Low', 'normal' => 'Normal', 'urgent' => 'Urgent'])
    ->title('Priority');
```

## Group

`Group::make([...])` puts fields on one line (cross-ref `orchid-layouts` for `Columns`/`Tabs`/`Split`):

```php
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;

Group::make([
    Input::make('patient.first_name')->title('First name'),
    Input::make('patient.last_name')->title('Last name'),
])->autoWidth();
```

Width: `fullWidth()` (default stretch), `autoWidth()` (content-sized), `widthColumns('2fr 1fr')` (CSS grid template, one value per field). Alignment: `alignStart()`, `alignEnd()`, `alignCenter()`, `alignBaseLine()`. `toEnd()` pushes the group to the row end. Stacks vertically on mobile automatically.

## Matrix

JSON-column grid editor. Cells default to textareas; override per column with `fields()`:

```php
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\TextArea;

Matrix::make('invoice.items')
    ->title('Line items')
    ->columns(['Code', 'Quantity', 'Price'])
    ->fields([
        'Code'     => Input::make()->type('text'),
        'Quantity' => Input::make()->type('number'),
        'Price'    => Input::make()->type('number'),
    ])
    ->maxRows(20)
    ->addRowLabel('Add item');
```

`columns()` accepts a plain list or `Label => key` pairs. `removableRows(false)` locks row deletion. Note: cells are cloned client-side, so complex nested fields may misbehave — keep cell fields simple.

## NumberRange, Range, Code

```php
use Orchid\Screen\Fields\Code;
use Orchid\Screen\Fields\NumberRange;
use Orchid\Screen\Fields\Range;

Range::make('survey.pain_level')
    ->min(0)->max(10)->step(1)
    ->title('Pain level (0-10)');

// Primarily a table filter (see orchid-tables); result is ['min' => x, 'max' => y].
NumberRange::make('filter.age');

Code::make('template.body')
    ->language(Code::MARKUP)
    ->lineNumbers()
    ->title('Print template');
```

`Code` language constants: `Code::MARKUP`, `Code::CSS`, `Code::CLIKE`, `Code::JS`.

## Rich text, location, misc

```php
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Fields\Map;
use Orchid\Screen\Fields\Markdown;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\TimeZone;
use Orchid\Screen\Fields\UTM;
use Orchid\Screen\Fields\ViewField;

Quill::make('post.body')
    ->toolbar(['text', 'color', 'header', 'list', 'format', 'media'])
    ->title('Content');

Markdown::make('protocol.notes')->title('Notes');

Map::make('clinic.location')->title('Location')->zoom(12)->height('400px');

TimeZone::make('clinic.timezone')->title('Time zone');

UTM::make('campaign.params')->title('Campaign parameters');

Label::make('hint')->title('Invoices are issued in RON.');

ViewField::make('custom')->view('platform.partials.patient-card');
```

`Quill` toolbar groups: `text`, `color`, `header`, `list`, `format`, `media`. `TimeZone::listIdentifiers(DateTimeZone::EUROPE)` narrows the list. Prefer `Markdown` over the deprecated `SimpleMDE`.

## Composing forms with Layout::rows()

`Layout::rows([...])` (`Orchid\Support\Facades\Layout`, via `LayoutFactory::rows()`) is the canonical form container — fields stack vertically, one per row, except fields wrapped in `Group`. For other containers (`Tabs`, `Columns`, `Split`, `Block`, `Accordion`) see `orchid-layouts`.

```php
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;

public function layout(): array
{
    return [
        Layout::rows([
            Input::make('patient.first_name')->title('First name')->required(),
            Input::make('patient.last_name')->title('Last name')->required(),
            TextArea::make('patient.notes')->title('Notes')->rows(3),
        ]),
    ];
}
```

For reusable forms, extract a `Rows` subclass (`php artisan orchid:rows Appointment`, class in `app/Orchid/Layouts/`) with a `fields(): array` method. Inside it, `$this->query` exposes screen data for conditional fields (`->canSee($this->query->has('x'))`).

## Save and validation flow

The screen owns persistence. Canonical pattern from `quickstart-crud.md`, hardened with validation:

```php
use Illuminate\Http\Request;
use Orchid\Support\Facades\Alert;

public function query(Patient $patient): array
{
    return ['patient' => $patient];
}

public function createOrUpdate(Request $request, Patient $patient)
{
    $data = $request->validate([
        'patient.first_name' => 'required|string|max:100',
        'patient.last_name'  => 'required|string|max:100',
        'patient.email'      => 'nullable|email|max:255',
    ]);

    $patient->fill($data['patient'])->save();

    Alert::info('Patient saved.');

    return redirect()->route('platform.patients.list');
}
```

Rules:

- Name fields with the model prefix (`patient.first_name`) so `$request->get('patient')` maps to `fill()`.
- Always `$request->validate()` in the handler — `required()` on the field is UX only, never a substitute.
- Use route-model binding (`Patient $patient`) matching the `Route::screen('patient/{patient?}', ...)` parameter.
- Redirect after save plus `Alert::info()` (alerts API belongs to `orchid-actions`).
- `AsSource` trait on the model enables dot-notation binding.
- For simple catalogs, full resources can be auto-generated with the orchid/crud package — see the orchid-crud skill.

## Custom fields

When no built-in field fits (special picker, JS widget), extend `Field`:

```bash
php artisan orchid:field EmojiPicker
```

This creates `app/Orchid/Fields/EmojiPicker.php` and `resources/views/orchid/fields/emoji-picker.blade.php` (verified: `Platform\Commands\FieldCommand`, `orchid:field`). Minimal class:

```php
namespace App\Orchid\Fields;

use Orchid\Screen\Field;

class EmojiPicker extends Field
{
    protected $view = 'orchid.fields.emoji-picker';

    protected $attributes = [
        'placeholder' => 'Pick an emoji...',
        'class'       => 'form-control',
    ];

    protected $inlineAttributes = [
        'placeholder',
        'value',
    ];
}
```

Blade view wraps markup in the standard form shell and wires Stimulus:

```blade
@component($typeForm, get_defined_vars())
    <div data-controller="emoji-picker">
        <input {{ $attributes->merge(['data-emoji-picker-target' => 'input']) }}>
    </div>
@endcomponent

<script>
    Orchid.register('emoji-picker', class extends window.Controller {
        connect() { /* init */ }
    });
</script>
```

Build a custom field only when composition (`Group`, `ViewField`, `Matrix`) cannot express it. Keep the PHP side thin; put behavior in the Stimulus controller.

## Form Builder

`Orchid\Screen\Builder` renders fields to HTML outside screens (emails, previews, exports):

```php
use Orchid\Screen\Builder;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Repository;

$builder = new Builder(
    [Input::make('name')->title('Name')],
    new Repository(['name' => 'Maria']),
);

$html = $builder->generateForm();
```

`setLanguage('ro')` / `setPrefix('patient')` scope multilingual or nested names. Dot-notation names (`name.ro`) resolve nested repository data. Verified methods: `generateForm()`, `setLanguage()`, `setPrefix()`, `convertDotToArray()`.

## This project (medical-orchid)

- Compose every create/edit form as a single `Layout::rows()` block, fields stacked vertically per project rule; use `Group` only for genuinely paired inputs (first/last name, min/max).
- Copy legacy models flat into `app/Models/` with `AsSource`; name fields `model.attribute` so `fill($request->get('model'))` works unchanged.
- Validate with `$request->validate()` in every save handler; `required()` on fields is presentation only.
- User-facing `title()`/`help()`/`placeholder()` strings in `en` + `ro` (field `title`/`placeholder`/`help` are auto-translatable attributes).
- Keep upload fields short here — attachment relations, groups, and storage policy live in `orchid-models`.
- Run `./vendor/bin/pint` and click through at `https://med-orch.test` after each form change.

## Anti-patterns

- Using `Relation::make()`, `Upload::make()`, or `Repeatable` — these classes do not exist in this source tree; use `Select::fromModel()`, `Attach`/`Picture`, `Matrix`.
- Using `SimpleMDE` in new code — it is a deprecated alias; use `Markdown`.
- Trusting `required()` / `accept()` / `maxSize()` alone — always re-validate in the handler; client hints are bypassable.
- `disabled()` on a field whose value you need — disabled inputs are not submitted; use `readonly()` instead.
- Forgetting `sendTrueOrFalse()` on `CheckBox`/`Switcher` and then wondering why unchecking never persists `false`.
- Filament/Livewire idioms (`->live()`, `->reactive()`, `wire:model`, form schemas) — Orchid fields are server-rendered with plain POST handlers.
- Pasting doc examples using `Relation`/`Upload` without checking they exist — grep `src/Screen/Fields/` first.

## Reference

- Docs: `https://orchid.software/en/docs/field` · `https://orchid.software/en/docs/rows` · `https://orchid.software/en/docs/custom-field` · `https://orchid.software/en/docs/builder` · `https://orchid.software/en/docs/quickstart-crud`
- Source: `vendor/orchid/platform/src/Screen/Field.php` · `vendor/orchid/platform/src/Screen/Builder.php` · `vendor/orchid/platform/src/Screen/Fields/` · `vendor/orchid/platform/src/Screen/Concerns/` · `vendor/orchid/platform/src/Screen/LayoutFactory.php`
- Stubs: `vendor/orchid/platform/stubs/app/Orchid/Screens/Examples/ExampleFieldsScreen.php` · `vendor/orchid/platform/stubs/app/Orchid/Screens/Examples/ExampleFieldsAdvancedScreen.php`
- Siblings: attachments deep dive → `orchid-models`; table filters (`TD::filter()`) → `orchid-tables`; `Alert`, `Button`, modals → `orchid-actions`; `Tabs`/`Columns`/`Split` → `orchid-layouts`.
