# Details & Design: Admin Dashboard

## Details
- **Route**: `platform/dashboard` for `super-admin`/`org-admin` · **Source tables**: `patients`,
  `appointments`, `billing_invoices`, `pharmacy_inventory`, `prescriptions`, `clinics`, `pharmacies`.
- **Purpose**: org-wide daily ops at a glance + drill-down links into each CRUD screen.

## Design
- **Layout**: Orchid `Screen` with `Layout::metrics` row (6 KPI cards) + two `Layout::table`
  blocks (Today's appointments, Open invoices) + `Layout::view` alert strip (low stock count,
  prescriptions pending fulfillment).
- **KPIs**: total patients (scoped to org) · today's appointments count · open invoices sum ·
  low-stock SKUs · prescriptions awaiting fulfillment · active clinics/pharmacies.
- **Interactions**: each metric links to its filtered list screen; tables show max 10 rows with
  "View all →" link. No forms on this screen.
- **Roles**: hidden for doctor/pharmacist (they get their own dashboards).
- **i18n**: `en`+`ro` labels for all card titles.
