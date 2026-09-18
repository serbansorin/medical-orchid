# Details & Design: Organizations

## Details
- **Menu**: Organizare → Organizations · **Model**: `Organization` (`organizations`) ·
  **Relations**: `clinics`, `pharmacies`, `products`, users via `user_workplaces`.
- **Legacy ref**: `OrganizationResource` (Admin). Columns: name, slug, type, status, contact.

## Design
- **List**: `Layout::table` — TD: name (link→edit), type, clinics count, pharmacies count,
  is_active badge. Filters: type, active. Search: name.
- **Form** (`Layout::rows` stacked): name, slug (auto), type (Selection), contact fields
  (email/phone/address), logo (Upload), is_active (Switcher). Side `Layout::legend` on edit showing
  clinics/pharmacies counts.
- **Roles**: super-admin full; org-admin sees own org read-only (edit contact only).
- **Notes**: slug uniqueness validated; deleting org blocked if clinics/pharmacies exist.
