<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain_code',
        'cui',
        'reg_com',
        'address',
        'city',
        'county',
        'country',
        'contact_email',
        'contact_phone',
        'website',
        'is_active',
    ];

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }

    public function pharmacies(): HasMany
    {
        return $this->hasMany(Pharmacy::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_workplaces', 'organization_id', 'user_id')
            ->distinct();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected $table = 'organizations';

    protected $columns = [
        'id',
        'name',
        'cui',
        'reg_com',
        'address',
        'city',
        'county',
        'country',
        'contact_email',
        'contact_phone',
        'website',
        'is_active',
        'created_at',
        'updated_at',
        'subdomain_code',
        'slug',
        'type',
        'postal_code',
        'phone',
        'email',
        'logo_url',
        'settings_json',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'settings_json' => 'json',
    ];
}
