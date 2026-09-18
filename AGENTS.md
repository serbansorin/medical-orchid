# Medical-Orchid — Agent Guidelines

EMS rebuild: Laravel (latest) + Orchid, sharing PostgreSQL DB `medikode_laravel` with
`legacy-medical` (https://medikode.test). Local URL: `https://med-orch.test`.

## Source of truth
- Plan: `openspec/MASTER_PLAN.md` · Change: `openspec/changes/ems-orchid-rebuild/`
  (proposal.md, specs/requirements.md, design.md, tasks.md, designs/*.md per page).
- Legacy models: `../legacy-medical/app/Models/` (copy, don't rewrite). Legacy DB: same
  credentials as `../legacy-medical/.env` (`DB_DATABASE=medikode_laravel`, main/1234).

## Rules
- **Additive-only migrations** — never alter/drop legacy tables. Verify with `migrate --pretend`.
- **Flat `app/Models/`** — no `Base/` split. Standard Orchid Screens only (Table/Row/Action).
- **One panel, role-gated menus**: super-admin, org-admin, doctor, pharmacist, receptionist
  (Cabinet + Facturare only). Scope lists by org → clinic/pharmacy (see design.md).
- **★ Detail screens** (Patient, Appointment, Medical Visit, Billing Invoice): one Screen with
  header view + `commandBar()` + `Layout::tabs` driven by `?tab=`. Others: plain CRUD.
- Forms stack vertically (`Layout::rows`). Server-side pagination, 25/page.
- User-facing strings in `en` + `ro`. Pint-clean PHP per step + browser pass at med-orch.test.
- Keep `openspec/MASTER_PLAN.md` status current as steps complete.

## Code conventions
- **Placement map**: Orchid UI in `app/Orchid/{Screens,Layouts,Presenters,Filters}/`; business logic
  in `app/Services/` (mirror legacy layout: `Services/{Domain}/XxxService.php`, cross-domain flat);
  enums `app/Enums/`; custom Blade views for `Layout::view` under `resources/views/orchid/`;
  standard Laravel dirs (Requests, Jobs, Policies, Observers, Notifications) only as needed.
- **Thin Screens**: `query()` → `layout()` → actions only. No business rules in Screens, Layouts or
  Blade — domain logic lives in services.
- **Services**: stateless, constructor-injected, no Orchid/HTTP coupling (Laravel facades OK); one
  domain per class; wrap multi-write flows (visit → invoice, dispense → stock, receive → stock) in
  `DB::transaction()`; throw exceptions, don't fail silently; return models/collections.
- **Port, don't rewrite**: adapt legacy `app/Services/*` (drop Filament/Livewire coupling); copied
  models stay verbatim in flat `app/Models/` — add casts/relations/scopes additively only.
- **Scope once**: role-based list scoping mirrors legacy `OrgScopeMiddleware` in ONE place
  (middleware / global scope), never repeated `where()` per screen.
- **PHP style**: `declare(strict_types=1)` in new files (never retrofit copied ones); full parameter
  and return types; `App\Enums` over magic strings; no `dd()`/`dump()`/`var_dump()` in committed code.
- **Validation**: `$request->validate()` in screen methods, FormRequests for non-Orchid routes;
  messages via lang files.
- **Queries**: eager-load relations in `query()`; server-side pagination; no full-table `->get()`
  in screens.
- **Routes**: admin routes only via `Route::screen()` (naming nuances in the `orchid-screens` skill);
  route names `platform.*`.
- **Naming**: `XxxListScreen` / `XxxEditScreen` / `XxxScreen` (★ detail); `XxxService`; keep copied
  legacy relation aliases unchanged.

## Agent tooling
- Nested guides: `openspec/AGENTS.md` (plan → tasks → designs workflow) · `.opencode/skills/AGENTS.md`
  (skill pack maintenance).
- Orchid work: load the `orchid-*` skills — source-verified for Orchid 14.x (online docs lag the code).
