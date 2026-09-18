<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $casts = [
        'invoice_id' => 'int',
        'amount' => 'float',
        'paid_at' => 'datetime',
        'received_by' => 'int',
    ];

    public function billing_invoice()
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
