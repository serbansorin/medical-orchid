<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class PrescriptionFulfillment extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'prescription_id',
        'pharmacy_id',
        'status',
        'quantity_dispensed',
        'dispensed_date',
        'expiry_date',
        'pharmacist_id',
        'accepted_at',
        'estimated_ready_at',
        'notes',
    ];

    protected $casts = [
        'prescription_id' => 'int',
        'pharmacy_id' => 'int',
        'quantity_dispensed' => 'int',
        'dispensed_date' => 'datetime',
        'expiry_date' => 'datetime',
        'pharmacist_id' => 'int',
        'accepted_at' => 'datetime',
        'estimated_ready_at' => 'datetime',
    ];

    protected $table = 'prescription_fulfillments';

    public function pharmacy_staff()
    {
        return $this->belongsTo(PharmacyStaff::class, 'pharmacist_id');
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
