<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

/**
 * Class LoyaltyProgram
 *
 * Program de loialitate pentru farmacie
 *
 * @property int $id
 * @property int $pharmacy_id
 * @property string $name
 * @property string|null $description
 * @property float $points_per_currency
 * @property float $points_value
 * @property int|null $min_points_redeem
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Pharmacy $pharmacy
 * @property \Illuminate\Database\Eloquent\Collection|LoyaltyPoint[] $loyaltyPoints
 */
class LoyaltyProgram extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'loyalty_programs';

    protected $fillable = [
        'pharmacy_id',
        'name',
        'description',
        'points_per_currency',
        'points_value',
        'min_points_redeem',
        'is_active',
    ];

    protected $casts = [
        'pharmacy_id' => 'int',
        'points_per_currency' => 'float',
        'points_value' => 'float',
        'min_points_redeem' => 'int',
        'is_active' => 'bool',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class, 'program_id');
    }

    /**
     * Calculează câte puncte primește clientul pentru o sumă
     */
    public function calculatePointsForAmount(float $amount): int
    {
        return (int) floor($amount * $this->points_per_currency);
    }

    /**
     * Calculează valoarea punctelor în monedă
     */
    public function calculateValueForPoints(int $points): float
    {
        return $points * $this->points_value;
    }
}
