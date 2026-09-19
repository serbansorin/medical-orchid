<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

/**
 * Class PharmacySupplier
 *
 * Furnizor pentru farmacie
 *
 * @property int $id
 * @property int $pharmacy_id
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $tax_id
 * @property string|null $payment_terms
 * @property bool $is_active
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property Pharmacy $pharmacy
 * @property \Illuminate\Database\Eloquent\Collection|PharmacyPurchaseOrder[] $purchaseOrders
 */
class PharmacySupplier extends Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'pharmacy_suppliers';

    protected $fillable = [
        'pharmacy_id',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_id',
        'payment_terms',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'pharmacy_id' => 'int',
        'is_active' => 'bool',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PharmacyPurchaseOrder::class, 'supplier_id');
    }
}
