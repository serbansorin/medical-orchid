<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class DeliveryZone
 *
 * Zonă de livrare pentru farmacie
 *
 * @property int $id
 * @property int $pharmacy_id
 * @property string $name
 * @property array|null $polygon_coordinates
 * @property float $delivery_fee
 * @property float|null $min_order_amount
 * @property int|null $estimated_delivery_minutes
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Pharmacy $pharmacy
 * @property \Illuminate\Database\Eloquent\Collection|PharmacyDelivery[] $deliveries
 */
class DeliveryZone extends Model
{
    use HasFactory;

    protected $table = 'delivery_zones';

    protected $fillable = [
        'pharmacy_id',
        'name',
        'polygon_coordinates',
        'delivery_fee',
        'min_order_amount',
        'estimated_delivery_minutes',
        'is_active',
    ];

    protected $casts = [
        'pharmacy_id' => 'int',
        'polygon_coordinates' => 'array',
        'delivery_fee' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'estimated_delivery_minutes' => 'int',
        'is_active' => 'bool',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PharmacyDelivery::class, 'zone_id');
    }

    /**
     * Verifică dacă un punct geografic se află în zonă
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if (empty($this->polygon_coordinates)) {
            return false;
        }

        $polygon = $this->polygon_coordinates;
        $n = count($polygon);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'] ?? $polygon[$i][0];
            $yi = $polygon[$i]['lng'] ?? $polygon[$i][1];
            $xj = $polygon[$j]['lat'] ?? $polygon[$j][0];
            $yj = $polygon[$j]['lng'] ?? $polygon[$j][1];

            if (
                (($yi > $longitude) !== ($yj > $longitude))
                && ($latitude < ($xj - $xi) * ($longitude - $yi) / ($yj - $yi) + $xi)
            ) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
