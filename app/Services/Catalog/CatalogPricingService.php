<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\Clinic;
use App\Models\ClinicProduct;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class CatalogPricingService
{
    /**
     * Resolve effective catalog fields for one product.
     * Precedence: doctor override → clinic override → base product.
     * Doctor-owned rows (own / cloned) use the row itself.
     *
     * @return array{
     *     price: float,
     *     duration_minutes: int|null,
     *     available: bool,
     *     requires_payment: bool,
     *     override_level: string
     * }
     */
    public function forProduct(Product $product, ?int $clinicId = null, ?int $doctorId = null): array
    {
        $doctorId = $this->normalizeDoctorId($doctorId);

        $data = [
            'price' => (float) $product->price,
            'duration_minutes' => $product->duration_minutes,
            'available' => (bool) $product->available,
            'requires_payment' => (bool) $product->requires_payment,
            'override_level' => $this->baseOverrideLevel($product, $clinicId, $doctorId),
        ];

        if ($this->isOwnedByDoctor($product, $doctorId)) {
            return $data;
        }

        if ($clinicId) {
            $clinicOverride = $product->clinicOverrides()
                ->where('clinic_id', $clinicId)
                ->first();

            if ($clinicOverride) {
                $data['price'] = $clinicOverride->price !== null ? (float) $clinicOverride->price : $data['price'];
                $data['duration_minutes'] = $clinicOverride->duration_minutes ?? $data['duration_minutes'];
                $data['available'] = (bool) $clinicOverride->available;
                $data['requires_payment'] = (bool) $clinicOverride->requires_payment;
                $data['override_level'] = 'clinic';
            }
        }

        if ($doctorId) {
            $doctorOverride = $product->doctorOverrides()
                ->where('doctor_id', $doctorId)
                ->when($clinicId, function ($query) use ($clinicId) {
                    $query->where(function ($inner) use ($clinicId) {
                        $inner->where('clinic_id', $clinicId)->orWhereNull('clinic_id');
                    })->orderByRaw('clinic_id IS NULL ASC');
                })
                ->first();

            if ($doctorOverride) {
                $data['price'] = $doctorOverride->price !== null ? (float) $doctorOverride->price : $data['price'];
                $data['duration_minutes'] = $doctorOverride->duration_minutes ?? $data['duration_minutes'];
                $data['available'] = (bool) $doctorOverride->available;
                $data['requires_payment'] = (bool) $doctorOverride->requires_payment;
                $data['override_level'] = 'doctor';
            }
        }

        return $data;
    }

    public function unitPrice(Product $product, ?int $clinicId = null, ?int $doctorId = null): float
    {
        return (float) $this->forProduct($product, $clinicId, $doctorId)['price'];
    }

    /**
     * Effective service catalog for a clinic + doctor context.
     * Org services appear only when assigned via clinic_product (or when the
     * product is clinic-owned / doctor-owned).
     *
     * @return Collection<int, Product>
     */
    public function getEffectiveProducts(?int $clinicId = null, ?int $doctorId = null): Collection
    {
        $doctorId = $this->normalizeDoctorId($doctorId);
        $clinic = $clinicId ? Clinic::query()->find($clinicId) : null;
        $orgId = $clinic?->organization_id;

        if (! $clinicId && ! $doctorId) {
            return collect();
        }

        $products = Product::query()
            ->services()
            ->where(function ($query) use ($clinicId, $doctorId, $orgId) {
                if ($clinicId) {
                    $query->orWhere(function ($clinicOwned) use ($clinicId) {
                        $clinicOwned->where('clinic_id', $clinicId)
                            ->whereNull('doctor_id');
                    });

                    $query->orWhereIn('id', function ($pivot) use ($clinicId) {
                        $pivot->select('product_id')
                            ->from('clinic_product')
                            ->where('clinic_id', $clinicId);
                    });
                } elseif ($orgId) {
                    $query->orWhere(function ($orgLevel) use ($orgId) {
                        $orgLevel->where('organization_id', $orgId)
                            ->whereNull('clinic_id')
                            ->whereNull('doctor_id')
                            ->whereNull('pharmacy_id');
                    });
                }

                if ($doctorId) {
                    $query->orWhere('doctor_id', $doctorId);
                }
            })
            ->get();

        return $products
            ->map(function (Product $product) use ($clinicId, $doctorId) {
                $effective = $this->forProduct($product, $clinicId, $doctorId);
                $product->effective_price = $effective['price'];
                $product->effective_duration = $effective['duration_minutes'];
                $product->effective_available = $effective['available'];
                $product->override_level = $effective['override_level'];

                return $product;
            })
            ->filter(fn (Product $product) => $product->effective_available)
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function serviceOptions(?int $clinicId = null, ?int $doctorId = null): array
    {
        $currency = config('billing.currency', 'RON');

        return $this->getEffectiveProducts($clinicId, $doctorId)
            ->mapWithKeys(function (Product $product) use ($currency) {
                $price = number_format((float) $product->effective_price, 2);

                return [$product->id => "{$product->name} ({$price} {$currency})"];
            })
            ->all();
    }

    /**
     * Sum effective duration_minutes for attached catalog services.
     * Quantity defaults to 1 unless the caller passes pivot quantities.
     *
     * @param  list<int|string>|array<int, int|string>  $productIds
     * @param  array<int, int>  $quantities
     */
    public function sumServiceDurationMinutes(
        array $productIds,
        ?int $clinicId = null,
        ?int $doctorId = null,
        array $quantities = [],
    ): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));

        if ($ids === []) {
            return 0;
        }

        $products = Product::query()->whereIn('id', $ids)->get()->keyBy('id');
        $sum = 0;

        foreach ($ids as $id) {
            $product = $products->get($id);

            if (! $product) {
                continue;
            }

            $minutes = (int) ($this->forProduct($product, $clinicId, $doctorId)['duration_minutes'] ?? 0);
            $qty = max(1, (int) ($quantities[$id] ?? 1));
            $sum += max(0, $minutes) * $qty;
        }

        return $sum;
    }

    /**
     * @return array{
     *     product: Product|null,
     *     product_id: int|null,
     *     description: string|null,
     *     unit_price: float|null
     * }
     */
    public function resolveForBilling(?int $productId, ?int $clinicId, ?int $doctorId): array
    {
        if (! $productId) {
            return [
                'product' => null,
                'product_id' => null,
                'description' => null,
                'unit_price' => null,
            ];
        }

        $product = Product::query()
            ->services()
            ->findOrFail($productId);

        $effective = $this->forProduct($product, $clinicId, $doctorId);

        return [
            'product' => $product,
            'product_id' => $product->id,
            'description' => $product->name,
            'unit_price' => (float) $effective['price'],
        ];
    }

    public function cloneForDoctor(Product $source, Doctor $doctor, ?int $clinicId = null): Product
    {
        $effective = $this->forProduct($source, $clinicId, $doctor->id);

        $clone = $source->replicate([
            'sku',
            'product_code',
            'stock_quantity',
        ]);
        $clone->doctor_id = $doctor->id;
        $clone->clinic_id = null;
        $clone->pharmacy_id = null;
        $clone->cloned_from_product_id = $source->id;
        $clone->organization_id = $source->organization_id
            ?? ($clinicId ? Clinic::query()->find($clinicId)?->organization_id : null);
        $clone->type = 'service';
        $clone->price = $effective['price'];
        $clone->duration_minutes = $effective['duration_minutes'] ?? $source->duration_minutes;
        $clone->available = $effective['available'];
        $clone->requires_payment = $effective['requires_payment'];
        $clone->product_code = $this->uniqueDoctorProductCode($source, $doctor);
        $clone->save();

        return $clone;
    }

    public function assignToClinic(Product $product, Clinic $clinic, array $overrides = []): ClinicProduct
    {
        return ClinicProduct::query()->updateOrCreate(
            [
                'clinic_id' => $clinic->id,
                'product_id' => $product->id,
            ],
            [
                'price' => $overrides['price'] ?? null,
                'duration_minutes' => $overrides['duration_minutes'] ?? null,
                'available' => $overrides['available'] ?? true,
                'requires_payment' => $overrides['requires_payment'] ?? true,
            ]
        );
    }

    public function doctorIdFromUser(?User $user): ?int
    {
        if (! $user) {
            return null;
        }

        return $user->doctor?->id
            ?? Doctor::query()->where('user_id', $user->id)->value('id');
    }

    public function normalizeDoctorId(mixed $doctorId): ?int
    {
        if ($doctorId === null || $doctorId === '') {
            return null;
        }

        $id = (int) $doctorId;

        if ($id <= 0) {
            return null;
        }

        if (Doctor::query()->whereKey($id)->exists()) {
            return $id;
        }

        return Doctor::query()->where('user_id', $id)->value('id');
    }

    private function isOwnedByDoctor(Product $product, ?int $doctorId): bool
    {
        return $doctorId !== null
            && $product->doctor_id !== null
            && (int) $product->doctor_id === $doctorId;
    }

    private function baseOverrideLevel(Product $product, ?int $clinicId, ?int $doctorId): string
    {
        if ($this->isOwnedByDoctor($product, $doctorId)) {
            return $product->cloned_from_product_id ? 'cloned' : 'own';
        }

        if ($product->doctor_id) {
            return $product->cloned_from_product_id ? 'cloned' : 'own';
        }

        if ($product->clinic_id && $clinicId && (int) $product->clinic_id === $clinicId) {
            return 'clinic';
        }

        return 'organization';
    }

    private function uniqueDoctorProductCode(Product $source, Doctor $doctor): string
    {
        $base = $source->product_code
            ? $source->product_code.'-D'.$doctor->id
            : 'DOC-'.$doctor->id.'-'.strtoupper(substr(sha1((string) $source->id), 0, 6));

        $code = $base;
        $suffix = 1;

        while (Product::query()->where('product_code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}
