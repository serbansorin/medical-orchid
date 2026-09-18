# Details & Design: Users & Roles

## Details
- **Menu**: Organizare → Users & Roles · **Models**: `User` (`users`), `Role`/`Permission`
  (Spatie tables: `roles`, `permissions`, `model_has_roles`, `role_has_permissions`),
  `user_workplaces` (org/clinic/pharmacy assignment), `user_specialties`.
- **Legacy ref**: `UserResource`, `RolePermissionResource`, Shield UI (do NOT port Shield).

## Design
- **Users list**: table — name (link), email, roles badges, workplace (org/clinic/pharmacy),
  last login, active. Filters: role, workplace, active. Search: name/email.
- **User form**: rows — name, email, password (optional on edit), roles (Checkboxes multi),
  workplace assignment (org → clinic/pharmacy cascading Relations), specialties (multi, doctors
  only), active. Role assignment restricted: only super-admin grants super-admin/org-admin.
- **Roles list**: table — name, guard, users count. Role form: name + permission matrix
  (grouped Checkboxes mirroring legacy `permissions` rows, mapped to Orchid `access()` keys).
- **Scope**: org-admin sees/creates users only within own org; cannot escalate roles.
- **i18n**: `en`+`ro` for role display names.
