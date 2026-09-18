<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PharmacyDelivery
 *
 * Livrare de la farmacie
 *
 * @property int $id
 * @property int $sale_id
 * @property int|null $zone_id
 * @property DeliveryStatus $status
 * @property string $delivery_address
 * @property float|null $delivery_latitude
 * @property float|null $delivery_longitude
 * @property string|null $recipient_name
 * @property string|null $recipient_phone
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $picked_up_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property int|null $driver_id
 * @property string|null $driver_notes
 * @property string|null $customer_notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property PharmacySale $sale
 * @property DeliveryZone|null $zone
 * @property User|null $driver
 */
class PharmacyDelivery extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_deliveries';

    protected $fillable = [
        'sale_id',
        'zone_id',
        'status',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'recipient_name',
        'recipient_phone',
        'scheduled_at',
        'picked_up_at',
        'delivered_at',
        'driver_id',
        'driver_notes',
        'customer_notes',
    ];

    protected $casts = [
        'sale_id' => 'int',
        'zone_id' => 'int',
        'status' => DeliveryStatus::class,
        'delivery_latitude' => 'float',
        'delivery_longitude' => 'float',
        'scheduled_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'driver_id' => 'int',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Verifică dacă livrarea a fost finalizată
     */
    public function isDelivered(): bool
    {
        return $this->status === DeliveryStatus::Delivered;
    }

    /**
     * Marchează livrarea ca finalizată
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => DeliveryStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Marchează livrarea ca preluată
     */
    public function markAsPickedUp(): void
    {
        $this->update([
            'status' => DeliveryStatus::PickedUp,
            'picked_up_at' => now(),
        ]);
    }

    /**
     * Marchează livrarea ca eșuată
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => DeliveryStatus::Failed,
            'driver_notes' => $reason,
        ]);
    }

    /**
     * Atribuie un șofer livrării
     */
    public function assignDriver(User $driver): void
    {
        $this->update([
            'driver_id' => $driver->id,
            'status' => DeliveryStatus::Assigned,
        ]);
    }
}
