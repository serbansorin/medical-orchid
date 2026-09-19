<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

/**
 * Class PharmacyPurchaseOrder
 *
 * Comandă de achiziție pentru farmacie
 *
 * @property int $id
 * @property int $pharmacy_id
 * @property int $supplier_id
 * @property string|null $order_number
 * @property PurchaseOrderStatus $status
 * @property \Carbon\Carbon $order_date
 * @property \Carbon\Carbon|null $expected_delivery_date
 * @property \Carbon\Carbon|null $actual_delivery_date
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $total_amount
 * @property int|null $created_by
 * @property int|null $received_by
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Pharmacy $pharmacy
 * @property PharmacySupplier $supplier
 * @property User|null $createdByUser
 * @property User|null $receivedByUser
 * @property \Illuminate\Database\Eloquent\Collection|PharmacyPurchaseOrderItem[] $items
 */
class PharmacyPurchaseOrder extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'pharmacy_purchase_orders';

    protected $fillable = [
        'pharmacy_id',
        'supplier_id',
        'order_number',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'created_by',
        'received_by',
        'notes',
    ];

    protected $casts = [
        'pharmacy_id' => 'int',
        'supplier_id' => 'int',
        'status' => PurchaseOrderStatus::class,
        'order_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_by' => 'int',
        'received_by' => 'int',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(PharmacySupplier::class, 'supplier_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyPurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Generează un număr unic pentru comanda de achiziție
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('PO-%s-%05d', $date, $sequence);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }

            if (empty($order->order_date)) {
                $order->order_date = now();
            }

            if (empty($order->status)) {
                $order->status = PurchaseOrderStatus::Draft;
            }
        });
    }
}
