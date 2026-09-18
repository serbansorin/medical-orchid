---
name: orchid-actions
description: "Build Orchid command bars, buttons, DropDowns, modals, Alert/Toast feedback, Listeners and screen state. Activate when working with commandBar, Button, ModalToggle, DropDown, Layout::modal, Alert, Toast, Listener layouts, or Turbo/Stimulus JS. Do not use for field catalogs, table columns, layout grouping, or Eloquent conventions."
license: MIT
compatibility: opencode
metadata:
  author: orchid
  version: "1.0.0"
  domain: backend
  framework: orchid-platform-14
  triggers: commandBar, Button, ModalToggle, DropDown, modal, Alert, Toast, Listener, screen state, confirm dialog
  role: specialist
  scope: laravel
  related-skills: orchid-screens, orchid-layouts, orchid-fields, orchid-access
---

# Orchid Actions, Modals & Feedback

Orchid 14.x on Laravel 10–13. Actions are the interactive layer of a screen:
`commandBar()` buttons, row toggles, dropdown menus, modal dialogs, flash/toast
feedback, reactive `Listener` layouts, and Stimulus/Turbo JavaScript hooks.
Every class and method below was verified against platform source or official docs.

## Version notes

Mined from `upgrade.md` (`grep -n -i "action|modal|button|alert|listener|toast|dropdown"`):

- **Listener contract changed**: no screen-method property. Every `Listener` must
  implement `handle(Repository $repository, Request $request): Repository`.
- **Profile/logout buttons are explicit**: no default Logout / Quit Impersonation
  actions. Define them with `Button::make(...)->method(...)` on the profile screen.
- **Turbo replaces Turbolinks**: use the `turbo:load` event, not `turbolinks:load`.
- No breaking changes found for `Button`, `DropDown`, `ModalToggle`, `Modal`,
  `Alert`, or `Toast`.

## Action class inventory

`ls src/Screen/Actions/` gives six classes, all in `Orchid\Screen\Actions`.
`ModalToggle extends Button`; `Toggle extends Button`; `Menu extends Link`.

| Class | Purpose | Key methods |
|---|---|---|
| `Button` | POST screen form to a screen method or URL, or navigate | `method()`, `parameters()`, `route()`, `action()`, `confirm()`, `icon()`, `disabled()`, `novalidate()`, `rawClick()`, `canSee()` |
| `ModalToggle` | Open a `Layout::modal()` dialog, submit to a screen method | `modal()`, `modalTitle()`, `asyncParameters()`, + `Button` methods |
| `Link` | Anchor navigation, no submit | `href()`, `route()`, `target()`, `download()` |
| `DropDown` | Overlay menu of Buttons/Links | `list([...])`, `icon()` |
| `Toggle` | Boolean switch for table cells | `method()`, `status()` |
| `Menu` | Sidebar/navigation item, not a command-bar action | `list()`, `badge()`, `permission()` |

`canSee(bool)` (verified `Screen/Concerns/CanSee.php`) hides any action by role or
record state. `confirm(string)` (verified `@method` docblock + `confirm` attribute in
`Button.php`, plus stub usage) shows a confirm dialog before submit.
`icon(string)` takes Orchid keys like `bs.box-arrow-up-right`.

## Command bar patterns

`commandBar()` returns the primary actions (verified `screens.md`, `ExampleActionsScreen` stub):

```php
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Color;

class PatientScreen extends Screen
{
    public function commandBar(): iterable
    {
        return [
            Button::make('Save')->method('save')->type(Color::PRIMARY),
            Link::make('Back to list')->route('platform.patients.list'),
            ModalToggle::make('Discharge')
                ->modal('dischargeModal')
                ->method('discharge')
                ->type(Color::WARNING)
                ->canSee($this->patient->isAdmitted()),
        ];
    }
}
```

### `method()`, `route()`, `action()`

`method()` posts the whole visible form to a public method on the same screen class:

```php
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Toast;

Button::make('Deactivate')
    ->method('switch', ['user' => $user->id])
    ->confirm('Deactivate this user?')
    ->type(Color::DANGER);

public function switch(Request $request): void
{
    User::findOrFail($request->input('user'))->update(['active' => false]);
    Toast::success('User deactivated.');
}
```

`route()` navigates (`Button.php:181`, `Link.php:79`); `action()` posts to a custom URL:

```php
Button::make('Open report')->route('platform.reports.show', $report);
Button::make('Google')->action('https://google.com');
Link::make('External reference')->href('https://orchid.software');
Link::make('Download')->href('#')->download();
```

Keep confirm text specific (what happens, is it reversible) and re-authorize destructive
methods server-side. Gate visibility with `canSee()`:

```php
Button::make('Approve')
    ->method('approve')
    ->canSee($user->can('emr.approve') && $visit->isDraft());
```

### Toggle and downloads

`Toggle` flips booleans inside table cells (see `orchid-tables` for `TD`):

```php
use Orchid\Screen\Actions\Toggle;
use Orchid\Screen\TD;

TD::make('active', 'Enabled')->render(fn (User $user) => Toggle::make()
    ->method('switch', ['user' => $user->id])
    ->status($user->active));
```

Recommended toggle config in `config/orchid.php`: `'refresh-method' => 'morph'` and
`'refresh-scroll' => 'preserve'`. For binary downloads disable reactive response
processing with `rawClick()` (alias `download()`, verified `Button.php`):

```php
Button::make('Download')->icon('bs.download')->method('export')->rawClick();
```

## DropDown menus

`DropDown::make()->list([...])` verified (`DropDown.php:43`, stub). Holds submitting
Buttons or navigating Links:

```php
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;

DropDown::make('Row actions')
    ->icon('bs.three-dots-vertical')
    ->list([
        Button::make('Admit')->method('admit', ['patient' => $patient->id]),
        Button::make('Transfer')->method('transfer', ['patient' => $patient->id]),
        Link::make('Open chart')->route('platform.patients.chart', $patient),
    ]),
```

One verb per item, three to five items max. Gate the menu broadly, items specifically.
Never nest a `DropDown` inside another.

## Modals

Verified: `Orchid\Screen\Actions\ModalToggle`, `Orchid\Screen\Layouts\Modal`,
`Orchid\Support\Facades\Layout`, API in `src/Screen/Layouts/Modal.php`.

Declare the toggle in `commandBar()` and the modal at the **top level** of `layout()`.
Never nest `Layout::modal()` inside `Tabs`, tables, or another layout.

```php
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

public function commandBar(): array
{
    return [
        ModalToggle::make('Create notification')
            ->modal('toastModal')
            ->method('sendNotification')
            ->icon('bs.plus-circle'),
    ];
}

public function layout(): iterable
{
    return [
        Layout::modal('toastModal', Layout::rows([
            Input::make('notification.subject')->title('Subject')->required(),
            TextArea::make('notification.body')->title('Body')->rows(3),
        ]))->title('New notification')->applyButton('Send'),
    ];
}

public function sendNotification(Request $request): void
{
    $request->validate([
        'notification.subject' => 'required|string|max:255',
        'notification.body'    => 'required|string',
    ]);
    Toast::success('Notification queued.');
}
```

### `Layout::modal()` options

| Method | Effect | Source |
|---|---|---|
| `->title()` | Header text | `Modal.php:162` |
| `->size(Modal::SIZE_LG)` | `SIZE_SM`, `SIZE_LG`, `SIZE_XL` | `Modal.php:21-23,142` |
| `->type(Modal::TYPE_RIGHT)` | Right slide-in vs centered | `Modal.php:25-26,152` |
| `->applyButton()` / `->closeButton()` | Rename footer buttons | `Modal.php:102,132` |
| `->withoutApplyButton()` / `->withoutCloseButton()` | Hide a footer button | `Modal.php:112,122` |
| `->open()` | Open on page load | `Modal.php:182` |
| `->method()` | Submit target | `Modal.php:192` |
| `->staticBackdrop()` | Block outside-click close | `Modal.php:202` |
| `->rawClick()` | Skip reactive processing | `Modal.php:172` |

Multiple body layouts take an array: `Layout::modal('m', [Layout::rows([...]),
Layout::table(...)])`. Avoid tables with filters/sorting/pagination inside modals —
their links close the dialog. Validate with `$request->validate()` in the handler;
use `->novalidate()` only to bypass browser-native validation.

### Deferred modal content

One modal per row is wasteful. Use `->deferred('loader')` (verified `Modal.php:236`)
and pass parameters at open time:

```php
Layout::modal('deferredModal', Layout::rows([
    Input::make('welcome')->readonly(),
]))->deferred('loadDataOnOpen'),

ModalToggle::make('Open Modal')
    ->method('methodForSubmitModal')
    ->modalTitle('Customizable Title')
    ->modal('deferredModal', ['welcome' => 'Hello world!']);

public function loadDataOnOpen(string $welcome): array
{
    return ['welcome' => $welcome];
}
```

The loader returns `query`-shaped data swapped into the modal on open, no page reload.

## Notifications: Alert and Toast

Verified: `Orchid\Support\Facades\Alert`, `Orchid\Support\Facades\Toast`,
`src/Alert/{Alert,Toast}.php` (`Toast extends Alert`).

| Helper | Renders as | Use for |
|---|---|---|
| `Alert::info/success/warning/error/message()` | Inline flash banner | Saves, transitions, summaries |
| `Toast::info/success/warning/error()` | Auto-hiding pop-up | Button/toggle/modal confirmations |

```php
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;

Alert::success('Your message has been sent.');
Alert::error('Please fill in all required fields.');
alert('Your action has been completed.'); // helper = Alert::message()
Toast::warning('Invalid input. Please check your form.');
Toast::info('Saved.')->delay(2000);   // ms; ->seconds(5) for seconds
Toast::warning('Read carefully.')->persistent(); // no auto-hide
Alert::info('This is <strong>bold</strong>.')->withoutEscaping();
Alert::view('alert', Color::INFO(), ['name' => 'Alexandr']);
```

Feedback flow: the action method calls `Alert`/`Toast` (session flash), then returns
`void` or a redirect — Orchid paints the flash next render. There is no
`->with('toast', ...)` step (unverifiable, omitted). `Alert::check(): bool`
(`Alert.php:147`) reports pending flashes.

For bell-icon notifications that outlive the page, use `DashboardChannel` with
`DashboardMessage` (`alert.md`; user model needs `Notifiable`):

```php
use Orchid\Platform\Notifications\DashboardChannel;
use Orchid\Platform\Notifications\DashboardMessage;

public function via($notifiable): array { return [DashboardChannel::class]; }

public function toDashboard($notifiable): DashboardMessage
{
    return (new DashboardMessage)->title('Report ready')->action(url('/'));
}
```

## Listeners: reactive layouts without reload

Verified: `Orchid\Screen\Layouts\Listener`, `src/Platform/Commands/ListenerCommand.php`,
`stubs/listener.stub`. A `Listener` re-renders its own `layouts()` when a watched
field changes — server-side `handle()`, DOM patch, no reload, no custom JS.

```bash
php artisan orchid:listener DosageCalculator
```

```php
namespace App\Orchid\Layouts;

use Illuminate\Http\Request;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Listener;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Layout;

class DosageCalculator extends Listener
{
    protected $targets = ['weight', 'dose_per_kg'];

    protected function layouts(): iterable
    {
        return [
            Layout::rows([
                Input::make('weight')->title('Weight (kg)')->type('number'),
                Input::make('dose_per_kg')->title('Dose per kg (mg)')->type('number'),
                Input::make('total_dose')->readonly()
                    ->canSee($this->query->has('total_dose')),
            ]),
        ];
    }

    public function handle(Repository $repository, Request $request): Repository
    {
        return $repository
            ->set('weight', $w = (float) $request->input('weight'))
            ->set('dose_per_kg', $d = (float) $request->input('dose_per_kg'))
            ->set('total_dose', $w * $d);
    }
}
```

Mount with `return [new DosageCalculator];`. Rules: `$targets` names the watched fields
(multi-select `users[]` uses trailing dot `'users.'`); `handle()` gets current state +
new input and returns the updated `Repository`; keep it fast and side-effect free (no
writes/mail — it fires on every change); routing via `asyncRoute()` is automatic, never
call it yourself; one concern per class.

## Screen state (public properties)

Verified `quickstart-state.md`: each request carries the screen's **public properties**,
so small intermediate values survive between action calls.

```php
class StateScreen extends Screen
{
    public int $clicks = 0;

    public function query(): iterable
    {
        return ['clicks' => $this->clicks];
    }

    public function commandBar(): iterable
    {
        return [Button::make('Increment')->method('increment')];
    }

    public function increment(): void
    {
        $this->clicks++;
    }
}
```

Expose state in `query()` (`'key' => $this->prop ?? $default`); mutate via `$this->prop`
in action methods. Scalars and single models only — collections/paginators hurt
performance. Refresh resets to `query()` defaults; persist durable data to the DB.

## `method()` dispatch: screen method vs async

Full lifecycle lives in `orchid-screens`. Summary:

- `Button::make()->method('name')` / `ModalToggle::make()->modal(...)->method('name')`
  dispatch to `public function name(Request $request)` on the same screen class.
- The visible form posts plus `parameters()` extras. Return `void` (re-render), a
  redirect, or a download (with `rawClick()`).
- `Listener::handle()` and `->deferred()` loaders are the async path: they return data
  (`Repository` / array), never redirects or downloads.
- Keep handlers thin: authorize, validate, mutate, `Toast`/`Alert` feedback.

## JavaScript: Turbo + Stimulus surface

Verified `javascript.md` for the Turbo/Stimulus surface and `resources/js/orchid.js`
for the `window.Orchid` object (which contains only `register()` and `id()`).
Bootstrap CSS + Hotwired (Turbo + Stimulus). No Vue/React runtime, no
`window.orchidListener` global — only `window.Orchid`:

| API | Purpose |
|---|---|
| `Orchid.register(name, definition)` | Register a Stimulus controller |
| `Orchid.id()` | Per-tab UUID (`sessionStorage: orchid_tab_id`) |
| `window.Controller` | Stimulus base class (per `javascript.md`) |
| `turbo:load` event | Page-ready hook; plain `load` fires once under Turbo (per `javascript.md`) |

```js
document.addEventListener('turbo:load', () => { console.log('Page ready'); });
```

```javascript
// resources/js/controllers/hello.js
export default class extends window.Controller {
    static get targets() { return ['name', 'output']; }
    greet() { this.outputTarget.textContent = `Hello, ${this.nameTarget.value}!`; }
}
// resources/js/dashboard.js
import HelloController from './controllers/hello';
application.register('hello', HelloController);
```

```blade
<div data-controller="hello">
  <input data-hello-target="name" type="text">
  <button data-action="click->hello#greet">Greet</button>
  <span data-hello-target="output"></span>
</div>
```

Wire with `'vite' => ['resources/js/dashboard.js']` in `config/orchid.php`, then
`npm run build` + `php artisan config:clear`. Decision rule: plain `method()` by
default; `Listener` when field B recomputes from field A; Stimulus only for cosmetic
DOM behavior — never duplicate validation or permissions in JS.

## This project (medical-orchid)

- `commandBar()` holds primary actions only: Create/Back plus transitions (Admit,
  Discharge, Approve, Sign, Pay). Filters and secondary links belong in layouts.
- Detail screens (Patient, Appointment, Medical Visit, Billing Invoice) carry an action
  bar above `Layout::tabs`: transitions gated by `->canSee()` on role + record state,
  `->confirm()` on irreversible steps.
- Every mutation ends with `Toast::success()` / `Toast::error()` (ro + en); `Alert`
  inline only when the next render must explain context.
- Small forms (note, reschedule, payment) go in top-level `Layout::modal()` dialogs;
  anything larger gets a full screen.
- Heavy tabs load via `async`/`deferred`, 10/page with "view all" links.
- No Vue/React: cascading selects and computed doses are `Listener` layouts.
- Pint-clean PHP per step; click every button, modal, dropdown, and Listener path at
  `https://med-orch.test` before marking done.

## Anti-patterns

- `Menu` in `commandBar()` — navigation only; bars take Button/ModalToggle/Link/DropDown/Toggle.
- `Layout::modal()` nested in Tabs/tables — always top-level in `layout()`.
- Filter/sort/paginate tables inside modals — their links close the dialog.
- Side effects in `Listener::handle()` — compute and return state only.
- Collections in public screen properties — scalars/small models only.
- Filament/Livewire idioms (`->actions()`, `wire:click`) — use `commandBar()`, `->method()`, `$targets`.
- Invented JS globals (`window.orchidListener`, `Orchid.refresh()`) — verified surface is
  `Orchid.register()`, `Orchid.id()`, `window.Controller`, `turbo:load`.

## Reference

- Actions: <https://orchid.software/en/docs/screens> (Screen Actions)
- Modals: <https://orchid.software/en/docs/modals>
- Notifications: <https://orchid.software/en/docs/alert>
- Listener: <https://orchid.software/en/docs/listener>
- State: <https://orchid.software/en/docs/quickstart-state>
- JavaScript: <https://orchid.software/en/docs/javascript>

Sources (`vendor/orchid/platform/src/...`): `Screen/Actions/{Button,ModalToggle,Link,
DropDown,Toggle,Menu}.php`; `Screen/Concerns/CanSee.php`; `Screen/Layouts/{Modal,
Listener}.php`; `Alert/{Alert,Toast}.php`; `Platform/Commands/ListenerCommand.php`;
`stubs/listener.stub`; `stubs/.../ExampleActionsScreen.php`; `resources/js/orchid.js`.
