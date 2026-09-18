<?php

namespace App\Models;

class JobBatch extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'name',
        'total_jobs',
        'pending_jobs',
        'failed_jobs',
        'failed_job_ids',
        'options',
        'cancelled_at',
        'finished_at',
    ];

    protected $table = 'job_batches';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'total_jobs' => 'int',
        'pending_jobs' => 'int',
        'failed_jobs' => 'int',
        'cancelled_at' => 'int',
        'finished_at' => 'int',
    ];
}
