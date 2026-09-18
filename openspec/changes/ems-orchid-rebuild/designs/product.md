# Details & Design: Products & Services

## Details
- **Menu**: Facturare → Products & Services · **Models**: `Product` (`products`: org/clinic/doctor
  levels), pivots `clinic_product`, `doctor_product`, `appointment_product`.
- **Legacy rule (keep)**: services/products modeled at organization → clinic → doctor levels;
  doctors may inherit clinic catalog entries and override pricing or clone as personal offerings.

## Design
- **List**: table — name (link), level badge (org/clinic/doctor), owner, base price, active.
  Filters: level, owner (org/clinic/doctor), active. Search: name/code.
- **Form**: rows — owner level (Radio: organization/clinic/doctor + corresponding Relation), name
  ro/en, code, category, base price + currency, duration min (services), active; on edit show
  inheritance block (parent entry + override price) and usage counts.
- **Consumers**: visit service lines, appointment products, invoice lines, pharmacy sale lines
  (sellable flag).
