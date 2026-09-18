<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoiceItem extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $table = 'billing_invoice_items';

    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'invoice_id' => 'int',
        'product_id' => 'int',
        'quantity' => 'int',
        'unit_price' => 'float',
        'line_total' => 'float',
    ];

    public function billing_invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getEffectiveProduct(): ?Product
    {
        return $this->product;
    }
}
