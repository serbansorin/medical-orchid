<?php

namespace App\Models;

use App\Services\Catalog\CatalogPricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Orchid\Screen\AsSource;

class Product extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    public const TYPE_SERVICE = 'service';

    public const TYPE_PHYSICAL = 'physical';

    public const SERVICE_CATEGORIES = [
        'appointment',
        'procedure',
        'lab_test',
        'video_consultation',
        'in_person',
    ];

    public const PHYSICAL_CATEGORIES = [
        'medication',
        'supply',
        'equipment',
        'supplement',
    ];

    protected $table = 'products';

    protected $fillable = [
        'type',
        'product_code',
        'organization_id',
        'pharmacy_id',
        'clinic_id',
        'doctor_id',
        'specialty_id',
        'cloned_from_product_id',
        'category',
        'name',
        'description',
        'sku',
        'price',
        'stock_quantity',
        'duration_minutes',
        'available',
        'requires_payment',
        'requires_prescription',
        'image_url',
    ];

    protected $casts = [
        'organization_id' => 'int',
        'pharmacy_id' => 'int',
        'clinic_id' => 'int',
        'doctor_id' => 'int',
        'specialty_id' => 'int',
        'cloned_from_product_id' => 'int',
        'price' => 'float',
        'stock_quantity' => 'int',
        'duration_minutes' => 'int',
        'available' => 'bool',
        'requires_payment' => 'bool',
        'requires_prescription' => 'bool',
    ];

    public function cartItems(): MorphMany
    {
        return $this->morphMany(CartItem::class, 'itemable');
    }

    public function billingInvoiceItems(): HasMany
    {
        return $this->hasMany(BillingInvoiceItem::class, 'product_id');
    }

    public function scopeServices($query)
    {
        return $query->where('type', self::TYPE_SERVICE);
    }

    public function scopePhysical($query)
    {
        return $query->where('type', self::TYPE_PHYSICAL);
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeForOrganization($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeOrganizationLevel($query)
    {
        return $query->whereNotNull('organization_id')
            ->whereNull('clinic_id')
            ->whereNull('doctor_id')
            ->whereNull('pharmacy_id');
    }

    public function scopeClinicLevel($query)
    {
        return $query->whereNotNull('clinic_id')
            ->whereNull('doctor_id')
            ->whereNull('pharmacy_id');
    }

    public function scopeDoctorLevel($query)
    {
        return $query->whereNotNull('doctor_id');
    }

    public function scopePharmacyLevel($query)
    {
        return $query->whereNotNull('pharmacy_id');
    }

    public function scopeForBilling($query, ?int $organizationId = null, ?int $clinicId = null, ?int $doctorId = null)
    {
        return $query->services()
            ->available()
            ->where(function ($q) use ($organizationId, $clinicId, $doctorId) {
                $q->where(function ($subq) use ($organizationId) {
                    $subq->whereNotNull('organization_id')
                        ->whereNull('clinic_id')
                        ->whereNull('doctor_id')
                        ->whereNull('pharmacy_id');
                    if ($organizationId) {
                        $subq->where('organization_id', $organizationId);
                    }
                })
                    ->orWhere(function ($subq) use ($clinicId) {
                        if ($clinicId) {
                            $subq->whereNotNull('clinic_id')
                                ->where('clinic_id', $clinicId)
                                ->whereNull('doctor_id')
                                ->whereNull('pharmacy_id');
                        }
                    })
                    ->orWhere(function ($subq) use ($doctorId) {
                        if ($doctorId) {
                            $subq->whereNotNull('doctor_id')
                                ->where('doctor_id', $doctorId);
                        }
                    });
            });
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format((float) $this->price, 2).' '.config('billing.currency', 'RON');
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity === null || $this->stock_quantity === 0) {
            return 'out_of_stock';
        }
        if ($this->stock_quantity < 10) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->available ? 'success' : 'gray';
    }

    /**
     * @return array{
     *     price: float,
     *     duration_minutes: int|null,
     *     available: bool,
     *     requires_payment: bool,
     *     override_level: string
     * }
     */
    public function getEffectiveData(?int $clinicId = null, ?int $doctorId = null): array
    {
        return app(CatalogPricingService::class)->forProduct($this, $clinicId, $doctorId);
    }

    /**
     * @return Collection<int, Product>
     */
    public static function getEffectiveProducts(?int $clinicId = null, ?int $doctorId = null): Collection
    {
        return app(CatalogPricingService::class)->getEffectiveProducts($clinicId, $doctorId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function clonedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cloned_from_product_id');
    }

    public function clones(): HasMany
    {
        return $this->hasMany(self::class, 'cloned_from_product_id');
    }

    public function clinicOverrides(): HasMany
    {
        return $this->hasMany(ClinicProduct::class);
    }

    public function doctorOverrides(): HasMany
    {
        return $this->hasMany(DoctorProduct::class);
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class, 'clinic_product')
            ->withPivot(['price', 'duration_minutes', 'available', 'requires_payment'])
            ->withTimestamps();
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_product', 'product_id', 'doctor_id')
            ->withPivot(['clinic_id', 'price', 'duration_minutes', 'available', 'requires_payment'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    public static function serviceCategoryOptions(): array
    {
        return [
            'appointment' => __('filament/admin/product_resource.category.appointment'),
            'procedure' => __('filament/admin/product_resource.category.procedure'),
            'lab_test' => __('filament/admin/product_resource.category.lab_test'),
            'video_consultation' => __('filament/admin/product_resource.category.video_consultation'),
            'in_person' => __('filament/admin/product_resource.category.in_person'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function physicalCategoryOptions(): array
    {
        return [
            'medication' => __('filament/admin/product_resource.category.medication'),
            'supply' => __('filament/admin/product_resource.category.supply'),
            'equipment' => __('filament/admin/product_resource.category.equipment'),
            'supplement' => __('filament/admin/product_resource.category.supplement'),
        ];
    }
}
