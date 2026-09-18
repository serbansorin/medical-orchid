# Tasks: EMS on Orchid — step per menu link

> Each step = one menu link (one Orchid Screen set: List + Create/Edit, or ★ Detail).
> Work steps in order; each step ends with: Pint clean + browser click-through at
> `https://med-orch.test`. Page details live in `designs/<page>.md`.

## Step 0 — Scaffold & shared DB (prerequisite)
- [ ] `composer create-project laravel/laravel medical-orchid-tmp` (latest), move into
  `medical-orchid/`, `composer require orchid/platform`
- [ ] `php artisan orchid:install`, Valet link: `valet link med-orch`, `valet secure med-orch`
- [ ] `.env`: `DB_DATABASE=medikode_laravel`, same host/user/pass as legacy; verify
  `php artisan db:show` → 150 tables
- [ ] Copy `app/Models/*.php` (+ `Enums/`) from `legacy-medical`; tinker smoke per model
- [ ] Seed roles/permissions; role→dashboard redirect; `AGENTS.md` filled

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
