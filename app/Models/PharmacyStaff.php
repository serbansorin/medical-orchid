<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PharmacyStaff extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'pharmacy_staff';

    protected $casts = [
        'pharmacy_id' => 'int',
        'user_id' => 'int',
        'license_expiry' => 'datetime',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prescription_fulfillments()
    {
        return $this->hasMany(PrescriptionFulfillment::class, 'pharmacist_id');
    }
}
