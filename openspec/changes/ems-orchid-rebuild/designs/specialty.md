# Details & Design: Specialties

## Details
- **Menu**: Organizare → Specialties · **Model**: `Specialty` (`specialties`) · linked via
  `user_specialties` to users/doctors.
- **Legacy ref**: `SpecialtyResource`.

## Design
- **List**: table — name (link, ro+en), code, doctors count, active. Search: name.
- **Form**: rows — name_ro, name_en, code (unique), description, icon (optional), active.
- Pure CRUD; no detail screen. Used as Relation options in User/Doctor/Schedule screens.
