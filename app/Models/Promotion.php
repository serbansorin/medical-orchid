<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

/**
 * Class Promotion
 *
 * Promoție pentru farmacie
 *
 * @property int $id
 * @property int $pharmacy_id
 * @property string $name
 * @property string|null $description
 * @property DiscountType $discount_type
 * @property float $discount_value
 * @property float|null $min_purchase_amount
 * @property float|null $max_discount_amount
 * @property \Carbon\Carbon $starts_at
 * @property \Carbon\Carbon $ends_at
 * @property bool $is_active
 * @property string|null $promo_code
 * @property int|null $usage_limit
 * @property int $times_used
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Pharmacy $pharmacy
 */
class Promotion extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'promotions';

    protected $fillable = [
        'pharmacy_id',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_purchase_amount',
        'max_discount_amount',
        'starts_at',
        'ends_at',
        'is_active',
        'promo_code',
        'usage_limit',
        'times_used',
    ];

    protected $casts = [
        'pharmacy_id' => 'int',
        'discount_type' => DiscountType::class,
        'discount_value' => 'float',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'bool',
        'usage_limit' => 'int',
        'times_used' => 'int',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    /**
     * Scope pentru promoții valide (active și în perioada corectă)
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereRaw('times_used < usage_limit');
            });
    }

    /**
     * Aplică discountul asupra unei sume
     */
    public function applyDiscount(float $amount): float
    {
        if (! $this->isValidForAmount($amount)) {
            return 0;
        }

        $discount = match ($this->discount_type) {
            DiscountType::Percentage => $amount * ($this->discount_value / 100),
            DiscountType::FixedAmount => $this->discount_value,
            DiscountType::BuyXGetY => 0, // Se calculează diferit pe baza produselor
        };

        // Aplicăm limita maximă dacă există
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return min($discount, $amount);
    }

    /**
     * Verifică dacă promoția este validă pentru o anumită sumă
     */
    public function isValidForAmount(float $amount): bool
    {
        if ($this->min_purchase_amount && $amount < $this->min_purchase_amount) {
            return false;
        }

        return $this->isCurrentlyActive();
    }

    /**
     * Verifică dacă promoția este activă în momentul curent
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && $this->ends_at >= now()
            && ($this->usage_limit === null || $this->times_used < $this->usage_limit);
    }

    /**
     * Incrementează contorul de utilizări
     */
    public function recordUsage(): void
    {
        $this->increment('times_used');
    }
}
