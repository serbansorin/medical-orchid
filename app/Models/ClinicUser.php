<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class ClinicUser extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'clinic_id',
        'user_id',
    ];

    protected $table = 'clinic_user';

    protected $casts = [
        'clinic_id' => 'int',
        'user_id' => 'int',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
