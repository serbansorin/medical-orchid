<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Doctor-level product override (price, duration, availability).
 *
 * @property int $id
 * @property int $doctor_id
 * @property int $product_id
 * @property int|null $clinic_id
 * @property float|null $price
 * @property int|null $duration_minutes
 * @property bool $available
 * @property bool $requires_payment
 */
class DoctorProduct extends Model
{
    protected $table = 'doctor_product';

    protected $fillable = [
        'doctor_id',
        'product_id',
        'clinic_id',
        'price',
        'duration_minutes',
        'available',
        'requires_payment',
    ];

    protected $casts = [
        'doctor_id' => 'int',
        'product_id' => 'int',
        'clinic_id' => 'int',
        'price' => 'float',
        'duration_minutes' => 'int',
        'available' => 'bool',
        'requires_payment' => 'bool',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
