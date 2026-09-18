# Details & Design: Fulfillment Queue

## Details
- **Menu**: Farmacie → Fulfillment · **Models**: `prescription_routing`, `prescription_fulfillments`
  (+ parent `prescriptions`, `prescription_medications`).
- **Purpose**: pharmacist worklist — routed prescriptions waiting to be dispensed.

## Design
- **List**: table — routed date, Rx number (link→Rx read view), patient, doctor, medications count,
  priority/status, stock-ok badge (all lines available in `pharmacy_inventory`?). Filters:
  pending/done, date. Search: Rx/patient.
- **Dispense action** (row + bulk): modal — confirm quantities per line (adjust for partial stock),
  creates `pharmacy_sale` (type=fulfillment) + `prescription_fulfillments` row, decrements
  inventory, sets Rx status→dispensed. Partial dispense keeps line pending with note.
- **Scope**: assigned pharmacy only. Read-only Rx clinical content; no editing of prescription.
