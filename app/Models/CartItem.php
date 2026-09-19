<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class CartItem extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'itemable_type',
        'itemable_id',
        'quantity',
        'price_snapshot',
        'locked',
        'locked_reason',
    ];

    protected $casts = [
        'cart_id' => 'int',
        'itemable_id' => 'int',
        'quantity' => 'int',
        'price_snapshot' => 'float',
        'locked' => 'boolean',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function itemable()
    {
        return $this->morphTo();
    }

    /**
     * Scope pentru itemi deblocate
     */
    public function scopeUnlocked($query)
    {
        return $query->where('locked', false);
    }

    /**
     * Calculează subtotalul itemului
     */
    public function getSubtotalAttribute(): float
    {
        return round($this->price_snapshot * $this->quantity, 2);
    }

    protected $table = 'cart_items';

    protected $columns = [
        'id',
        'cart_id',
        'itemable_type',
        'itemable_id',
        'quantity',
        'price_snapshot',
        'locked',
        'locked_reason',
        'created_at',
        'updated_at',
    ];
}
