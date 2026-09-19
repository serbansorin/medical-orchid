# Medical-Orchid — Master Plan

**Created:** 2026-09-18 · **Status:** 🚀 Steps 0–6 complete — full EMS surface live at https://med-orch.test (65 screens, 6 role-gated sections, zero 500s on 83-route smoke) · next: scoping pass (FR-4) + Step 7 (Sistem) · Step 4 screens implemented 2026-09-19 (Prescriptions, Records, Letters+Templates, Diagnostics, 5 Catalogs, Questionnaires — route registration pending)
**Source:** `legacy-medical` (Laravel 12 + Filament 4, 150 tables, DB `medikode_laravel`)
**Target:** `medical-orchid` — Laravel (latest) + Orchid, same DB, `https://med-orch.test`
**Method:** spec-driven (OpenSpec) — this file tracks progress across changes.

## Change map

| Change | Path | Status |
|---|---|---|
| ems-orchid-rebuild (proposal/specs/design/tasks) | `openspec/changes/ems-orchid-rebuild/` | ✅ Planned |
| 24 page designs (details + design each) | `openspec/changes/ems-orchid-rebuild/designs/` | ✅ Planned |
| Step 0 scaffold | — | ✅ Done |
| Steps 1–6 implementation (one step per menu link) | `tasks.md` | ✅ Done (65 screens live) |
| Scoping pass (FR-4) + model field-drift pass | `tasks.md` notes | ⬜ Follow-up |
| Step 7 (Sistem: menus mirror, imports, articles + cutover) | `tasks.md` | ⬜ Queued |

## Menu → step → design file index

| # | Menu link | Step | Design file |
|---|---|---|---|
| 1 | Dashboard (admin/doctor/pharmacist; doctor = 10-widget React HomePage port) | 1 | `designs/dashboard-{admin,doctor,pharmacist}.md` |
| 2 | Organizations | 2 | `designs/organization.md` |
| 3 | Clinics | 2 | `designs/clinic.md` |
| 4 | Pharmacies | 2 | `designs/pharmacy.md` |
| 5 | Users & Roles | 2 | `designs/users-roles.md` |
| 6 | Specialties | 2 | `designs/specialty.md` |
| 7 | ★ Patients | 3 | `designs/patient.md` |
| 8 | ★ Appointments | 3 | `designs/appointment.md` |
| 9 | ★ Medical Visits | 3 | `designs/medical-visit.md` |
| 10 | Doctor Schedules | 3 | `designs/doctor-schedule.md` |
| 11 | Prescriptions | 4 | `designs/prescription.md` |
| 12 | Medical Records | 4 | `designs/medical-record.md` |
| 13 | Medical Letters (+Templates) | 4 | `designs/medical-letter.md` |
| 14 | Patient Diagnostics | 4 | `designs/patient-diagnostic.md` |
| 15 | Catalogs (5 screens) | 4 | `designs/catalogs.md` |
| 16 | Questionnaire Templates | 4 | `designs/questionnaire-template.md` |
| 17 | Fulfillment queue | 5 | `designs/fulfillment.md` |
| 18 | Inventory | 5 | `designs/pharmacy-inventory.md` |
| 19 | Sales | 5 | `designs/pharmacy-sale.md` |
| 20 | Purchase Orders | 5 | `designs/purchase-order.md` |
| 21 | Suppliers & Deliveries | 5 | `designs/supplier-delivery.md` |
| 22 | ★ Invoices | 6 | `designs/billing-invoice.md` |
| 23 | Payments | 6 | `designs/payment.md` |
| 24 | Products & Services | 6 | `designs/product.md` |
| 25 | Promotions / Loyalty | 6 | `designs/promotion-loyalty.md` |
| 26 | Sistem (menus mirror, imports, articles) | 7 | `designs/system.md` |

★ = command-bar detail screen.

## Key flows (lineage)

- Appointment → (Start visit) → Medical Visit → diagnostics/prescriptions/services →
  Invoice → Payments. Every child stores `appointment_id`/`medical_visit_id`.
- Prescription → (Route) → Fulfillment queue → (Dispense) → Pharmacy Sale → stock decrement.
- Low stock → Purchase Order → Delivery → Receive → stock increment.

## Scaffold runbook (Step 0, exact commands)

```bash
cd /Users/serbansorin/imc-dev
composer create-project laravel/laravel medical-orchid-tmp   # latest Laravel
mv medical-orchid-tmp/* medical-orchid-tmp/.* medical-orchid/ 2>/dev/null
cd medical-orchid && composer require orchid/platform
php artisan orchid:install
valet link med-orch && valet secure med-orch   # → https://med-orch.test
# .env: DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432
#       DB_DATABASE=medikode_laravel DB_USERNAME=main DB_PASSWORD=1234
php artisan db:show                             # expect ~150 tables
cp ../legacy-medical/app/Models/*.php app/Models/
cp -r ../legacy-medical/app/Enums app/Enums
php artisan tinker --execute="echo App\Models\Patient::count();"
```

Rules: additive-only migrations; flat `app/Models/`; Orchid patterns only; `en`+`ro`;
Pint + browser pass per step.
