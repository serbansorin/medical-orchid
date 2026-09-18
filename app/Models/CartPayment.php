<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartPayment extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<\Database\Factories\CartPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cart_id',
        'payment_method_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_transaction_id',
        'metadata_json',
        'paid_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'cart_id' => 'int',
        'payment_method_id' => 'int',
        'amount' => 'float',
        'metadata_json' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Scope pentru plăți finalizate
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope pentru plăți în curs de procesare
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Accessor pentru culoare badge status
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'processing' => 'info',
            'pending' => 'warning',
            'failed' => 'danger',
            'refunded' => 'gray',
            default => 'secondary',
        };
    }

    /**
     * Accessor pentru sumă formatată
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2).' '.$this->currency;
    }

    protected $table = 'cart_payments';

    protected $columns = [
        'id',
        'user_id',
        'cart_id',
        'payment_method_id',
        'amount',
        'currency',
        'status',
        'gateway',
        'gateway_transaction_id',
        'metadata_json',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
