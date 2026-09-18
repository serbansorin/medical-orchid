<?php

namespace App\Models;

use App\Services\ClinicCodeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Clinic extends \Illuminate\Database\Eloquent\Model
{
    public const SLUG_MAX_LENGTH = 48;

    /** @use HasFactory<\Database\Factories\ClinicFactory> */
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'slug', 'code', 'type', 'is_active'];

    protected static function booted(): void
    {
        static::creating(function (Clinic $clinic): void {
            if (blank($clinic->code)) {
                $clinic->code = ClinicCodeGenerator::generateSequential('CLN', self::class);
            }

            if (blank($clinic->slug)) {
                $clinic->slug = self::generateUniqueSlug($clinic->name);
            }
        });

        static::updating(function (Clinic $clinic): void {
            if (blank($clinic->code)) {
                $clinic->code = ClinicCodeGenerator::generateSequential('CLN', self::class);
            }

            if (blank($clinic->slug)) {
                $clinic->slug = self::generateUniqueSlug($clinic->name, $clinic->getKey());
            }
        });
    }

    protected static function generateUniqueSlug(?string $name, ?int $ignoreId = null): string
    {
        $baseSlug = self::slugBaseFromName($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            self::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = self::slugWithSuffix($baseSlug, $suffix);
            $suffix++;
        }

        return $slug;
    }

    public static function slugBaseFromName(?string $name): string
    {
        $baseSlug = Str::slug($name ?: 'clinica');

        if ($baseSlug === '') {
            return 'clinica';
        }

        $trimmed = Str::limit($baseSlug, self::SLUG_MAX_LENGTH, '');
        $trimmed = rtrim($trimmed, '-');

        return $trimmed !== '' ? $trimmed : 'clinica';
    }

    public static function slugWithSuffix(string $baseSlug, int $suffix): string
    {
        $suffixToken = sprintf('-%02d', $suffix);
        $maxBaseLength = max(1, self::SLUG_MAX_LENGTH - strlen($suffixToken));
        $trimmedBase = rtrim(Str::limit($baseSlug, $maxBaseLength, ''), '-');

        return ($trimmedBase !== '' ? $trimmedBase : 'clinica').$suffixToken;
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'clinic_patient');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_workplaces', 'workplace_id', 'user_id')
            ->wherePivot('workplace_type', 'clinic')
            ->withPivot(['roles']);
    }

    public function doctors()
    {
        return $this->users()->whereJsonContains('user_workplaces.roles', 'doctor');
    }

    public function staff()
    {
        return $this->users()->where(function ($query) {
            $query->whereJsonContains('user_workplaces.roles', 'clinic_receptionist')
                ->orWhereJsonContains('user_workplaces.roles', 'receptionist')
                ->orWhereJsonContains('user_workplaces.roles', 'nurse');
        });
    }

    public function pharmacies()
    {
        return $this->hasMany(Pharmacy::class, 'organization_id', 'organization_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'clinic_product')
            ->withPivot(['price', 'duration_minutes', 'available', 'requires_payment'])
            ->withTimestamps();
    }

    protected $table = 'clinics';

    protected $columns = [
        'id',
        'name',
        'code',
        'type',
        'phone',
        'email',
        'website',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'timezone',
        'is_active',
        'opened_at',
        'created_at',
        'updated_at',
        'slug',
        'organization_id',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'opened_at' => 'datetime',
        'organization_id' => 'int',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function clinic_departments()
    {
        return $this->hasMany(ClinicDepartment::class);
    }

    public function billing_invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function internal_messages()
    {
        return $this->hasMany(InternalMessage::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

    public function calendar_events()
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function ownedProducts()
    {
        return $this->hasMany(Product::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
