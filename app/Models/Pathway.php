<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

class Pathway extends Model
{
    use AsSource;

    use HasFactory;

    protected $casts = [
        'templates' => 'array',
        'schedule' => 'array',
        'meta' => 'array',
    ];

    protected $fillable = ['name', 'slug', 'description', 'templates', 'schedule', 'meta', 'parent_id', 'type'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Pathway::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Pathway::class, 'parent_id');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(PatientNode::class);
    }

    public function schedulers(): HasMany
    {
        return $this->hasMany(PathwayScheduler::class)->orderBy('order');
    }

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(Condition::class, 'condition_pathway')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'pathway_treatment')
            ->withPivot('order', 'settings')
            ->withTimestamps()
            ->orderByPivot('order');
    }
}
