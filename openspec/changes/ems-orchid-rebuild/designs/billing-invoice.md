# Details & Design: ★ Billing Invoice Detail (sophisticated)

## Review of legacy (`ViewBillingInvoice` + `HasFinancePageActions`)

**What exists**: finance menu action, open-document (PDF URL), mark-as-paid (confirm modal),
cancel (confirm modal). Solid status primitives — keep all four, but mark-as-paid must create a
real `payments` row (not just flip status) and取消 must void with reason.

## Relationship inventory (BillingInvoice model — all real relations)

BelongsTo: `appointment`, `clinic`, `doctor`, `patient`, `creator` (`created_by` → User).
HasMany: `items`/`billing_invoice_items` (aliased twice in model — normalize to `items`),
`payments`. Attributes: `document_url` (generated PDF). Status enum: draft/issued/paid/partial/
overdue/cancelled (align with legacy fix migration `..._status_enum_values`).

## Design

- **List**: table — number, date, patient (link), clinic/doctor, subtotal/discount/tax/total, paid,
  balance, status badge, overdue highlight, source icons (appointment/visit). Stats row (open total,
  overdue total — ports `BillingStatsWidget`). Filters: status, clinic, date range, unpaid-only,
  overdue-only. Search: number/patient/CNP.
- **Create form**: patient, clinic, doctor, appointment/visit links (**prefill lines** from
  `appointment_product` / uninvoiced `visit_service_lines`), lines editor (product/service → qty →
  unit price → discount → tax), notes; Save as draft.
- **★ Detail Screen** (`platform/invoices/{id}?tab=`):
  - **Header view**: number, patient (link→ ★ Patient), clinic/doctor, issue/due dates, totals
    block (subtotal − discount + tax = total, paid, **balance**), status badge, document link.
    **Lineage strip**: appointment → visit → services chain (each a link), so any invoice is
    traceable to the encounter.
  - **Command bar**: Add item (modal, draft/issued only) · Edit item · Remove item ·
    **Record payment** (modal: amount ≤ balance validated, method Relation, reference, notes →
    `payments` row; status auto paid/partial) · **Issue/Finalize** (draft→issued, locks lines) ·
    Mark overdue (auto-job + manual) · Print/PDF (`document_url` regenerate) · Open document ·
    Void/Cancel (reason modal,org-admin if payments exist → creates reversal note, never deletes).
  - **Tabs**:
    1. **Overview** — totals + lineage + status history.
    2. **Lines** (`items`: product, qty, unit price, discount, tax, line total; per-line visit link).
    3. **Payments** (`payments`: date, method, amount, reference, created_by; running remaining).
    4. **Lineage** (appointment detail snapshot → visit timeline snapshot → services that fed lines).
    5. **Patient context** (patient's other open invoices + lifetime balance — collection leverage).
    6. **History** (status transitions with who/when).
- **Rules**: payments validated against balance; issued+ invoices editable by org-admin only (logged);
  void with payments requires org-admin + reason; no hard deletes (financial audit).
