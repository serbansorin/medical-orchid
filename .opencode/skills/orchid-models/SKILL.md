---
name: orchid-models
description: "Use when wiring Eloquent models into Orchid screens, writing presenters, handling file attachments, or configuring global search. Activate when working with models, presenters, Attach/Cropper fields, Attachable trait, or Scout search. Do not use for screen lifecycle, field catalogs, table columns, actions, layouts, or permissions."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: model, presenter, attachment, attachable, search, scout, file upload, cropper
  role: specialist
  scope: laravel
  related-skills: orchid-screens, orchid-fields, orchid-layouts, orchid-tables
---

# Orchid Models

Eloquent models are the data backbone of every Orchid screen: `query()` loads them,
`TD` closures and legends display them, and presenters, attachments, and global
search extend them. This skill covers model conventions in Orchid 14.x apps
(Laravel 10-13), presenters for display logic, the attachment/file system, and
Scout-powered global search.

## Version notes

Grep of `upgrade.md` for `attach`, `upload`, `presenter`, `search`, `scout`,
`cropper`, `picture` found no breaking changes for this topic (13.x to 14.0;
the lone `search` hit is table-filter comma handling). Source-level caution:
older tutorials use `Upload` / `Images` fields, but v14 source ships only
`Attach` / `Cropper` / `Picture` - translate `Upload::make()` to
`Attach::make()` when copying old examples.

## Model conventions in Orchid apps

Models live in flat `app/Models/` (one class per file, standard Eloquent). Screens never query inside layouts - all loading happens in `query()`, and rows read values through the `AsSource` contract.

### Where models live

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Screen\AsSource;

class Patient extends Model
{
    use AsSource;
    use Attachable;

    protected $fillable = ['first_name', 'last_name', 'cnp', 'phone'];
}
```

Rules:

| Rule | Reason |
|------|--------|
| Keep models in `app/Models/`, no subfolders | Project convention; matches legacy copy layout |
| Add `AsSource` to every model shown on a screen | Layouts and TD read values via `getContent()` |
| Add `Attachable` only when the model owns files | Brings the `attachments()` relation |
| Put display logic in presenters, not accessors | Keeps models DB-focused and screens consistent |
| Guard nullable/legacy columns at read time | Legacy tables contain odd nulls; never trust a column |

### How screens consume models

`query()` returns a named array. Layouts resolve values by key path
(`patient.first_name`).

```php
<?php

use App\Models\Patient;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;

class PatientListScreen extends Screen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'patients' => Patient::paginate(25),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::table('patients', [
                TD::make('last_name', 'Last name')
                    ->render(fn (Patient $patient) => $patient->presenter()->fullName()),
            ]),
        ];
    }
}
```

For an edit screen, load file relations eagerly and sync them on save:

```php
public function query(Patient $patient): array
{
    $patient->load('attachments');

    return ['patient' => $patient];
}

public function createOrUpdate(Request $request, Patient $patient): void
{
    $patient->fill($request->get('patient'))->save();

    $patient->attachments()->syncWithoutDetaching(
        $request->input('patient.attachments', [])
    );
}
```

Placement rules: never query inside `layout()`, `TD::render()`, or legend
closures - load in `query()` and eager-load per-row relations there (N+1).

## Presenters

Presenters wrap an entity and hold display logic. Layouts call them; models stay
clean. Base class: `Orchid\Support\Presenter` (alias of `Orchid\Presenter\Presenter`).
Verified source: `src/Support/Presenter.php` and
`src/Presenter/{Presenter,Presentable,UsePresenter}.php`.

### Creating a presenter

Generate the stub with the built-in command (verified in
`vendor/orchid/platform/src/Platform/Commands/PresenterCommand.php`):

```bash
php artisan orchid:presenter CustomerPresenter
```

Presenters land in `app/Orchid/Presenters/` by default.

Hand-written minimal presenter:

```php
<?php

declare(strict_types=1);

namespace App\Orchid\Presenters;

use Orchid\Support\Presenter;

class CustomerPresenter extends Presenter
{
    public function fullName(): string
    {
        return sprintf('%s %s', $this->entity->first_name, $this->entity->last_name);
    }
}
```

The base class (`Orchid\Presenter\Presenter`) stores the model in
`$this->entity`; `__get()` calls a same-named presenter method first, then falls
back to the entity attribute. So `$presenter->first_name` returns the column
unless the presenter defines `first_name()`.

### Attaching a presenter to a model

Two supported patterns (both verified in source):

**Option A - `presenter()` method (classic, used by the platform search UI):**

```php
<?php

namespace App\Models;

use App\Orchid\Presenters\PatientPresenter;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    public function presenter(): PatientPresenter
    {
        return new PatientPresenter($this);
    }
}
```

Usage:

```php
$name = Patient::findOrFail(1)->presenter()->fullName();
```

**Option B - `Presentable` trait plus `#[UsePresenter]` attribute:**

```php
<?php

namespace App\Models;

use App\Orchid\Presenters\PatientPresenter;
use Illuminate\Database\Eloquent\Model;
use Orchid\Presenter\Presentable;
use Orchid\Presenter\UsePresenter;

#[UsePresenter(PatientPresenter::class)]
class Patient extends Model
{
    use Presentable;
}
```

Usage (identical call shape, optional runtime override):

```php
$presenter = $patient->presenter(); // resolves via attribute
```

> `Presentable::presenter()` throws `RuntimeException` when neither an override
> class nor a `#[UsePresenter]` attribute is present. Option A never throws -
> pick one pattern per model and stay consistent. Over collections:
> `Patient::limit(10)->get()->map->presenter()->map->fullName()`.

Reference presenter (canonical example in every install) -
`stubs/app/Orchid/Presenters/UserPresenter.php` implements `Personable` and
`Searchable` with `label()`, `title()`, `subTitle()`, `url()`, `image()`,
`perSearchShow()`, `searchQuery()`. Copy its shape for search-capable models.

## Attachments and files

Orchid stores files in an `attachments` table (model
`Orchid\Attachment\Models\Attachment`) plus a polymorphic pivot
(`attachmentable` table, model `Orchid\Attachment\Models\Attachmentable`).
Physical files live on a Laravel filesystem disk; the DB row holds `name`,
`path`, `extension`, `disk`, `hash`, `group`, `sort`.

Default config (`config/orchid.php`, key `attachment`):

```php
'attachment' => [
    'disk'      => env('PLATFORM_FILESYSTEM_DISK', 'public'),
    'generator' => \Orchid\Attachment\Engines\Generator::class,
],
```

Deduplication is hash-based: uploading identical content reuses the stored file
and creates a new DB link. Physical deletion happens only when the last link to
a `(hash, disk)` pair is gone (see `Attachment::delete()`).

### Attaching files to a model

Add the trait (verified: `vendor/orchid/platform/src/Attachment/Attachable.php`):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Screen\AsSource;

class MedicalVisit extends Model
{
    use AsSource;
    use Attachable;
}
```

Use `attachments(?string $group = null)` (filters by `group`, orders by
`sort`). The singular `attachment()` is a deprecated alias - do not use it.

```php
$visit->attachments()->get(); // all files
$visit->attachments('reports')->get(); // one group only
```

### Uploading via fields

> Field-name note: Orchid 14 source ships `Attach`, `Cropper`, and `Picture`
> (`vendor/orchid/platform/src/Screen/Fields/{Attach,Cropper,Picture}.php`).
> There is **no** `Upload` or `Images` field class in this version - older docs
> and tutorials use `Upload::make(...)`, which maps to today's
> `Attach::make(...)`. Use `Attach` for multi-file groups and
> `Cropper`/`Picture` for single images.

**Attach - multi-file groups.** Key APIs: `accept()`, `multiple()`,
`maxSize()` (MB, checked against `php.ini`), `path()`, `group()` (field filters
loaded values by it), `storage()` (disk must exist in `config/filesystems.php`).

```php
Attach::make('visit.attachments')
    ->title('Medical documents')
    ->accept('application/pdf,image/*')
    ->multiple(true)
    ->group('reports')
    ->storage('public'),
```

**Cropper / Picture - single images.** `targetId()` stores the attachment id
(recommended), `targetUrl()` the full URL, `targetRelativeUrl()` the relative
URL; plus `path()`, `acceptedFiles()` (default `'image/*'`), `width()` /
`height()`, `maxFileSize()`.

```php
Cropper::make('patient.photo')->targetId()->width(400)->height(400);
```
Keep one target mode per column - mixing ids and URLs breaks display code.

### Retrieving files in screens

Single image stored by id - resolve via the `Attachment` model, and read
group files via the relation (remember `->load('attachments')` in `query()`):

```php
use Orchid\Attachment\Models\Attachment;

$photo = $patient->photo ? Attachment::find($patient->photo) : null;
$reports = $visit->attachments('reports'); // MorphToMany, ordered by sort
$url = $visit->attachments()->first()?->url(); // null-safe for legacy rows
```

Attachment model helpers (verified in
`vendor/orchid/platform/src/Attachment/Models/Attachment.php`): `url()` /
`url` accessor, `relativeUrl` accessor, `physicalPath()`, `isPhysicalExists()`,
`isMime(string)`, `download()`, and `delete()` (removes links; deletes the
physical file only when unreferenced).

### Uploading programmatically

`Orchid\Attachment\File` wraps an `UploadedFile` (verified: `load()`,
`allowDuplicates()`, `path()`, `sort()`, optional `$disk`/`$group` constructor
args). Validate before `load()` - the system enforces no MIME/size rules itself.

```php
$attachment = (new File($request->file('document')))
    ->path('visits/'.$request->user()->id)
    ->load();

return response()->json($attachment);
```

Deduplication is on by default (`allowDuplicates()` opts out); custom path segments accept dynamic values (`'visits/'.$visit->id`).

### Deleting attachments

Attachments survive model deletion. Clean up with the collection form so each
`Attachment::delete()` runs the physical-file reference check (the query form
`attachments()->delete()` skips it):

```php
public function deleting(MedicalVisit $visit): void
{
    $visit->attachments->each->delete();
}
```

For single-image-by-id columns, guard the nullable legacy value the same way
(`Attachment::find($patient->photo)?->delete()` inside an `if ($patient->photo)`
check). Sweep never-linked uploads on a schedule via
`Attachment::doesntHave('relationships')->whereDate(...)->get()->each->delete()`.

Two upload events exist, dispatched from different places: `UploadFileEvent`
(dispatched in `File::load()`) for post-processing after a file is stored, and
`UploadedFileEvent` (dispatched in `AttachmentController`) when the HTTP upload
endpoint handles a file. Subscribe to the one matching your hook point:

```php
use Orchid\Platform\Events\UploadFileEvent;

Event::listen(function (UploadFileEvent $event) {
    // $event->attachment, $event->time
});
```

## Global search

Sidebar search is powered by [Laravel Scout](https://laravel.com/docs/scout).

### 1. Install and configure Scout

```bash
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

Choose a driver (database, Meilisearch, Algolia) per the Scout docs. Orchid only calls `$model->search($query)` - driver behaviour is Scout's domain.

### 2. Make the model searchable

Add Scout's `Searchable` trait, a `presenter()`, and a `toSearchableArray()`
(cherry-pick indexable columns - never index the whole legacy row):

```php
class Patient extends Model
{
    use Searchable;

    public function presenter(): PatientPresenter
    {
        return new PatientPresenter($this);
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return ['first_name' => $this->first_name, 'last_name' => $this->last_name];
    }
}
```

> `Laravel\Scout\Searchable` (model trait) and
> `Orchid\Screen\Contracts\Searchable` (presenter interface) are different
> types with similar names. Models use the first; presenters implement the second.

### 3. Register the model in `config/orchid.php` and clear config

```php
'search' => [
    \App\Models\Patient::class,
    \App\Models\MedicalVisit::class,
],
```
```bash
php artisan config:clear
```

### 4. Implement the presenter contract

Verified interface: `vendor/orchid/platform/src/Screen/Contracts/Searchable.php`;
canonical shape: `stubs/app/Orchid/Presenters/UserPresenter.php`. Required
methods: `label()` (section heading), `title()`, `subTitle()`, `url()` (result
link), `image()` (thumbnail or `null`), `searchQuery(?string $query)` (Scout
builder), `perSearchShow()` (rows per section, e.g. `3`).

```php
class PatientPresenter extends Presenter implements Searchable
{
    public function label(): string
    {
        return 'Patients';
    }

    public function title(): string
    {
        return sprintf('%s %s', $this->entity->first_name, $this->entity->last_name);
    }

    public function subTitle(): string
    {
        return (string) ($this->entity->cnp ?? __('No CNP'));
    }

    public function url(): string
    {
        return route('platform.patients.edit', $this->entity);
    }

    public function image(): ?string
    {
        return null;
    }

    public function searchQuery(?string $query = null): Builder
    {
        // Constrain per role here (e.g. ->where('organization_id', ...)):
        return $this->entity->search($query);
    }

    public function perSearchShow(): int
    {
        return 3;
    }
}
```

> Search results render through `presenter()`, so every registered model MUST
> expose one - missing presenters break the search dropdown, not just styling.
> Scope `searchQuery()` per role (org-admin by `organization_id`, doctor to own
> clinics/patients, pharmacist to own pharmacy); super-admin stays unscoped.

## This project (medical-orchid)

- Legacy models are copied verbatim into flat `app/Models/` - add `AsSource`,
  `Attachable`, `Searchable`, or presenter wiring on top; never rewrite columns.
- Guard every legacy display read: `$patient->cnp ?? __('-')`,
  `$file?->url()`, `optional(Attachment::find($id))->url()` - nulls and stale
  ids are normal in legacy data.
- Prefer presenters for all patient/visit/invoice display strings (`fullName()`,
  `statusLabel()`, `totalFormatted()`) so RO/EN labels live in one place.
- Medical documents (reports, scans, prescriptions) go through `Attachable`
  plus `Attach` with explicit `group()` values (`reports`, `prescriptions`,
  `scans`); single photos use `Cropper` with `targetId()`.
- Additive-only DB: new file-reference columns arrive via new migrations,
  never by editing legacy tables.
- Scope `searchQuery()` by role (org-admin by `organization_id`, doctor to own
  clinics/patients, pharmacist to own pharmacy); super-admin stays unscoped.
- Register only role-safe models in `config/orchid.php` `'search'`;
  receptionist search must never surface pharmacy stock or other-org records.

## Anti-patterns

- Querying inside `layout()` or `TD::render()` instead of `query()` - layouts
  read, `query()` loads. Per-row queries cause N+1 on every paginated page.
- Forgetting `AsSource` on a model shown in a screen - layouts silently fail to
  resolve `getContent()` values.
- Using deprecated `attachment()` instead of `attachments()` in new code.
- Copying `Upload::make()` / `Images::make()` from old tutorials - these classes
  do not exist in Orchid 14 source; use `Attach::make()`, `Cropper`, `Picture`.
- Mixing `targetId()` and `targetUrl()` values in one column - pick one target
  mode per column or display code breaks.
- Calling `$model->attachments()->delete()` (query form) instead of
  `$model->attachments->each->delete()` (collection form) - the query form skips
  the physical-file reference check.
- Registering a model in `'search'` without a `presenter()` - the search
  dropdown calls it unconditionally.
- Confusing `Laravel\Scout\Searchable` with
  `Orchid\Screen\Contracts\Searchable` - the model uses the former, its
  presenter implements the latter.
- Uploading without validation - the attachment system enforces no
  extension/MIME/size rules by default; validate before `load()`.

## Reference

- Presenters: <https://orchid.software/en/docs/presenters>
- Attachments: <https://orchid.software/en/docs/attachments>
- Manage file attachments (quickstart): <https://orchid.software/en/docs/quickstart-files>
- Global search: <https://orchid.software/en/docs/global-search>
- Configuration (attachment defaults, search list): <https://orchid.software/en/docs/configuration>
- Upload field catalog, see the `orchid-fields` skill; file display in legends, see the `orchid-layouts` skill.

Key source paths (as `vendor/orchid/platform/...` in a real app):

- `src/Support/Presenter.php` plus `src/Presenter/{Presenter,Presentable,UsePresenter}.php`
- `src/Screen/Contracts/Searchable.php`, `src/Screen/Contracts/Personable.php`
- `src/Attachment/Attachable.php`, `src/Attachment/File.php`
- `src/Attachment/Models/Attachment.php`, `src/Attachment/Models/Attachmentable.php`
- `src/Screen/Fields/{Attach,Cropper,Picture}.php`
- `src/Platform/Commands/PresenterCommand.php` (`orchid:presenter`)
- `src/Platform/Events/UploadFileEvent.php`
- `stubs/app/Orchid/Presenters/UserPresenter.php`
- `config/orchid.php` (`attachment`, `search` keys)
