<?php

namespace App\Models;

use Spatie\Translatable\HasTranslations;
use Orchid\Screen\AsSource;

class Specialty extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasTranslations;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function getNameRoAttribute()
    {
        return $this->getTranslation('name', 'ro');
    }

    public function setNameRoAttribute($value)
    {
        $this->setTranslation('name', 'ro', $value);
    }

    public function getNameEnAttribute()
    {
        return $this->getTranslation('name', 'en');
    }

    public function setNameEnAttribute($value)
    {
        $this->setTranslation('name', 'en', $value);
    }

    public function getDescriptionRoAttribute()
    {
        return $this->getTranslation('description', 'ro');
    }

    public function setDescriptionRoAttribute($value)
    {
        $this->setTranslation('description', 'ro', $value);
    }

    public function getDescriptionEnAttribute()
    {
        return $this->getTranslation('description', 'en');
    }

    public function setDescriptionEnAttribute($value)
    {
        $this->setTranslation('description', 'en', $value);
    }

    protected $table = 'specialties';

    protected $casts = [
        'status' => 'bool',
    ];

    public function user_specialties()
    {
        return $this->hasMany(UserSpecialty::class);
    }
}
