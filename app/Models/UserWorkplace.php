<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class UserWorkplace extends MorphPivot
{
    protected $table = 'user_workplaces';

    public $incrementing = true;

    public $timestamps = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'organization_id',
        'workplace_type',
        'workplace_id',
        'roles',
        'settings',
        'is_active',
        'status',
    ];

    protected $casts = [
        'roles' => 'array',
        'settings' => 'array',
        'is_active' => 'bool',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserWorkplace $workplace): void {
            if (filled($workplace->organization_id) || blank($workplace->workplace_id)) {
                return;
            }

            $organizationId = match ($workplace->workplace_type) {
                'clinic', Clinic::class => Clinic::query()->whereKey($workplace->workplace_id)->value('organization_id'),
                'pharmacy', Pharmacy::class => Pharmacy::query()->whereKey($workplace->workplace_id)->value('organization_id'),
                default => null,
            };

            if ($organizationId) {
                $workplace->organization_id = $organizationId;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the parent workplace model (clinic or pharmacy).
     */
    public function workplace()
    {
        return $this->morphTo(null, 'workplace_type', 'workplace_id');
    }
}
