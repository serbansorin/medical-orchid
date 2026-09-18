# Details & Design: Pharmacy Inventory

## Details
- **Menu**: Farmacie → Inventory · **Model**: `PharmacyInventory` (`pharmacy_inventory`: pharmacy,
  product/medication, batch, expiry, quantity, reorder point).
- **Legacy ref**: `PharmacyInventoryResource` (Pharmacist panel).

## Design
- **List**: table — SKU/product (link), batch, expiry (red badge ≤30d, amber ≤90d), on-hand,
  reserved, available, reorder-point breach badge. Filters: pharmacy (org-admin), low-stock-only,
  expiring-only. Search: product/SKU/batch.
- **Form**: rows — pharmacy, product (Relation), batch, expiry date, quantity, reorder point,
  unit cost. Stock adjustments via dedicated modal (in/out + reason → audit columns), not raw
  quantity edits on the form.
- **Roles**: pharmacist manages assigned pharmacy; org-admin all; doctor hidden.
