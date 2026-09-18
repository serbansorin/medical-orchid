<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PharmacyReceipt
 *
 * Bon fiscal pentru vânzare
 *
 * @property int $id
 * @property int $sale_id
 * @property string|null $receipt_number
 * @property array|null $receipt_data
 * @property \Carbon\Carbon|null $generated_at
 * @property \Carbon\Carbon|null $printed_at
 * @property string|null $pdf_path
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property PharmacySale $sale
 */
class PharmacyReceipt extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_receipts';

    protected $fillable = [
        'sale_id',
        'receipt_number',
        'receipt_data',
        'generated_at',
        'printed_at',
        'pdf_path',
    ];

    protected $casts = [
        'sale_id' => 'int',
        'receipt_data' => 'array',
        'generated_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id');
    }

    /**
     * Generează un număr unic pentru bon
     */
    public static function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('RCP-%s-%05d', $date, $sequence);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (empty($receipt->receipt_number)) {
                $receipt->receipt_number = self::generateReceiptNumber();
            }

            if (empty($receipt->generated_at)) {
                $receipt->generated_at = now();
            }
        });
    }
}
