<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class PharmacyInventory extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'pharmacy_inventory';

    protected $casts = [
        'pharmacy_id' => 'int',
        'medication_id' => 'int',
        'stock_quantity' => 'int',
        'reorder_level' => 'int',
        'price_per_unit' => 'float',
        'expiry_date' => 'datetime',
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
