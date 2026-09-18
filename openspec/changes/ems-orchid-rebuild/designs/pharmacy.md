# Details & Design: Pharmacies

## Details
- **Menu**: Organizare → Pharmacies · **Model**: `Pharmacy` (`pharmacies`) · **Relations**:
  organization, staff (`pharmacy_staff`), inventory (`pharmacy_inventory`), sales, suppliers.
- **Legacy ref**: `PharmacyResource` (Admin/Doctor/Pharmacist variants).

## Design
- **List**: table — name (link), organization, slug, verified badge (`verified_by`), staff count,
  active. Filters: organization, verified. Search: name/slug.
- **Form**: rows — organization (Relation), name, slug (auto), address/contact, license fields,
  verified (admin-only Switcher), is_active. Edit tabs: Info | Staff (attach user + role +
  active dates via `pharmacy_staff`) | Stock summary (read-only low-stock table linking to
  Inventory screen).
- **Roles**: org-admin manages own; pharmacist sees assigned pharmacy read-only (except stock ops
  on dedicated screens).
