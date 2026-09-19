<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Orchid\Screen\AsSource;

class PatientInvitation extends Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\PatientInvitationFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'code',
        'created_by_user_id',
        'clinic_id',
        'patient_name',
        'patient_email',
        'patient_phone',
        'patient_birthdate',
        'notes',
        'status',
        'registered_user_id',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'patient_birthdate' => 'date',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user who created this invitation (usually a doctor or admin).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the clinic associated with this invitation.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Get the user who registered using this invitation.
     */
    public function registeredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_user_id');
    }

    /**
     * Generate a unique invitation code.
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Check if invitation is valid (pending and not expired).
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isFuture();
    }

    /**
     * Mark invitation as used by a user.
     */
    public function markAsUsed(User $user): void
    {
        $this->update([
            'status' => 'used',
            'registered_user_id' => $user->id,
            'used_at' => now(),
        ]);
    }

    /**
     * Mark invitation as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (self $invitation) {
            if (empty($invitation->code)) {
                $invitation->code = self::generateCode();
            }

            if (empty($invitation->status)) {
                $invitation->status = 'pending';
            }

            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }
}
