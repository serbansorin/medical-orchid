# Details & Design: ★ Appointment Detail

## Review of legacy (`ViewAppointment` + `AppointmentInfolist`)

**What exists**: thin view page (header has only Edit) + 6-section infolist — appointment overview
(patient/date/time/status), details (type/duration/clinic/phone/address/notes), patient details
(name/phone/email/DOB/gender/CNP), **patient allergies repeatable** (allergen/severity/reaction —
good safety pattern, keep), doctor info, system info (ids).

**Gaps we fix in Orchid**:
1. No state transitions (no confirm/cancel/no-show/complete; status edited as raw field).
2. No **Start visit** — nothing creates the `medical_visits` row; visit linkage is manual.
3. No related lists — appointment's own relations (`appointment_note`, `products`,
   `diagnostics`, `billing_invoices`, `medical_records`, `medical_letters`, `referrals`,
   `lab_results`, `vital_signs`, `vaccinations`, `medical_attachments`, `medical_certificates`,
   `medicalHistories`) are invisible here.
4. No invoice/payment visibility (`resolvedBillableTotal()`, `isPaid()`, `markAsPaid()` model
   logic exists but has no UI home).
5. Allergy block shows but nothing warns when booking a conflicting product/medication.

## Relationship inventory (Appointment model)

BelongsTo: `clinic`, `doctor`, `patient`, `product` (primary). HasMany/BelongsToMany:
`appointment_note`, `products` (via `appointment_product`), `diagnostics` (via
`patient_diagnostic_appointments`), `billing_invoices`, `medical_records`, `medical_letters`,
`medical_certificates`, `medical_attachments`, `lab_results`, `vital_signs`, `vaccinations`,
`referrals`, `medicalHistories`. Model helpers: `markAsPaid()`, `markAsCancelled()`,
`resolvedBillableTotal()`, `isPending()`, `isPaid()`, scopes `pending/scheduled/paid/search`.

## Design

- **List**: table — date/time, patient (link), doctor, clinic, type, status badge, billable total
  (`resolvedBillableTotal`), paid icon (`isPaid`), linked-visit icon. Filters: date range, doctor,
  clinic, status (pending/scheduled/completed/cancelled/no-show), type, unpaid-only. Search:
  patient name/phone. Day-agenda preset (today, this doctor).
- **Form**: patient (Relation + quick-create), doctor (scoped to clinic), clinic, date/time/
  duration, type, reason/notes, products multi-add (`appointment_product`), status default
  scheduled. Validation: doctor availability (schedule + time-off overlap check), allergy warning
  block when patient has severe allergies (kept from legacy pattern, shown — not blocking).
- **★ Detail Screen** (`platform/appointments/{id}?tab=`):
  - **Header view**: patient/doctor/clinic/slot/type/duration + status badge + billable total +
    paid badge + linked visit chip (once created). Allergy alert strip if severe allergies.
  - **Command bar**: **Start visit** (once; creates `medical_visits`, status→in-progress, redirect
    to ★ Visit) · Confirm / Cancel (reason modal → `markAsCancelled`) / No-show / Complete ·
    Add note · Add product/service · Link diagnostic · Create prescription (prefilled) ·
    Create invoice (lines from `products`) · Record payment (→ `markAsPaid` path).
  - **Tabs**: 1. Overview (details + patient snapshot + doctor + clinic, as legacy sections) |
    2. Notes (`appointment_note` thread) | 3. Products & services (`products`) | 4. Diagnostics
    (linked `diagnostics`) | 5. Clinical linked (`medical_records`, `medical_letters`,
    `lab_results`, `vital_signs`, `referrals`, `vaccinations`) | 6. Visit (status + link once
    created) | 7. Billing (`billing_invoices` + totals + paid state) | 8. History (status audit).
- **Rules**: Start visit hidden once visit exists; completing visit sets appointment→completed;
  payment never exceeds `resolvedBillableTotal`.
