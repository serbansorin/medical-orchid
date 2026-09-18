# Details & Design: Questionnaire Templates

## Details
- **Menu**: Medical → Questionnaires · **Models**: `QuestionnaireTemplate`
  (`questionnaire_templates`), responses (`questionnaire_responses`, `patient_questionnaires`,
  `patient_scheduled_questionnaires`).
- **Legacy ref**: `QuestionnaireTemplateResource` (Admin + Doctor).

## Design
- **List**: table — name, version, questions count, active. Search: name.
- **Form**: rows — name, description, version (auto-bump on edit), active + questions builder
  (repeatable Matrix/builder rows: label, type [text/number/select/scale/date], required, options
  JSON). Stored as JSON column — no per-question tables.
- **Use**: templates attach to visits/diagnostics (link-only v1; patient-fill UI out of scope).
- **Roles**: org-admin/doctor-senior manage; doctors use.
