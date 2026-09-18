# Details & Design: Pharmacy Sales

## Details
- **Menu**: Farmacie → Sales · **Models**: `PharmacySale` (`pharmacy_sales`), items
  (`pharmacy_sale_items`), receipts (`pharmacy_receipts`), payments (`pharmacy_payments`,
  `cart_payments`).
- **Purpose**: OTC + fulfillment sales with receipt.

## Design
- **List**: table — number, date, customer/profile, type (OTC/fulfillment), lines count, total,
  payment status. Filters: date range, type, payment status. Search: number/customer.
- **Form**: rows — pharmacy, customer (Relation to `pharmacy_customer_profiles` + walk-in option),
  linked fulfillment (optional), lines editor (product → qty → price → discount, stock-checked),
  totals (auto), payment method + amount tendered (creates `pharmacy_payments` + receipt).
  Finalize locks the sale and decrements inventory.
- **Receipt**: finalized sale → Print/PDF receipt view.
- **Roles**: pharmacist full on assigned pharmacy; org-admin read + reports.
