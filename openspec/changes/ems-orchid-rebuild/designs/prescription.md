# Details & Design: Prescriptions

## Details
- **Menu**: Medical → Prescriptions · **Models**: `Prescription` (`prescriptions`), medications
  (`prescription_medications`), routing (`prescription_routing`), fulfillment
  (`prescription_fulfillments`).
- **Legacy ref**: `PrescriptionResource` (+ View page), `PrescriptionFulfillmentResource`.

## Design
- **List**: table — Rx number, date, patient (link), doctor, type, status (draft/signed/dispensed/
  cancelled), medications count, fulfillment badge. Filters: status, doctor, date range,
  unfulfilled-only. Search: Rx number/patient.
- **Form**: rows — patient, doctor (defaults current), clinic/visit link, type, diagnosis +
  ICD code, issue/valid dates; `Layout::table` medications editor (medication Relation from
  `medications` catalog + dosage/frequency/duration/instructions, add/remove rows); status flow
  buttons: Save draft → **Sign** (status→signed, stamps signed_by/at; editing locks) → Route to
  pharmacy (creates `prescription_routing` row, appears in Fulfillment queue) → Cancel.
- **Roles**: doctor creates/signs; pharmacist read + fulfills via Farmacie screens; org-admin all.
- **Notes**: e-signature integration is a stub hook on Sign (out of scope).
