<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pharmacy extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_workplaces', 'workplace_id', 'user_id')
            ->wherePivot('workplace_type', 'pharmacy')
            ->withPivot(['roles']);
    }

    protected $table = 'pharmacies';

    protected $columns = [
        'id',
        'name',
        'registration_number',
        'license_number',
        'phone',
        'email',
        'address_line1',
        'city',
        'county',
        'postal_code',
        'latitude',
        'longitude',
        'is_verified',
        'verification_date',
        'verified_by',
        'created_at',
        'updated_at',
        'organization_id',
        'slug',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_verified' => 'bool',
        'verification_date' => 'datetime',
        'verified_by' => 'int',
        'organization_id' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function pharmacy_staffs()
    {
        return $this->hasMany(PharmacyStaff::class);
    }

    public function pharmacy_inventories()
    {
        return $this->hasMany(PharmacyInventory::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'patient_pharmacy_preferences')
            ->withPivot('id', 'is_preferred')
            ->withTimestamps();
    }

    public function prescription_routings()
    {
        return $this->hasMany(PrescriptionRouting::class);
    }

    public function prescription_fulfillments()
    {
        return $this->hasMany(PrescriptionFulfillment::class);
    }

    public function patient_preferred_pharmacies()
    {
        return $this->hasMany(PatientPreferredPharmacy::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function pharmacy_suppliers()
    {
        return $this->hasMany(PharmacySupplier::class);
    }

    public function pharmacy_purchase_orders()
    {
        return $this->hasMany(PharmacyPurchaseOrder::class);
    }

    public function pharmacy_sales()
    {
        return $this->hasMany(PharmacySale::class);
    }

    public function loyalty_programs()
    {
        return $this->hasMany(LoyaltyProgram::class);
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function delivery_zones()
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function pharmacy_customer_profiles()
    {
        return $this->hasMany(PharmacyCustomerProfile::class);
    }
}
