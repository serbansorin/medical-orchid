<?php

declare(strict_types=1);

namespace App\Services\Pharmacy;

use App\Models\PharmacyStaff;

/**
 * Single place that resolves "which pharmacy is this user acting for".
 *
 * Pharmacist identity = PharmacyStaff::where('user_id', auth id)->first()?->pharmacy_id.
 * When it resolves, list queries scope to that pharmacy; when it does not
 * (super-admin, org-admin, no staff row), screens show all rows. This
 * "scope when resolvable, else show all" decision is documented here once
 * so screens never repeat the where() logic.
 */
final class PharmacyScope
{
    public static function currentPharmacyId(): ?int
    {
        $userId = auth()->id();

        if ($userId === null) {
            return null;
        }

        return PharmacyStaff::where('user_id', $userId)->first()?->pharmacy_id;
    }
}
