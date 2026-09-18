# Details & Design: Payments

## Details
- **Menu**: Facturare → Payments · **Models**: `Payment` (`payments`), methods
  (`payment_methods`), carts (`carts`, `cart_items`, `cart_payments`) if online payments in scope.
- **Purpose**: money-in ledger across invoices and pharmacy receipts.

## Design
- **List**: table — date, payer/patient, linked invoice/sale, method, amount, reference, created_by.
  Filters: method, date range, linked-type. Search: reference/patient.
- **Form**: rows — patient, link type (invoice OR sale), amount (validated ≤ outstanding), method
  (Relation), reference, notes. Created fastest from ★ Invoice detail ("Record payment") prefilled;
  standalone create allowed for walk-in/adjustment payments.
- **Rules**: deleting a payment requires org-admin + reason; totals recalc on invoice/sale.
