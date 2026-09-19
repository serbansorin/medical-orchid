<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\BillingInvoice;
use App\Models\BillingInvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Step 6 (Facturare) — invoice lifecycle, mirroring legacy semantics.
 *
 * Status literals: draft / sent / paid / overdue / cancelled.
 * Source: legacy app/Filament/Doctor/Resources/BillingInvoiceResource.php:71
 * (form select options) and :353 (an invoice is "open" when its status is NOT
 * in [paid, cancelled]).
 *
 * Calculation mirror: legacy
 * app/Services/Billing/AppointmentBillingService.php::generateInvoice()
 * creates draft invoices (invoice_number INV-xxxxxxxx, invoice_date today,
 * due_date +14 days, source_kind manual, direction cash_in) and recomputes
 * subtotal as the sum of line totals with total_amount = subtotal.
 *
 * Finalize mirror: legacy
 * app/Filament/Doctor/Resources/BillingInvoiceResource/Pages/ListBillingInvoices.php
 * stamps locked_at when an invoice leaves the editable draft state; our
 * finalize() moves draft → sent and stamps locked_at, locking the lines.
 *
 * Legacy mark-as-paid (BillingInvoiceResource.php:348-356,
 * Pages/ViewBillingInvoice.php:34-46) flipped status straight to paid.
 * Our recordPayment() instead writes a real payments row and derives the
 * status from the paid total (paid when balance reaches zero).
 */
class InvoiceService
{
    /**
     * Invoice statuses, in canonical order.
     *
     * @return list<string>
     */
    public static function statuses(): array
    {
        return ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
    }

    /**
     * Whether lines/payments may still mutate (draft, or sent but unlocked).
     */
    public static function isEditable(BillingInvoice $invoice): bool
    {
        return $invoice->locked_at === null
            && in_array($invoice->status, ['draft', 'sent'], true);
    }

    /**
     * Create a draft invoice with its items, atomically.
     *
     * @param  array{patient_id:int, clinic_id?:int|null, doctor_id?:int|null, appointment_id?:int|null, medical_visit_id?:int|null, invoice_date?:string|null, due_date?:string|null, notes?:string|null, direction?:string|null, items?:list<array{product_id?:int|null, description:string, quantity:int, unit_price:float}>}  $data
     */
    public function createDraft(array $data, ?User $creator = null): BillingInvoice
    {
        return DB::transaction(function () use ($data, $creator): BillingInvoice {
            $invoice = new BillingInvoice;
            $invoice->invoice_number = 'INV-'.strtoupper(Str::random(12));
            $invoice->patient_id = (int) $data['patient_id'];
            $invoice->appointment_id = isset($data['appointment_id']) ? (int) $data['appointment_id'] : null;
            $invoice->medical_visit_id = isset($data['medical_visit_id']) ? (int) $data['medical_visit_id'] : null;
            $invoice->doctor_id = isset($data['doctor_id']) ? (int) $data['doctor_id'] : null;
            $invoice->clinic_id = isset($data['clinic_id']) ? (int) $data['clinic_id'] : null;
            $invoice->invoice_date = $data['invoice_date'] ?? now()->toDateString();
            $invoice->due_date = $data['due_date'] ?? now()->addDays(14)->toDateString();
            $invoice->direction = $data['direction'] ?? 'cash_in';
            $invoice->source_kind = 'manual';
            $invoice->notes = $data['notes'] ?? null;
            $invoice->status = 'draft';
            $invoice->subtotal = 0;
            $invoice->tax_amount = 0;
            $invoice->discount_amount = 0;
            $invoice->total_amount = 0;
            $invoice->created_by = $creator?->id;
            $invoice->save();

            foreach ($data['items'] ?? [] as $row) {
                $this->buildItem($invoice, $row);
            }

            return $this->recalculateTotals($invoice);
        });
    }

    /**
     * Append one billing_invoice_items row, then recalculate. Atomic.
     *
     * @param  array{product_id?:int|null, description:string, quantity:int, unit_price:float}  $data
     */
    public function addItem(BillingInvoice $invoice, array $data): BillingInvoiceItem
    {
        $this->assertEditable($invoice);

        return DB::transaction(function () use ($invoice, $data): BillingInvoiceItem {
            $item = $this->buildItem($invoice->fresh(), $data);
            $this->recalculateTotals($invoice->fresh());

            return $item;
        });
    }

    /**
     * Remove one line, then recalculate. Atomic.
     */
    public function removeItem(BillingInvoice $invoice, BillingInvoiceItem $item): void
    {
        $this->assertEditable($invoice);

        if ((int) $item->invoice_id !== (int) $invoice->id) {
            throw new InvalidArgumentException('Item does not belong to this invoice.');
        }

        DB::transaction(function () use ($invoice, $item): void {
            $item->delete();
            $this->recalculateTotals($invoice->fresh());
        });
    }

    /**
     * Recompute subtotal/total from the current lines.
     * Mirrors AppointmentBillingService::generateInvoice(): subtotal is the
     * sum of line totals; total = subtotal − discount + tax.
     */
    public function recalculateTotals(BillingInvoice $invoice): BillingInvoice
    {
        return DB::transaction(function () use ($invoice): BillingInvoice {
            $fresh = $invoice->fresh() ?? $invoice;
            $subtotal = (float) $fresh->billing_invoice_items()->sum('line_total');
            $fresh->subtotal = round($subtotal, 2);
            $fresh->total_amount = round(
                $subtotal - (float) $fresh->discount_amount + (float) $fresh->tax_amount,
                2
            );
            $fresh->save();

            return $fresh->fresh();
        });
    }

    /**
     * Record a real payments row and derive the invoice status:
     * balance zero → paid; otherwise a draft moves to sent.
     *
     * @param  array{amount:float, method:string, paid_at?:string|null, reference?:string|null, notes?:string|null}  $data
     */
    public function recordPayment(BillingInvoice $invoice, array $data, ?User $receiver = null): Payment
    {
        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            throw new InvalidArgumentException('Cannot record a payment on a '.$invoice->status.' invoice.');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $receiver): Payment {
            $fresh = $invoice->fresh() ?? $invoice;
            $balance = $this->balance($fresh);

            if ($amount - $balance > 0.009) {
                throw new InvalidArgumentException(
                    'Payment exceeds the outstanding balance of '.number_format($balance, 2).'.'
                );
            }

            $payment = new Payment;
            $payment->invoice_id = $fresh->id;
            $payment->amount = $amount;
            $payment->method = (string) ($data['method'] ?? 'cash');
            $payment->paid_at = $data['paid_at'] ?? now();
            $payment->reference = $data['reference'] ?? null;
            $payment->notes = $data['notes'] ?? null;
            $payment->received_by = $receiver?->id;
            $payment->save();

            $after = $fresh->fresh();
            $remaining = $this->balance($after);

            if ($remaining <= 0.009) {
                $after->status = 'paid';
            } elseif ($after->status === 'draft') {
                $after->status = 'sent';
            }

            $after->save();

            return $payment;
        });
    }

    /**
     * Issue a draft invoice: draft → sent + locked_at stamp (lines locked).
     * Mirrors the legacy locked_at stamping in ListBillingInvoices.
     */
    public function finalize(BillingInvoice $invoice): BillingInvoice
    {
        return DB::transaction(function () use ($invoice): BillingInvoice {
            $fresh = $invoice->fresh() ?? $invoice;

            if ($fresh->status !== 'draft') {
                throw new InvalidArgumentException('Only draft invoices can be finalized.');
            }

            if ($fresh->billing_invoice_items()->count() === 0) {
                throw new InvalidArgumentException('Cannot finalize an invoice without lines.');
            }

            $fresh = $this->recalculateTotals($fresh);
            $fresh->status = 'sent';
            $fresh->locked_at = now();
            $fresh->save();

            return $fresh->fresh();
        });
    }

    /**
     * Flag an open, past-due invoice as overdue.
     */
    public function markOverdue(BillingInvoice $invoice): BillingInvoice
    {
        return DB::transaction(function () use ($invoice): BillingInvoice {
            $fresh = $invoice->fresh() ?? $invoice;

            if (! in_array($fresh->status, ['draft', 'sent'], true)) {
                throw new InvalidArgumentException('Only open invoices can be marked overdue.');
            }

            $fresh->status = 'overdue';
            $fresh->save();

            return $fresh->fresh();
        });
    }

    /**
     * Void an invoice. Mirrors legacy ViewBillingInvoice cancel action
     * (status → cancelled); never deletes (financial audit).
     */
    public function void(BillingInvoice $invoice, string $reason): BillingInvoice
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A void reason is required.');
        }

        return DB::transaction(function () use ($invoice, $reason): BillingInvoice {
            $fresh = $invoice->fresh() ?? $invoice;

            if ($fresh->status === 'cancelled') {
                throw new InvalidArgumentException('Invoice is already cancelled.');
            }

            $fresh->status = 'cancelled';
            $fresh->notes = trim(($fresh->notes ? $fresh->notes."\n" : '').'Void: '.trim($reason));
            $fresh->save();

            return $fresh->fresh();
        });
    }

    /**
     * Sum of recorded payments.
     */
    public function paidTotal(BillingInvoice $invoice): float
    {
        return round((float) $invoice->payments()->sum('amount'), 2);
    }

    /**
     * Outstanding balance (never negative for display).
     */
    public function balance(BillingInvoice $invoice): float
    {
        return round(max(0.0, (float) $invoice->total_amount - $this->paidTotal($invoice)), 2);
    }

    /**
     * @param  array{product_id?:int|null, description:string, quantity:int, unit_price:float}  $data
     */
    private function buildItem(BillingInvoice $invoice, array $data): BillingInvoiceItem
    {
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $unitPrice = round((float) ($data['unit_price'] ?? 0), 2);

        $item = new BillingInvoiceItem;
        $item->invoice_id = $invoice->id;
        $item->product_id = isset($data['product_id']) ? (int) $data['product_id'] : null;
        $item->description = (string) ($data['description'] ?? '');
        $item->quantity = $quantity;
        $item->unit_price = $unitPrice;
        $item->line_total = round($quantity * $unitPrice, 2);
        $item->save();

        return $item;
    }

    private function assertEditable(BillingInvoice $invoice): void
    {
        if (! self::isEditable($invoice->fresh() ?? $invoice)) {
            throw new InvalidArgumentException('Invoice lines are locked for this status.');
        }
    }
}
