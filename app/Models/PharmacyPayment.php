<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

/**
 * Class PharmacyPayment
 *
 * Plată pentru vânzare în farmacie
 *
 * @property int $id
 * @property int $sale_id
 * @property PaymentMethod $payment_method
 * @property float $amount
 * @property string|null $transaction_reference
 * @property \Carbon\Carbon|null $processed_at
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property PharmacySale $sale
 */
class PharmacyPayment extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'pharmacy_payments';

    protected $fillable = [
        'sale_id',
        'payment_method',
        'amount',
        'transaction_reference',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'sale_id' => 'int',
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id');
    }
}
