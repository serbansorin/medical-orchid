# Details & Design: Catalogs (Conditions, Symptoms, Treatments, Procedures, Medical Codes)

## Details
- **Menu**: Medical → Catalogs (one menu group, five screens) · **Tables**: `conditions`,
  `symptoms`, `treatments`, `treatment_plans`, `procedures` (+`procedure_diagnosis_codes`),
  `medical_codes` (~MBs, read-mostly), `drug_interactions`, `medications`/`medication_list`.
- **Legacy ref**: `ConditionResource`, `SymptomResource`, `TreatmentResource`, `MedicalCodeResource`.

## Design
- **Pattern per catalog**: list table (name/code, active, usage count) + simple rows form
  (names ro/en, code, description, severity/type where applicable, active). Search by name/code.
- **Medical Codes**: read-only list + search (no create/edit except super-admin); used as Relation
  options in Diagnostics/Prescriptions/Visits.
- **Medications catalog**: list (name, substance, form, strength) + form; the prescription screen
  consumes it. `drug_interactions` shown as warning block on prescription when pair detected
  (read-only check, v1: manual lookup display).
- **Roles**: org-admin manages; doctor uses (read + select); pharmacist reads medications only.
