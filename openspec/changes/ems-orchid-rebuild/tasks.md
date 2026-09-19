# Tasks: EMS on Orchid — step per menu link

> Each step = one menu link (one Orchid Screen set: List + Create/Edit, or ★ Detail).
> Work steps in order; each step ends with: Pint clean + browser click-through at
> `https://med-orch.test`. Page details live in `designs/<page>.md`.

## Step 0 — Scaffold & shared DB (prerequisite)
- [x] Git bootstrap: `git init -b main`, root `.gitignore`, private repo
  `serbansorin/medical-orchid` (commit + push)
- [x] Laravel 13.32 skeleton + `orchid/platform` 14.53; `orchid:install` with shared-DB-guarded
  migrations (legacy `roles` untouched, `role_users` no-op → `model_has_roles` bridge,
  `users.permissions` additive, `attachments`/`attachmentable` added)
- [x] Valet: `link med-orch` + `secure med-orch` → https://med-orch.test (login verified in browser)
- [x] `.env`: pgsql `medikode_laravel` + APP_URL; migrations additive-only verified (150 → 152 tables)
- [x] Copied 136 verbatim models + 21 enums from `legacy-medical`; smoke 130/138 clean —
  8 legacy models target retired tables (FamilyHistory, PatientAllergy, PatientCondition,
  PatientExamResultFile, PatientTreatment, SurgicalHistory, Vaccination, VitalSign) — resolve at
  their page steps
- [x] Roles verified in shared RBAC (super_admin/org_admin/doctor/pharmacist/clinic_receptionist);
  dev admin `admin@med-orch.test`; `AGENTS.md` updated — role→dashboard redirect moves to Step 1
  (needs the dashboard routes)

## Step 1 — Dashboards (3 views, one panel)
- [x] Admin dashboard KPIs · `designs/dashboard-admin.md`
- [x] Doctor dashboard (agenda/open visits/Rx to sign) · `designs/dashboard-doctor.md`
- [x] Pharmacist dashboard (low stock/fulfillment/sales) · `designs/dashboard-pharmacist.md`
- [x] Role→dashboard redirect (`platform.dashboard` → admin/doctor/pharmacist by role) — deferred item from Step 0

## Step 2 — Organizare
- [x] Organizations · `designs/organization.md`
- [x] Clinics (+departments, assignments) · `designs/clinic.md`
- [x] Pharmacies (+staff) · `designs/pharmacy.md`
- [x] Users & Roles · `designs/users-roles.md`
- [x] Specialties · `designs/specialty.md`

## Step 3 — Cabinet (doctor flow)
- [x] ★ Patients (list + command-bar detail) · `designs/patient.md`
- [x] ★ Appointments (list + command-bar detail, start-visit transition) · `designs/appointment.md`
- [x] ★ Medical Visits (list + command-bar detail, aggregates visit work) · `designs/medical-visit.md`
- [x] Doctor Schedules (+time off) · `designs/doctor-schedule.md`

## Step 4 — Medical
- [x] Prescriptions (+medications, routing, fulfillment link) · `designs/prescription.md`
- [x] Medical Records & attachments · `designs/medical-record.md`
- [x] Medical Letters (+templates) · `designs/medical-letter.md`
- [x] Patient Diagnostics · `designs/patient-diagnostic.md`
- [x] Catalogs: Conditions/Symptoms/Treatments/Procedures/Medical Codes · `designs/catalogs.md`
- [x] Questionnaire Templates · `designs/questionnaire-template.md`

## Step 5 — Farmacie
- [x] Fulfillment queue (prescriptions routed → dispense) · `designs/fulfillment.md`
- [x] Inventory · `designs/pharmacy-inventory.md`
- [x] Sales (+items, receipts, payments) · `designs/pharmacy-sale.md`
- [x] Purchase Orders (+items) · `designs/purchase-order.md`
- [x] Suppliers & Deliveries · `designs/supplier-delivery.md`

## Step 6 — Facturare
- [x] ★ Invoices (+items, command-bar detail) · `designs/billing-invoice.md`
- [x] Payments · `designs/payment.md`
- [x] Products & Services · `designs/product.md`
- [x] Promotions / Loyalty (read-mostly) · `designs/promotion-loyalty.md`

## Steps 2–6 — notes & follow-ups (built 2026-09-19)
- 65 screens live under `/admin` (lists + create/edit + 4 ★ details); all wired in `routes/platform.php`
  with a role-gated six-section menu; authed smoke: **83/83 routes no 500s**, Pint clean.
- Orchid interop fixes: `AsSource` added additively to all 136 ported models; vendor overrides
  (`resources/views/vendor/platform/layouts/{base,block}.blade.php`) drop Orchid's redundant `__()`
  on already-translated titles (macOS case-insensitive FS collision with lang group files).
- ★ Start-visit flow (`VisitService::startFromAppointment`) and dispense/receive flows are transactional.
- **Follow-ups (remaining 20–40%):**
  1. **Org/clinic/pharmacy scoping helper (FR-4)** — one-place scope constraint not yet applied to lists.
  2. **Verbatim-model field drift** — sale items/receipts/payments/deliveries/suppliers, `MedicalRecord`
     fillable, `QuestionnaireTemplate` fillable; currently worked around via explicit attribute assignment
     (`forceFill`); needs an additive model-alignment pass.
  3. Print/PDF for invoices (design stub), loyalty adjustment reason logging, non-super role walkthrough
     (demo passwords), Step 7 (Sistem: menus mirror, imports, articles) + EN/RO audit.

## Step 7 — Sistem & cutover
- [ ] Menus & Permissions mirror (read-only), Imports, Articles · `designs/system.md`
- [ ] EN+RO audit pass, role walkthroughs (admin/doctor/pharmacist), cutover note
