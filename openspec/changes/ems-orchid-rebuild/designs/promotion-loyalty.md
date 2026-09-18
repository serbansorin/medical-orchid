# Details & Design: Promotions / Loyalty

## Details
- **Menu**: Facturare → Promotions / Loyalty · **Models**: `Promotion` (`promotions`),
  `LoyaltyProgram` (`loyalty_programs`), `LoyaltyPoint` (`loyalty_points`),
  `PatientEnrolledProgram` (`patient_enrolled_programs`).
- **Scope**: read-mostly CRUD v1 — manage campaigns and inspect points; automatic accrual rules
  are out of scope.

## Design
- **Promotions list**: table — name, discount %, valid from/to, active. Form: name, description,
  discount type/value, validity range, applicable products (multi), active.
- **Loyalty programs list**: table — name, points rule summary, enrolled patients count. Form:
  name, earn rule (points per amount, editable text+numeric), redeem rule, active.
- **Points ledger**: read-only table (patient, program, delta, reason, date) + manual adjustment
  (org-admin only, with reason).
