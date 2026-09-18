<?php

namespace App\Models;

use App\Enums\ContactMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class PharmacyCustomerProfile
 *
 * Profil de client pentru farmacie
 *
 * @property int $id
 * @property int $patient_id
 * @property int $pharmacy_id
 * @property ContactMethod|null $preferred_contact_method
 * @property bool $marketing_consent
 * @property bool $sms_notifications
 * @property bool $email_notifications
 * @property string|null $notes
 * @property \Carbon\Carbon|null $birthday_reward_claimed_at
 * @property \Carbon\Carbon|null $last_visit_at
 * @property float $total_spent
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Patient $patient
 * @property Pharmacy $pharmacy
 * @property LoyaltyPoint|null $loyaltyPoint
 */
class PharmacyCustomerProfile extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_customer_profiles';

    protected $fillable = [
        'patient_id',
        'pharmacy_id',
        'preferred_contact_method',
        'marketing_consent',
        'sms_notifications',
        'email_notifications',
        'notes',
        'birthday_reward_claimed_at',
        'last_visit_at',
        'total_spent',
    ];

    protected $casts = [
        'patient_id' => 'int',
        'pharmacy_id' => 'int',
        'preferred_contact_method' => ContactMethod::class,
        'marketing_consent' => 'bool',
        'sms_notifications' => 'bool',
        'email_notifications' => 'bool',
        'birthday_reward_claimed_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'total_spent' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function loyaltyPoint(): HasOne
    {
        return $this->hasOne(LoyaltyPoint::class, 'patient_id', 'patient_id');
    }

    /**
     * Actualizează data ultimei vizite
     */
    public function recordVisit(float $amount = 0): void
    {
        $this->update([
            'last_visit_at' => now(),
            'total_spent' => $this->total_spent + $amount,
        ]);
    }

    /**
     * Verifică dacă clientul poate primi recompensa de ziua de naștere
     */
    public function canClaimBirthdayReward(): bool
    {
        if (! $this->patient->date_of_birth) {
            return false;
        }

        $birthday = $this->patient->date_of_birth;
        $today = now();

        // Verificăm dacă este luna și ziua de naștere
        if ($birthday->month !== $today->month || $birthday->day !== $today->day) {
            return false;
        }

        // Verificăm dacă nu a fost deja revendicată anul acesta
        if ($this->birthday_reward_claimed_at && $this->birthday_reward_claimed_at->year === $today->year) {
            return false;
        }

        return true;
    }

    /**
     * Marchează recompensa de ziua de naștere ca revendicată
     */
    public function claimBirthdayReward(): bool
    {
        if (! $this->canClaimBirthdayReward()) {
            return false;
        }

        $this->update(['birthday_reward_claimed_at' => now()]);

        return true;
    }
}
