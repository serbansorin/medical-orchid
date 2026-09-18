# Details & Design: Clinics

## Details
- **Menu**: Organizare → Clinics · **Model**: `Clinic` (`clinics`: organization_id, name, slug,
  code, type, is_active) · **Relations**: departments (`clinic_departments`), doctor assignments
  (`clinic_doctor_assignments`), patients (`clinic_patient`), users (`clinic_user`), products
  (`clinic_product`).
- **Legacy ref**: `ClinicResource` (Admin + Doctor read).

## Design
- **List**: table — name (link), org, code, type, doctors count, patients count, active badge.
  Filters: organization, type, active. Search: name/code.
- **Form**: rows — organization (Relation, locked for org-admin), name, code (unique), type,
  contact/address, is_active. On edit, `Layout::tabs`: Info | Departments (has-many mini-table +
  inline add) | Doctor assignments (attach doctor + specialty + active dates) | Patients (read-only
  counts + attach existing).
- **Roles**: org-admin manages own org's clinics; doctor sees assigned clinics read-only.
- **Notes**: `code` used in URLs/scheduling — immutable after create (disabled input on edit).
