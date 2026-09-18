# Details & Design: ★ Patient Detail (sophisticated overview)

## Source analysis (what we are porting)

Two legacy sources define "patient overview" and both must be honored:

1. **Filament `ViewPatient` + `PatientInfolist`** — sub-navigation (Overview, Workspace,
   Medication Plan, Clinical Record), 5 infolist sections (overview, diagnostics workspace,
   clinical snapshot, care coordination, quick actions — bottom), header actions (open-diagnosis
   jump, Edit), alert widget (**disabled** — `is_abnormal` column issue; we fix by guarding
   nullable columns), and **6 quick actions** (quick prescription, medical note, record vitals,
   order labs, schedule appointment, start chat if portal account).
2. **React `PatientDetailsPage`** — header + alerts strip + tabs: **overview, appointments,
   prescriptions, vitals, labs, history, documents**; state spans patient, appointments,
   prescriptions, conditions, allergies, medical histories, treatment plans, vaccinations,
   surgical + family history, documents; modals for prescription + history.

## Relationship inventory (Patient model — every list below is a real Eloquent relation)

BelongsTo/single: `user` (portal account), `patient_health_profile`, `defaultPharmacy`.
HasMany/core clinical: `appointments`, `medical_visits`, `diagnostics` (+`activeDiagnostics`),
`prescriptions` (+`prescribedMedications`), `reports` (+`vital_signs`/`vitalSigns`,
`lab_results`, `patient_conditions`, `patient_allergies`/`allergies`, `conditions`,
`vaccinations`, `family_histories`, `surgical_histories`, `medicalHistories`,
`hospitalizations`), documents (`documents`, `medical_attachments`), comms
(`medical_letters`, `medical_certificates`, `consent_forms`, `referrals`), meds adherence
(`declaredMedications`, `medicationLogs`, `medicationReminders`, `medicationSideEffects`),
programs (`enrolledPrograms`, `exerciseLogs`), money (`billing_invoices`, `patient_insurances`,
`patient_insurance_policies`), org (`clinics`, `doctors` via `doctor_patient_relationships`,
`pharmacies`/`preferredPharmacies`, `emergency_contacts`).

## Design

- **List**: table — name (link→detail), CNP, phone, age, insurance status, last visit, open
  balance, alerts dot (severe allergy / abnormal vitals). Filters: clinic, insurance,
  has-open-balance, has-alerts. Search: name/CNP/phone.
- **Create/Edit form**: identity (name/CNP/DOB/gender/blood), contact (phone/email/address/city/
  county), insurance (provider + policy + status), emergency contacts repeatable, clinic attach,
  portal-account link (`user`), notes.
- **★ Detail Screen** (`platform/patients/{id}?tab=`):
  - **Header view**: avatar/initials, name, age/DOB, CNP, phone/email, blood type, insurance badge,
    portal-account badge, assigned doctors, open balance. **Risk strip** below header (severe
    allergies, abnormal vitals ≤24h, unsigned Rx, overdue vaccinations) — the fixed
    `PatientAlertWidget`.
  - **Command bar**: Edit · New appointment · Start visit · Quick prescription · Add note ·
    Record vitals (BP/HR/temp/SpO2/RR/weight/height/glucose + auto-BMI, as legacy) · Order labs
    (legacy test catalogue + priority) · Write letter · New invoice · Upload document · Start chat
    (if `user_id`) · Open diagnosis workspace (diagnostic selector → workspace tab).
  - **Tabs (each = one relationship list, latest-first, 10/page + "view all" to the full screen)**:
    1. **Overview** — clinical snapshot: latest vitals, active conditions/allergies chips, open Rx,
       next appointment, recent activity timeline (mixed appointments/visits/Rx/reports).
    2. **Appointments** (`appointments` + `appointment_note` preview).
    3. **Visits** (`medical_visits` + episode/service-line counts).
    4. **Diagnostics** (`diagnostics`, status + linked conditions/symptoms counts).
    5. **Prescriptions** (`prescriptions` + `prescribedMedications` + fulfillment status).
    6. **Medication plan** (`declaredMedications`, `medicationLogs`, `medicationReminders`,
       `medicationSideEffects`, `treatmentPlans` via patient_treatments) — ports the Filament
       Medication Plan sub-page.
    7. **Vitals** (`vital_signs` chart-ready table: date, BP, HR, temp, SpO2, glucose, BMI).
    8. **Labs & exams** (`lab_results`/`patient_exam_results` + files: pending vs completed).
    9. **Conditions & allergies** (`conditions`, `allergies`, `patient_conditions`).
    10. **Histories** (`medicalHistories`, `hospitalizations`, `family_histories`,
        `surgical_histories`, `patient_health_profile` summary).
    11. **Vaccinations & prevention** (`vaccinations`, prevention history, enrolled programs).
    12. **Documents** (`documents`, `medical_attachments`, `clinical_record_files`).
    13. **Letters & certificates** (`medical_letters`, `medical_certificates`, `consent_forms`,
        `referrals`).
    14. **Care team** (`doctors` relationships, `preferredPharmacies`/`defaultPharmacy`,
        `emergency_contacts`, `clinics`).
    15. **Insurance** (`patient_insurances`, `patient_insurance_policies`).
    16. **Invoices & payments** (`billing_invoices` + balance per invoice).
  - **Roles**: doctor full; receptionist sees Overview/Appointments/Invoices + Notes only;
    pharmacist sees nothing here (uses Fulfillment); org-admin all read.
