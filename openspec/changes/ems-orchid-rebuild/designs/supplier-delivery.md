# Details & Design: Suppliers & Deliveries

## Details
- **Menu**: Farmacie → Suppliers, Deliveries · **Models**: `PharmacySupplier`
  (`pharmacy_suppliers`), `PharmacyDelivery` (`pharmacy_deliveries`), zones (`delivery_zones`).
- **Legacy ref**: `PharmacySupplierResource`, `PharmacyDeliveryResource`.

## Design
- **Suppliers list**: table — name (link), contact, lead-time days, active. Form: name, contact/
  address, payment terms, lead time, active. Linked POs count on edit.
- **Deliveries list**: table — date, PO link, supplier, status (scheduled/in-transit/delivered),
  lines received/total. Form: PO (Relation), scheduled date, courier/tracking, status flow;
  marking delivered opens the PO receive modal (single path to stock increment — no double entry).
- **Roles**: pharmacist manages; org-admin all.
