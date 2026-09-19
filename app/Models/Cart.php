<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class Cart extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\CartFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'expires_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function payments()
    {
        return $this->hasMany(CartPayment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pentru coșuri active
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pentru coșuri expirate
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Calculează totalul coșului
     */
    public function calculateTotal(): array
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->price_snapshot * $item->quantity;
        });

        $tax = $subtotal * 0.19; // 19% TVA
        $total = $subtotal + $tax;

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    protected $table = 'carts';

    protected $columns = [
        'id',
        'user_id',
        'status',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    public function cart_items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function cart_payments()
    {
        return $this->hasMany(CartPayment::class);
    }
}
