<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

/**
 * Class PharmacySaleItem
 *
 * Element al vânzării din farmacie
 *
 * @property int $id
 * @property int $sale_id
 * @property int $inventory_id
 * @property int|null $prescription_id
 * @property int $quantity
 * @property float $unit_price
 * @property float $discount_percent
 * @property float $total_price
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property PharmacySale $sale
 * @property PharmacyInventory $inventory
 * @property Prescription|null $prescription
 */
class PharmacySaleItem extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'pharmacy_sale_items';

    protected $fillable = [
        'sale_id',
        'inventory_id',
        'prescription_id',
        'quantity',
        'unit_price',
        'discount_percent',
        'total_price',
    ];

    protected $casts = [
        'sale_id' => 'int',
        'inventory_id' => 'int',
        'prescription_id' => 'int',
        'quantity' => 'int',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'float',
        'total_price' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(PharmacyInventory::class, 'inventory_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Calculează prețul total al elementului
     */
    public function calculateTotalPrice(): float
    {
        $baseTotal = $this->quantity * $this->unit_price;
        $discount = $baseTotal * ($this->discount_percent / 100);

        return $baseTotal - $discount;
    }
}
