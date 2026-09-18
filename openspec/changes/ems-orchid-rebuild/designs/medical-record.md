# Details & Design: Medical Records

## Details
- **Menu**: Medical → Medical Records · **Models**: `MedicalRecord` (`medical_records`),
  `MedicalAttachment`/`ClinicalRecordFile`/`MedicalCertificate`/`ConsentForm`/`DoctorNote`.
- **Purpose**: generic clinical file drawer per patient (anything not a prescription/letter).

## Design
- **List**: table — date, patient (link), doctor, type (record/note/certificate/consent), title,
  appointment/visit link. Filters: type, doctor, date range. Search: patient/title.
- **Form**: rows — patient, type (Selection), title, rich content (TinyMCE/CK via Orchid),
  appointment/visit links (optional Relations), file Upload (multiple), signed Switcher.
- **Roles**: doctor full on own patients; org-admin read; pharmacist hidden.
- **Notes**: created fastest from ★ Visit detail ("Add note") with patient/visit prefilled.
