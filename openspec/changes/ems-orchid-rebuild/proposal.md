# Proposal: EMS Rebuild on Orchid (medical-orchid)

## Summary

Rebuild the medical practice management system (`legacy-medical`, Laravel 12 + Filament 4, 4 panels,
~60 resources, 150 tables in `medikode_laravel`) as a leaner **EMS on Laravel (latest) + Orchid** in
`medical-orchid/`, reusing the **same PostgreSQL database** (`medikode_laravel`) and the **existing
Eloquent models**. One Orchid panel serves three audiences via role-filtered menus and role-specific
dashboards: **super-admin/org-admin**, **doctor**, **pharmacist** (+ receptionist as a doctor-panel
subset if needed).

Most screens are straight Orchid CRUD (Table + form Rows). Four screens get a **special detail
layout with a command-bar menu**: **Patient**, **Appointment**, **Medical Visit** (created when an
appointment is completed; aggregates everything done during that visit), and **Billing Invoice**.
Everything else ships as CRUD first, refined later.

## Problem Statement

1. **Filament surface is too big to maintain**: 415 Filament files, 4 panel providers
   (`Admin`, `Doctor`, `Pharmacist`, `ClinicReceptionist`), ~60 resources. Change in one panel
   often needs mirroring in others.
2. **Doctor panel over-engineering risk**: the legacy roadmap (Payload CMS migration, widget
   registries, 150-task changes) pulls focus from shipping a working EMS.
3. **What the business actually needs now**: orgs with clinics + pharmacies, each with a panel;
   doctors see patients/appointments/visits/prescriptions; pharmacists see stock/sales/fulfillment;
   admins see everything + users/roles/billing. 90% of that is CRUD.
4. **Database is the stable asset**: 150 tables, seeded, used by `https://medikode.test`. Rewriting
   schema from scratch would be wasteful and risky. Reuse it.

## Proposed Solution

1. **Scaffold fresh Laravel (latest) + Orchid** in `medical-orchid/`, point it at the existing
   `medikode_laravel` database (no migrations that alter legacy tables; only additive Orchid +
   permission tables if needed).
2. **Copy Eloquent models** from `legacy-medical/app/Models/` (plus Enums/Observers/Policies as
   needed). No `Base/` split — flat `app/Models/` only.
3. **Single Orchid panel** (`/admin`) with:
   - role-based menu visibility (Orchid permissions per menu item / screen),
   - three dashboards: Admin (org-wide KPIs), Doctor (today's agenda, pending visits, Rx to sign),
     Pharmacist (low stock, pending fulfillments, today's sales).
4. **CRUD-first screens** using Orchid `Screen` + `Layout::table` + `Row` forms, one step per menu
   link (see `tasks.md`).
5. **Four command-bar detail screens**: Patient, Appointment, Medical Visit, Billing Invoice —
   each a `Screen` with a top action bar (buttons/dropdowns) switching sub-layouts/tabs instead of
   separate routes per tab.
6. **Spec-driven delivery**: this change (`ems-orchid-rebuild`) holds proposal/specs/design/tasks;
   `designs/*.md` holds one details+design doc per page. Each menu link = one implementation step.

## Impact

- **Who benefits**: org/clinic/pharmacy admins (one login, scoped data), doctors (agenda → visit →
  prescription flow), pharmacists (stock → sale/fulfillment flow).
- **What changes**: new codebase, same DB. Legacy (`medikode.test`) keeps running untouched until
  cutover. No data migration.
- **Out of scope for this change**: patient mobile app, public marketing site, Payload CMS ideas,
  real-time notifications, AI features, data-warehouse/reporting beyond dashboard KPIs.

## Scope

### In Scope
- Laravel + Orchid scaffold, Valet link `https://med-orch.test`, shared-DB config.
- Model port (~120 models) + relationships verified against live DB.
- RBAC: roles `super-admin`, `org-admin`, `doctor`, `pharmacist`, `receptionist` (subset of doctor
  menus); tenant scoping by organization → clinic / pharmacy.
- All menu links in `tasks.md` as CRUD screens + 4 command-bar detail screens + 3 dashboards.
- EN + RO labels for user-facing strings.

### Out of Scope
- Schema redesign (additive-only migrations).
- Billing automatic invoicing rules, insurance clearing integrations.
- Import pipelines beyond reusing the existing `imports` table UI.
- E-prescription regulatory signing (e.g. qualified e-signature) — stub the hook only.
