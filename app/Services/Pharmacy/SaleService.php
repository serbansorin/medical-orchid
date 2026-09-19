<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\PharmacyInventory;
use App\Models\PharmacySale;
use Illuminate\Support\Facades\DB;

/**
 * Standalone (walk-in) sale CRUD + payment finalization.
 *
 * Mirrors ../legacy-medical/app/Http/Controllers/Api/Pharmacist/POSController.php:
 * - store(): sale created with payment_status pending, totals = subtotal + 19% TVA.
 *   Unit price = inventory price_per_unit (legacy read sale_price ?? unit_price;
 *   only price_per_unit exists in the real table).
 * - processSale(): payment rows → stock decrement → status paid/partial →
 *   receipt row. cancelSale(): paid sales cannot be cancelled → refunded.
 *
 * Real columns (verbatim models use sale_id/patient_id/transaction_reference;
 * writes use forceFill + DB inserts with pharmacy_sale_id/customer_id/
 * reference_number).
 */
class SaleService
{
    /**
     * @param  array{pharmacy_id: int, customer_id?: int|null, discount_amount?: float, notes?: string|null}  $data
     * @param  array<int, array{inventory_id?: int|null, medication_name: string, quantity: int, unit_price: float, discount_percent?: float, prescription_id?: int|null}>  $lines
     */
    public function saveSale(array $data, array $lines, ?PharmacySale $sale = null): PharmacySale
    {
        return DB::transaction(function () use ($data, $lines, $sale) {
            if ($sale === null) {
                $sale = new PharmacySale;
                $sale->forceFill([
                    'pharmacy_id' => $data['pharmacy_id'],
                    'customer_id' => $data['customer_id'] ?? null,
                    'pharmacist_id' => auth()->id(),
                    'sale_date' => now(),
                    'payment_status' => PaymentStatus::Pending->value,
                ])->save();
            } else {
                if ($sale->payment_status === PaymentStatus::Paid) {
                    throw new \DomainException('Paid sales cannot be edited.');
                }
                $sale->forceFill([
                    'customer_id' => $data['customer_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                ])->save();
            }

            DB::table('pharmacy_sale_items')->where('pharmacy_sale_id', $sale->id)->delete();

            $subtotal = 0.0;
            foreach ($lines as $line) {
                $qty = max((int) $line['quantity'], 1);
                $discount = (float) ($line['discount_percent'] ?? 0);
                $total = ((float) $line['unit_price']) * $qty * (1 - $discount / 100);
                $subtotal += $total;

                DB::table('pharmacy_sale_items')->insert([
                    'pharmacy_sale_id' => $sale->id,
                    'inventory_id' => $line['inventory_id'] ?? null,
                    'medication_name' => $line['medication_name'],
                    'quantity' => $qty,
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $discount,
                    'total_price' => $total,
                    'prescription_id' => $line['prescription_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $discountAmount = (float) ($data['discount_amount'] ?? $sale->getAttribute('discount_amount') ?? 0);
            $tax = $subtotal * 0.19;
            $sale->forceFill([
                'notes' => $data['notes'] ?? $sale->getAttribute('notes'),
                'discount_amount' => $discountAmount,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax - $discountAmount,
            ])->save();

            return $sale->fresh();
        });
    }

    /**
     * Record a payment; when total paid covers the total, finalize:
     * decrement stock, mark paid, generate receipt (legacy processSale()).
     *
     * @return array{sale: PharmacySale, paid: float}
     */
    public function recordPayment(PharmacySale $sale, string $method, float $amount, ?string $reference = null): array
    {
        return DB::transaction(function () use ($sale, $method, $reference, $amount) {
            $sale = $sale->fresh();
            $validMethods = array_column(PaymentMethod::cases(), 'value');
            if (! in_array($method, $validMethods, true)) {
                throw new \DomainException('Unknown payment method.');
            }

            DB::table('pharmacy_payments')->insert([
                'pharmacy_sale_id' => $sale->id,
                'payment_method' => $method,
                'amount' => $amount,
                'reference_number' => $reference,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paid = (float) DB::table('pharmacy_payments')->where('pharmacy_sale_id', $sale->id)->sum('amount');
            $isPaid = $paid >= ((float) $sale->getAttribute('total_amount'));

            if ($isPaid) {
                foreach (DB::table('pharmacy_sale_items')->where('pharmacy_sale_id', $sale->id)->get() as $item) {
                    if ($item->inventory_id !== null) {
                        $inv = PharmacyInventory::find($item->inventory_id);
                        $inv?->decrement('stock_quantity', $item->quantity);
                    }
                }

                if (DB::table('pharmacy_receipts')->where('pharmacy_sale_id', $sale->id)->count() === 0) {
                    DB::table('pharmacy_receipts')->insert([
                        'pharmacy_sale_id' => $sale->id,
                        'receipt_number' => $this->receiptNumber(),
                        'receipt_data' => json_encode(['sale_id' => $sale->id, 'source' => 'pos_finalize']),
                        'generated_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $sale->forceFill([
                'payment_status' => $isPaid ? PaymentStatus::Paid->value : PaymentStatus::Partial->value,
            ])->save();

            return ['sale' => $sale->fresh(), 'paid' => $paid];
        });
    }

    public function cancelSale(PharmacySale $sale): PharmacySale
    {
        $sale = $sale->fresh();
        if ($sale->payment_status === PaymentStatus::Paid) {
            throw new \DomainException('Paid sales cannot be cancelled.');
        }
        $sale->forceFill(['payment_status' => PaymentStatus::Refunded->value])->save();

        return $sale->fresh();
    }

    private function receiptNumber(): string
    {
        $date = now()->format('Ymd');
        $seq = DB::table('pharmacy_receipts')->whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('RCP-%s-%05d', $date, $seq);
    }
}
