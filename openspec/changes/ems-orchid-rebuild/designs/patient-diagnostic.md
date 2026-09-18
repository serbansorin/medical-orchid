# Details & Design: Patient Diagnostics

## Details
- **Menu**: Medical → Diagnostics · **Model**: `PatientDiagnostic` (`patient_diagnostics`) + pivots
  `patient_diagnostic_appointments`, `patient_diagnostic_conditions`; symptoms via
  `diagnostic_symptoms`/`patient_symptoms`.
- **Legacy ref**: `PatientDiagnosticResource` (+ List/View/Edit pages).

## Design
- **List**: table — title, patient (link), doctor, status (active/referred/completed/cancelled),
  start date, linked conditions count. Filters: status, doctor. Search: patient/title.
- **Form**: rows — patient, doctor, title, chief complaint (textarea), status, start/end dates,
  notes; Relations multi-select: conditions (ICD), symptoms, appointments. Edit tabs: Info |
  Conditions | Appointments | Reports (linked `patient_reports` of diagnostic type, create-inline).
- **Roles**: doctor full; org-admin read; pharmacist hidden.
