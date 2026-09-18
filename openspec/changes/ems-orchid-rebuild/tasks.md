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
- [ ] Admin dashboard KPIs · `designs/dashboard-admin.md`
- [ ] Doctor dashboard (agenda/open visits/Rx to sign) · `designs/dashboard-doctor.md`
- [ ] Pharmacist dashboard (low stock/fulfillment/sales) · `designs/dashboard-pharmacist.md`

## Step 2 — Organizare
- [ ] Organizations · `designs/organization.md`
- [ ] Clinics (+departments, assignments) · `designs/clinic.md`
- [ ] Pharmacies (+staff) · `designs/pharmacy.md`
- [ ] Users & Roles · `designs/users-roles.md`
- [ ] Specialties · `designs/specialty.md`

## Step 3 — Cabinet (doctor flow)
- [ ] ★ Patients (list + command-bar detail) · `designs/patient.md`
- [ ] ★ Appointments (list + command-bar detail, start-visit transition) · `designs/appointment.md`
- [ ] ★ Medical Visits (list + command-bar detail, aggregates visit work) · `designs/medical-visit.md`
- [ ] Doctor Schedules (+time off) · `designs/doctor-schedule.md`

## Step 4 — Medical
- [ ] Prescriptions (+medications, routing, fulfillment link) · `designs/prescription.md`
- [ ] Medical Records & attachments · `designs/medical-record.md`
- [ ] Medical Letters (+templates) · `designs/medical-letter.md`
- [ ] Patient Diagnostics · `designs/patient-diagnostic.md`
- [ ] Catalogs: Conditions/Symptoms/Treatments/Procedures/Medical Codes · `designs/catalogs.md`
- [ ] Questionnaire Templates · `designs/questionnaire-template.md`

## Step 5 — Farmacie
- [ ] Fulfillment queue (prescriptions routed → dispense) · `designs/fulfillment.md`
- [ ] Inventory · `designs/pharmacy-inventory.md`
- [ ] Sales (+items, receipts, payments) · `designs/pharmacy-sale.md`
- [ ] Purchase Orders (+items) · `designs/purchase-order.md`
- [ ] Suppliers & Deliveries · `designs/supplier-delivery.md`

## Step 6 — Facturare
- [ ] ★ Invoices (+items, command-bar detail) · `designs/billing-invoice.md`
- [ ] Payments · `designs/payment.md`
- [ ] Products & Services · `designs/product.md`
- [ ] Promotions / Loyalty (read-mostly) · `designs/promotion-loyalty.md`

## Step 7 — Sistem & cutover
- [ ] Menus & Permissions mirror (read-only), Imports, Articles · `designs/system.md`
- [ ] EN+RO audit pass, role walkthroughs (admin/doctor/pharmacist), cutover note
