<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class PharmacyPurchaseOrderItem
 *
 * Element al comenzii de achiziție
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int|null $medication_id
 * @property string $medication_name
 * @property int $quantity_ordered
 * @property int|null $quantity_received
 * @property float $unit_price
 * @property float $total_price
 * @property string|null $batch_number
 * @property \Carbon\Carbon|null $expiry_date
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property PharmacyPurchaseOrder $purchaseOrder
 * @property MedicineDocument|null $medication
 */
class PharmacyPurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'medication_id',
        'medication_name',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'total_price',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'purchase_order_id' => 'int',
        'medication_id' => 'int',
        'quantity_ordered' => 'int',
        'quantity_received' => 'int',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PharmacyPurchaseOrder::class, 'purchase_order_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(MedicineDocument::class, 'medication_id');
    }
}
