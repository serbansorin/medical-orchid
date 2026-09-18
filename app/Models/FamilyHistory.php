<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyHistory extends \Illuminate\Database\Eloquent\Model
{
    //

    use SoftDeletes;

    protected $table = 'family_history';

    protected $casts = [
        'patient_id' => 'int',
        'age_at_onset' => 'int',
        'is_alive' => 'bool',
        'age_at_death' => 'int',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
