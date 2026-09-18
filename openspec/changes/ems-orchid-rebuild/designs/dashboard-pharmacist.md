# Details & Design: Pharmacist Dashboard

## Details
- **Route**: `platform/dashboard` for `pharmacist` · **Source tables**: `pharmacy_inventory`,
  `prescription_routing`/`prescription_fulfillments`, `pharmacy_sales`, `pharmacy_purchase_orders`.
- **Purpose**: stock risks + fulfillment work + today's takings.

## Design
- **Layout**: `Layout::metrics` (low-stock SKUs, pending fulfillments, today's sales total, open
  purchase orders) + `Layout::table` Fulfillment queue (routed prescriptions: patient, Rx number,
  medications count, "Dispense" → fulfillment screen) + `Layout::table` Low-stock list (SKU,
  on-hand, reorder point, "New PO" shortcut) + `Layout::table` Today's sales.
- **Interactions**: Dispense creates `pharmacy_sale` + `prescription_fulfillments` row and decrements
  `pharmacy_inventory`; New PO jumps to Purchase Order create with supplier pre-selected.
- **Scope**: assigned pharmacy only (`pharmacy_staff` / `user_workplaces`).
- **i18n**: `en`+`ro`.
