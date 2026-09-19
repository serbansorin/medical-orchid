<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class MedicineDocument extends Model
{
    use AsSource;

    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'type',
        'status',
        'consultation_date',
        'publish_date',
        'last_update_date',
        'reference_number',
        'url',
        'source',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'consultation_date' => 'date',
        'publish_date' => 'datetime',
        'last_update_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
