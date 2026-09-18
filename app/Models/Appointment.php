<?php

namespace App\Models;

use App\Services\Catalog\CatalogPricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the products (services/procedures) associated with this appointment.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'appointment_product')
            ->withPivot('quantity', 'price', 'notes')
            ->withTimestamps();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the medical histories recorded during this appointment.
     */
    public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class);
    }

    public function diagnostics(): BelongsToMany
    {
        return $this->belongsToMany(PatientDiagnostic::class, 'patient_diagnostic_appointments')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Mark the appointment as paid.
     */
    public function markAsPaid(string $method = 'cash'): bool
    {
        return $this->update([
            'payment_status' => 'paid',
            'payment_method' => $method,
            'paid_at' => now(),
            'final_price' => $this->final_price ?? $this->resolvedBillableTotal(),
        ]);
    }

    public function resolvedBillableTotal(): float
    {
        $this->loadMissing('products', 'product');

        $pivotTotal = $this->products->sum(function (Product $product) {
            $quantity = (int) ($product->pivot->quantity ?? 1);

            return (float) ($product->pivot->price ?? 0) * max(1, $quantity);
        });

        if ($pivotTotal > 0) {
            return round($pivotTotal, 2);
        }

        if ($this->product) {
            return app(CatalogPricingService::class)
                ->unitPrice($this->product, $this->clinic_id, $this->doctor_id);
        }

        return 0.0;
    }

    /**
     * Mark the appointment as cancelled.
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'payment_status' => 'cancelled',
        ]);
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if payment is complete.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Scope for pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope for paid appointments.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeSearch($query, $value)
    {
        $query->where('appointment_date', 'like', "%{$value}%")
            ->orWhere('appointment_time', 'like', "%{$value}%")
            ->orWhereHas('doctor.user', function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%");
            })
            ->orWhereHas('patient.user', function ($q) use ($value) {
                $q->where('name', 'like', "%{$value}%");
            });
    }

    protected $table = 'appointments';

    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'patient_id',
        'product_id',
        'appointment_date',
        'appointment_time',
        'duration',
        'status',
        'appointment_type',
        'notes',
        'payment_status',
        'payment_method',
        'final_price',
        'paid_at',
    ];

    protected $casts = [
        'doctor_id' => 'int',
        'clinic_id' => 'int',
        'patient_id' => 'int',
        'product_id' => 'int',
        'appointment_date' => 'datetime',
        'appointment_time' => 'datetime',
        'duration' => 'int',
        'final_price' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment_note()
    {
        return $this->hasOne(AppointmentNote::class);
    }

    public function billing_invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function lab_results()
    {
        return $this->hasMany(LabResult::class);
    }

    public function medical_attachments()
    {
        return $this->hasMany(MedicalAttachment::class);
    }

    public function medical_certificates()
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    public function medical_letters()
    {
        return $this->hasMany(MedicalLetter::class);
    }

    public function medical_records()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class);
    }

    public function vital_signs()
    {
        return $this->hasMany(VitalSign::class);
    }
}
