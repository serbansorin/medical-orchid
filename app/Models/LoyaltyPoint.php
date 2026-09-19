<?php

namespace App\Models;

use App\Enums\LoyaltyTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

/**
 * Class LoyaltyPoint
 *
 * Puncte de loialitate ale unui client
 *
 * @property int $id
 * @property int $program_id
 * @property int $patient_id
 * @property int $total_points
 * @property int $available_points
 * @property int $lifetime_points
 * @property LoyaltyTier $current_tier
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property LoyaltyProgram $program
 * @property Patient $patient
 */
class LoyaltyPoint extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'loyalty_points';

    protected $fillable = [
        'program_id',
        'patient_id',
        'total_points',
        'available_points',
        'lifetime_points',
        'current_tier',
    ];

    protected $casts = [
        'program_id' => 'int',
        'patient_id' => 'int',
        'total_points' => 'int',
        'available_points' => 'int',
        'lifetime_points' => 'int',
        'current_tier' => LoyaltyTier::class,
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Adaugă puncte la contul clientului
     */
    public function addPoints(int $amount): void
    {
        $this->increment('total_points', $amount);
        $this->increment('available_points', $amount);
        $this->increment('lifetime_points', $amount);

        $this->updateTier();
    }

    /**
     * Răscumpără puncte din contul clientului
     */
    public function redeemPoints(int $amount): bool
    {
        if ($this->available_points < $amount) {
            return false;
        }

        $this->decrement('available_points', $amount);

        return true;
    }

    /**
     * Actualizează nivelul de loialitate pe baza punctelor acumulate
     */
    protected function updateTier(): void
    {
        $tier = match (true) {
            $this->lifetime_points >= 10000 => LoyaltyTier::Gold,
            $this->lifetime_points >= 5000 => LoyaltyTier::Silver,
            default => LoyaltyTier::Bronze,
        };

        if ($this->current_tier !== $tier) {
            $this->update(['current_tier' => $tier]);
        }
    }

    /**
     * Obține multiplicatorul de puncte pentru nivelul curent
     */
    public function getTierMultiplier(): float
    {
        return $this->current_tier?->multiplier() ?? 1.0;
    }
}
