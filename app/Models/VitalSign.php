<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

/**
 * Class VitalSign
 *
 * @property int $id
 * @property int $patient_id
 * @property int|null $doctor_id
 * @property int|null $appointment_id
 * @property Carbon $measurement_date
 * @property string $measurement_location
 * @property int|null $blood_pressure_systolic
 * @property int|null $blood_pressure_diastolic
 * @property string|null $blood_pressure_position
 * @property int|null $heart_rate
 * @property int|null $respiratory_rate
 * @property float|null $temperature
 * @property string|null $temperature_method
 * @property float|null $weight
 * @property float|null $height
 * @property float|null $bmi
 * @property string|null $bmi_category
 * @property float|null $waist_circumference
 * @property float|null $head_circumference
 * @property float|null $oxygen_saturation
 * @property float|null $oxygen_flow_rate
 * @property int|null $blood_glucose
 * @property string|null $glucose_measurement_type
 * @property int|null $pain_score
 * @property string|null $pain_location
 * @property int|null $measured_by
 * @property string|null $device_used
 * @property string|null $notes
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Appointment|null $appointment
 * @property User|null $user
 * @property Doctor|null $doctor
 * @property Patient $patient
 */
class VitalSign extends Model
{
    use AsSource;

    protected $table = 'vital_signs';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'measurement_date' => 'datetime',
        'blood_pressure_systolic' => 'int',
        'blood_pressure_diastolic' => 'int',
        'heart_rate' => 'int',
        'respiratory_rate' => 'int',
        'temperature' => 'float',
        'weight' => 'float',
        'height' => 'float',
        'bmi' => 'float',
        'waist_circumference' => 'float',
        'head_circumference' => 'float',
        'oxygen_saturation' => 'float',
        'oxygen_flow_rate' => 'float',
        'blood_glucose' => 'int',
        'pain_score' => 'int',
        'measured_by' => 'int',
        'created_by' => 'int',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'measured_by');
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
