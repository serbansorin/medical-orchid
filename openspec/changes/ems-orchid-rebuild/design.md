# Design: EMS on Orchid — Architecture

## Decisions

1. **Single Orchid panel, not 4 panels.** Legacy has 4 Filament panel providers; Orchid has one
   `/admin` with permission-gated menus. Roles pick dashboard + visible menu. This kills the
   mirror-change problem.
2. **Shared database, additive-only.** `DB_DATABASE=medikode_laravel`, same host/user/pass as
   legacy. New app runs **no** legacy-table migrations; new tables (if any, e.g. Orchid-specific)
   are additive and prefixed. Verify with `migrate --pretend`.
3. **Models are copied, not rewritten.** `legacy-medical/app/Models/*.php` → `app/Models/`,
   plus `app/Enums/` as needed. Observers/Policies copied only where authorization requires them.
   Filament/Livewire/React code is **not** ported.
4. **CRUD-first with Orchid primitives.** List = `Screen::query()` + `Layout::table()` with
   `TD` columns, `Link`/`DropDown` actions; forms = `Layout::rows()` stacked vertically.
   Filters = `Selection`/`DateRange` on the table screen. No custom Vue/React.
5. **Command-bar detail = one Screen, tabbed Blocks.** Patient/Appointment/Visit/Invoice screens
   render a header `Layout::view` + action bar (`Button`/`DropDown` in `commandBar()`) + one
   `Layout::tabs` or conditional `Layout::block` per section, driven by a query-string `tab`
   parameter. Keeps deep-linking (`?tab=prescriptions`) without route explosion.
6. **Visit lifecycle.** `appointments.status → completed` triggers "Create medical visit" (button on
   Appointment detail; also auto-suggest). `medical_visits.appointment_id` is the lineage key;
   `visit_episodes` + `visit_service_lines` attach services; prescriptions/records/invoices created
   from the Visit screen carry `medical_visit_id`/`appointment_id` so Patient detail can aggregate.

## Panel / Menu Map (single Orchid menu, role-gated)

```
Dashboards (route picks by role: admin.dashboard / doctor.dashboard / pharmacist.dashboard)
├── Organizare (org-admin, super-admin)
│   ├── Organizations, Clinics, Pharmacies, Users & Roles, Specialties
├── Cabinet (doctor, receptionist, org-admin)
│   ├── ★ Patients, ★ Appointments, ★ Medical Visits, Doctor Schedules
├── Medical (doctor, org-admin)
│   ├── Prescriptions, Medical Records, Medical Letters (+Templates),
│   ├── Patient Diagnostics, Catalogs (Conditions/Symptoms/Treatments/Procedures/Codes),
│   ├── Questionnaire Templates
├── Farmacie (pharmacist, org-admin)
│   ├── Fulfillment queue, Inventory, Sales, Purchase Orders, Suppliers, Deliveries
├── Facturare (org-admin, receptionist-billing)
│   ├── ★ Invoices, Payments, Products & Services, Promotions / Loyalty
└── Sistem (super-admin)
    ├── Menus & Permissions (read-only mirror of legacy `menus` table), Imports, Articles
```
★ = command-bar detail screen. Receptionist = Cabinet + Facturare only.

## Data Scope Rules

- `super-admin`: all rows.
- `org-admin`: `where organization_id = user.org` (clinics/pharmacies inherit via org; pivot tables
  `clinic_user`, `user_workplaces`, `user_organizations` are the source of truth — mirror legacy
  `OrgScopeMiddleware` logic into an Orchid `Scope`/global query trait).
- `doctor`: rows for assigned clinics (`clinic_doctor_assignments`) + own patients
  (`doctor_patient_relationships`); prescriptions/records they authored are always visible to them.
- `pharmacist`: rows for assigned pharmacy (`pharmacy_staff` / `user_workplaces`).

## Screen Anatomy (applies to every page in `designs/`)

- `query()`: scoped Eloquent query + filter state.
- `commandBar()`: primary actions only (Create / Back / state transitions).
- `layout()`: table for lists; header-view + tabs for ★ details; rows-form for create/edit.
- `async` methods for tab data where tables are heavy (visit/invoice lines).
- After Orchid screen changes: open `https://med-orch.test` and click through (no Filament MCP
  checklist — Orchid has no equivalent; manual browser pass per step).

## Risks

- Legacy column quirks (e.g. commented-out widgets due to `is_abnormal`) — port defensively:
  guard nullable/odd columns in `TD::render()`.
- Orchid permission model differs from Shield/Spatie — map legacy `permissions`/`roles` rows to
  Orchid `access()` checks; do not import Shield.
- Two apps sharing one DB: coordinate deploys; never rename/drop legacy columns.
