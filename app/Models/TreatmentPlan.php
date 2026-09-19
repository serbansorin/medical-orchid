<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

/**
 * Class TreatmentPlan
 *
 * @property int $id
 * @property int $patient_id
 * @property int $doctor_id
 * @property int|null $condition_id
 * @property string $plan_name
 * @property string $description
 * @property string|null $goals
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $review_date
 * @property string $status
 * @property string $priority
 * @property string|null $medications
 * @property string|null $procedures
 * @property string|null $lifestyle_changes
 * @property string|null $diet_recommendations
 * @property string|null $exercise_plan
 * @property string|null $follow_up_schedule
 * @property string|null $notes
 * @property string|null $progress_notes
 * @property int $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property PatientCondition|null $patient_condition
 * @property User|null $user
 * @property Doctor $doctor
 * @property Patient $patient
 */
class TreatmentPlan extends Model
{
    use AsSource;

    use SoftDeletes;

    protected $table = 'treatment_plans';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'condition_id' => 'int',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'review_date' => 'datetime',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    public function patient_condition()
    {
        return $this->belongsTo(PatientCondition::class, 'condition_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
