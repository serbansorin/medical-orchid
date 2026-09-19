<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class UserSpecialty extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'user_id',
        'specialty_id',
    ];

    protected $table = 'user_specialties';

    protected $casts = [
        'user_id' => 'int',
        'specialty_id' => 'int',
    ];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
