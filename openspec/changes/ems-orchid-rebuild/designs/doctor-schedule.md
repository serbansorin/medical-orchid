# Details & Design: Doctor Schedules

## Details
- **Menu**: Cabinet → Schedules · **Models**: `DoctorSchedule` (`doctor_schedules` + clinic_id),
  `DoctorTimeOff` (`doctor_time_offs`).
- **Legacy ref**: `DoctorScheduleResource` (Doctor + Receptionist).

## Design
- **List**: table — doctor (link), clinic, weekday, start–end, slot minutes, active. Filters:
  doctor, clinic, weekday. Week-grid view out of scope v1 (list + weekday filter).
- **Form**: rows — doctor (Relation), clinic (Relation), weekday (Selection), start/end (Time),
  slot duration, valid-from/to, active. Inline `Layout::table` of upcoming time-off on edit +
  "Add time off" modal (date range + reason).
- **Roles**: doctor edits own schedule; receptionist views + books against it; org-admin all.
- **Validation**: overlapping ranges for same doctor/clinic/weekday rejected.
