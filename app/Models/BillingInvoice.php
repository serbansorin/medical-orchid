<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\AsSource;

class BillingInvoice extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    public function items()
    {
        return $this->billing_invoice_items();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDocumentUrlAttribute(): ?string
    {
        if (! $this->document_path) {
            return null;
        }

        return Storage::disk('public')->url($this->document_path);
    }

    protected $table = 'billing_invoices';

    protected $casts = [
        'patient_id' => 'int',
        'appointment_id' => 'int',
        'doctor_id' => 'int',
        'clinic_id' => 'int',
        'created_by' => 'int',
        'invoice_date' => 'datetime',
        'due_date' => 'datetime',
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'locked_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

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

    public function billing_invoice_items()
    {
        return $this->hasMany(BillingInvoiceItem::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
}
