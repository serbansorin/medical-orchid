<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Orchid\Screen\AsSource;

/**
 * Class PharmacySale
 *
 * Vânzare în farmacie
 *
 * @property int $id
 * @property int $pharmacy_id
 * @property int|null $patient_id
 * @property int $pharmacist_id
 * @property string|null $sale_number
 * @property \Carbon\Carbon $sale_date
 * @property PaymentStatus $payment_status
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total_amount
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Pharmacy $pharmacy
 * @property Patient|null $patient
 * @property User $pharmacist
 * @property \Illuminate\Database\Eloquent\Collection|PharmacySaleItem[] $items
 * @property \Illuminate\Database\Eloquent\Collection|PharmacyPayment[] $payments
 * @property PharmacyReceipt|null $receipt
 */
class PharmacySale extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'pharmacy_sales';

    protected $fillable = [
        'pharmacy_id',
        'patient_id',
        'pharmacist_id',
        'sale_number',
        'sale_date',
        'payment_status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'pharmacy_id' => 'int',
        'patient_id' => 'int',
        'pharmacist_id' => 'int',
        'sale_date' => 'datetime',
        'payment_status' => PaymentStatus::class,
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacySaleItem::class, 'sale_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PharmacyPayment::class, 'sale_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PharmacyReceipt::class, 'sale_id');
    }

    /**
     * Calculează totalul vânzării pe baza articolelor
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $taxAmount = $subtotal * 0.19; // TVA 19%

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount - $this->discount_amount,
        ]);
    }

    /**
     * Verifică dacă vânzarea este plătită integral
     */
    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * Calculează suma plătită
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Calculează suma rămasă de plătit
     */
    public function getRemainingBalanceAttribute(): float
    {
        return $this->total_amount - $this->total_paid;
    }

    /**
     * Generează un număr unic pentru vânzare
     */
    public static function generateSaleNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('SALE-%s-%05d', $date, $sequence);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = self::generateSaleNumber();
            }

            if (empty($sale->sale_date)) {
                $sale->sale_date = now();
            }

            if (empty($sale->payment_status)) {
                $sale->payment_status = PaymentStatus::Pending;
            }
        });
    }
}
