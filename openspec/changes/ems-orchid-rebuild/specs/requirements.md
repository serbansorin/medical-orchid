# Requirements: EMS on Orchid

> Change: `ems-orchid-rebuild` · Stack: Laravel (latest) + Orchid + PostgreSQL (`medikode_laravel`,
> shared, additive-only migrations) · Local URL: `https://med-orch.test`

## Functional Requirements

### Foundation
- **FR-1**: Fresh Laravel app boots against the existing `medikode_laravel` DB with **zero
  destructive migrations**; `php artisan migrate --pretend` shows only additive tables.
- **FR-2**: All ported Eloquent models load on the live DB (`tinker` smoke: first()/count() per
  model without SQL errors).
- **FR-3**: Valet site `med-orch.test` serves the Orchid login over HTTPS locally.
- **FR-4**: Roles `super-admin`, `org-admin`, `doctor`, `pharmacist`, `receptionist` exist; every
  screen checks permission; every list query is scoped to the user's organization (and
  clinic/pharmacy where applicable).

### Dashboards (one panel, three views)
- **FR-5**: Admin dashboard: org KPIs (patients, today's appointments, open invoices total, low-stock
  count, prescriptions pending fulfillment).
- **FR-6**: Doctor dashboard: today's agenda, open (unfinished) visits, prescriptions awaiting
  signature, recent patients.
- **FR-7**: Pharmacist dashboard: low-stock alerts, prescriptions routed for fulfillment, today's
  sales total, pending purchase orders.

### CRUD screens (each menu link = one step, details in `designs/*.md`)
- **FR-8 Organize**: Organizations, Clinics (+ departments, doctor assignments), Pharmacies (+
  staff), Users & Roles, Specialties.
- **FR-9 Clinic flow**: Patients (special detail), Appointments (special detail), Medical Visits
  (special detail, created on appointment completion), Doctor Schedules (+ time off).
- **FR-10 Medical**: Prescriptions (+ medications, routing, fulfillment link), Medical Records /
  attachments, Medical Letters (+ templates), Patient Diagnostics, catalogs (Conditions, Symptoms,
  Treatments, Procedures, Medical Codes), Questionnaire Templates.
- **FR-11 Pharmacy**: Inventory, Sales (+ items, receipts, payments), Purchase Orders (+ items),
  Suppliers, Deliveries.
- **FR-12 Billing**: Invoices (+ items, special detail), Payments, Products/Services catalog,
  Promotions/Loyalty (read-mostly CRUD).

### Special detail screens (command-bar menu)
- **FR-13 Patient detail**: header + command bar (Overview, Appointments, Visits, Prescriptions,
  Reports/Vitals, Documents, Invoices, Notes) — tabbed sub-layouts on one Screen.
- **FR-14 Appointment detail**: header (patient/doctor/clinic/slot/status) + command bar (Start
  visit → creates Medical Visit, Reschedule, Cancel, Add note, Create invoice, Create prescription).
- **FR-15 Medical Visit detail**: header (linked appointment/patient/doctor) + command bar sections
  (Diagnostics, Prescriptions, Procedures/Services, Notes, Attachments, Invoice) — everything done
  during the visit is created/linked from here.
- **FR-16 Billing Invoice detail**: header (totals/status) + command bar (Add item, Record payment,
  Issue/Finalize, Print/PDF, link to visit/appointment).

### Cross-cutting
- **FR-17**: All user-facing strings have `en` + `ro` translations.
- **FR-18**: Audit-relevant actions (visit finalize, invoice finalize, prescription sign, payment
  record) write who/when (use existing `created_by`-style columns where present).

## Non-Functional Requirements

- **NFR-1**: No change to legacy runtime: `medikode.test` keeps working; both apps share the DB but
  never run conflicting migrations.
- **NFR-2**: Standard Orchid patterns only (Screen/Table/Row/Action, no custom SPA); vertical
  stacked forms.
- **NFR-3**: List screens paginate server-side; default 25/page; searchable by the entity's natural
  key (name/CNP/phone/number).
- **NFR-4**: brotli-fast local boot: `composer run dev` / Valet serve, Pint-clean PHP.

## User Stories

1. As an **org-admin** I create a clinic, assign doctors, and see its agenda so I can run daily ops.
2. As a **doctor** I open today's agenda, start a visit from an appointment, add diagnosis +
   prescription, and finalize — so the visit file is complete in one place.
3. As a **doctor** I open a patient and see every appointment/visit/prescription/invoice from one
   command bar so I don't hunt across menus.
4. As a **pharmacist** I see routed prescriptions, check stock, record a sale — so fulfillment is
   one flow.
5. As a **pharmacist** I get low-stock alerts and raise a purchase order so shelves stay filled.
6. As an **org-admin** I open an invoice, see its visit/appointment lineage, record payment, and
   print — so billing is traceable.
