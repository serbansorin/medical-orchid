# Details & Design: Medical Letters (+ Templates)

## Details
- **Menu**: Medical → Medical Letters, Medical Letter Templates · **Models**: `MedicalLetter`
  (`medical_letters`), `MedicalLetterTemplate` (`medical_letter_templates`).
- **Legacy ref**: `MedicalLetterResource`, `MedicalLetterTemplateResource` (Admin + Doctor).

## Design
- **Templates list**: table — name, type, active. Form: name, type, rich body with `{{placeholders}}`
  (patient/doctor/clinic/date), active. Admin/doctor-senior manage; others use.
- **Letters list**: table — date, patient (link), doctor, template, type, signed badge. Filters:
  type, doctor, signed. Search: patient.
- **Letter form**: rows — patient, template (Relation → prefills body with placeholders resolved),
  type, title, rich content, letter date, sign button (locks editing, stamps signed_by/at).
- **Print**: signed letter → Print/PDF view (blade, reuse legacy letter layout if present).
