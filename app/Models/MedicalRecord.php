<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

/**
 * Class MedicalRecord
 *
 * @property int $id
 * @property int $patient_id
 * @property int|null $appointment_id
 * @property int|null $doctor_id
 * @property string $record_type
 * @property string $title
 * @property string|null $description
 * @property string|null $test_name
 * @property string|null $result_value
 * @property string|null $result_unit
 * @property string|null $reference_range
 * @property string|null $status
 * @property string|null $document_path
 * @property string|null $file_type
 * @property int|null $file_size
 * @property Carbon $created_date
 * @property Carbon|null $received_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Appointment|null $appointment
 * @property Doctor|null $doctor
 * @property Patient $patient
 */
class MedicalRecord extends Model
{
    use AsSource;

    protected $table = 'medical_records';

    protected $casts = [
        'patient_id' => 'int',
        'appointment_id' => 'int',
        'doctor_id' => 'int',
        'file_size' => 'int',
        'created_date' => 'datetime',
        'received_date' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
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
