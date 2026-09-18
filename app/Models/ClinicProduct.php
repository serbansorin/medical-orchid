<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Clinic-level product override (price, duration, availability).
 *
 * @property int $id
 * @property int $clinic_id
 * @property int $product_id
 * @property float|null $price
 * @property int|null $duration_minutes
 * @property bool $available
 * @property bool $requires_payment
 */
class ClinicProduct extends Model
{
    protected $table = 'clinic_product';

    protected $fillable = [
        'clinic_id',
        'product_id',
        'price',
        'duration_minutes',
        'available',
        'requires_payment',
    ];

    protected $casts = [
        'clinic_id' => 'int',
        'product_id' => 'int',
        'price' => 'float',
        'duration_minutes' => 'int',
        'available' => 'bool',
        'requires_payment' => 'bool',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
