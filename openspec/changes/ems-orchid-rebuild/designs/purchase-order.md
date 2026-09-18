# Details & Design: Purchase Orders

## Details
- **Menu**: Farmacie → Purchase Orders · **Models**: `PharmacyPurchaseOrder`
  (`pharmacy_purchase_orders`), items (`pharmacy_purchase_order_items`).
- **Legacy ref**: `PharmacyPurchaseOrderResource`.

## Design
- **List**: table — PO number, date, supplier, lines count, total, status
  (draft/sent/received/cancelled). Filters: supplier, status. Search: number.
- **Form**: rows — pharmacy, supplier (Relation), expected date, lines editor (product → qty →
  unit cost), status flow: Save draft → Send → **Receive** (modal per-line received qty → increments
  `pharmacy_inventory` batches) → Cancel. Receiving twice blocked (received lines locked).
- **Shortcut**: "New PO" from low-stock rows prefills supplier + lines.
