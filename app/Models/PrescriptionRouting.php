<?php

namespace App\Models;

class PrescriptionRouting extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'prescription_id',
        'pharmacy_id',
        'status',
        'notes',
        'status_changed_at',
    ];

    protected $table = 'prescription_routing';

    protected $casts = [
        'prescription_id' => 'int',
        'pharmacy_id' => 'int',
        'status_changed_at' => 'datetime',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
