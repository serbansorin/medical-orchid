<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class PaymentMethod extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\PaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'last_four',
        'is_default',
        'expires_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'is_default' => 'boolean',
        'expires_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(CartPayment::class);
    }

    /**
     * Scope pentru metode de plată implicite
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Verifică dacă metoda de plată este expirată
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Accessor pentru display name
     */
    public function getDisplayNameAttribute(): string
    {
        return match ($this->type) {
            'card' => ($this->provider ?? 'Card').' ***'.$this->last_four,
            'bank' => 'Transfer bancar',
            'wallet' => ($this->provider ?? 'Wallet').' ***'.$this->last_four,
            default => 'Metodă de plată',
        };
    }

    protected $table = 'payment_methods';

    protected $columns = [
        'id',
        'user_id',
        'type',
        'provider',
        'last_four',
        'is_default',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    public function cart_payments()
    {
        return $this->hasMany(CartPayment::class);
    }
}
