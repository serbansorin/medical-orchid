<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitServiceLine extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'visit_service_lines';

    protected $guarded = ['id'];

    public const STATUSES = [
        'pending' => 'În așteptare',
        'billed' => 'Facturată',
        'void' => 'Anulată',
    ];

    protected $casts = [
        'visit_id' => 'int',
        'product_id' => 'int',
        'quantity' => 'float',
        'unit_price' => 'float',
        'billed_invoice_id' => 'int',
        'created_by' => 'int',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(MedicalVisit::class, 'visit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'billed_invoice_id');
    }
}
