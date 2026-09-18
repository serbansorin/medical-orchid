# Details & Design: ★ Medical Visit Detail (sophisticated)

## Purpose

The visit file: everything done during the encounter is created or linked here. Created from ★
Appointment ("Start visit") or standalone (walk-in, optional appointment link). Finalizing the
visit completes the appointment and freezes the file.

## Relationship inventory (MedicalVisit + satellites — all real tables/relations)

BelongsTo: `clinic`, `patient`, `doctor`, `appointment`, `episode` (`visit_episodes`:
clinic/patient/primary-doctor scope). HasMany: `serviceLines` (`visit_service_lines` → `product`,
→ `invoice`), `prescriptions`, `invoices`. Reachable via patient+appointment scope (shown as
"visit-linked" lists, created prefilled from here): `patient_diagnostics`, `medical_records`,
`patient_reports` (notes/vitals), `patient_exam_results` (labs), `medical_letters`,
`medical_attachments`/`clinical_record_files`, `referrals`, `billing_invoice_items` (via invoice),
`prescription_medications` (via prescriptions).

## Design

- **List**: table — date, patient (link), doctor, clinic, source appointment, episode, status
  (in-progress/completed/cancelled), service-lines count, Rx count, invoiced badge, balance.
  Filters: date range, doctor, clinic, status, uninvoiced-only. Search: patient.
- **★ Detail Screen** (`platform/visits/{id}?tab=`):
  - **Header view**: patient (link→ ★ Patient), doctor, clinic, date/time, source appointment link,
    episode chip, status badge, totals (services sum, invoiced, balance). Allergy/risk strip
    (inherited from patient) — safety first.
  - **Command bar**: Add diagnostic · **Add prescription** (prefilled patient/doctor/visit) ·
    Add procedure/service line (catalog `products` + qty + price override → `visit_service_lines`) ·
    Add note · Record vitals · Order labs · Attach file · Write letter · Create referral ·
    **Create invoice** (pulls uninvoiced service lines) · **Finalize visit** (validates ≥1
    diagnostic or note; status→completed; appointment→completed; locks editing, org-admin override).
  - **Tabs (visit timeline first, then one list per relationship)**:
    1. **Timeline** — chronological feed of everything added this visit (diagnostics, Rx, services,
       notes, vitals, labs, files, invoice events). The money view of the encounter.
    2. **Diagnostics** (linked `patient_diagnostics` + conditions/symptoms).
    3. **Prescriptions** (`prescriptions` + medications + sign/route shortcuts).
    4. **Services & products** (`serviceLines` → product, qty, price, invoiced flag).
    5. **Notes & vitals** (visit-scoped `patient_reports`).
    6. **Labs & exams** (visit-scoped `patient_exam_results` + files; pending vs done).
    7. **Letters & referrals** (visit-scoped `medical_letters`, `referrals`).
    8. **Attachments** (`medical_attachments`/`clinical_record_files`).
    9. **Episode** (`episode` info + sibling visits in the same episode).
    10. **Invoice** (`invoices` + lines + payments + balance; create shortcut if missing).
- **Rules**: finalized visits lock all child creation (override logged); invoice creation only from
  uninvoiced lines; every child stores `medical_visit_id` (+ `appointment_id`) so ★ Patient detail
  aggregates per visit.
