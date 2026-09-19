<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Role-based dashboard redirect (Step 1).
 *
 * Resolution order: admin → doctor → pharmacist → fallback to platform.main.
 * Never 500s: a user with no roles lands on platform.main.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('platform.main');
        }

        $roleNames = $user->roles->pluck('name')->map(
            fn ($name): string => strtolower((string) $name)
        )->all();

        if (
            $user->isSuperAdmin()
            || $user->hasAnyAccess('access_admin_panel')
            || array_intersect($roleNames, ['admin', 'org_admin']) !== []
        ) {
            return redirect()->route('platform.dashboard.admin');
        }

        if (
            $user->hasAnyAccess('access_doctor_panel')
            || array_intersect($roleNames, ['doctor', 'doctor_admin', 'nurse']) !== []
        ) {
            return redirect()->route('platform.dashboard.doctor');
        }

        if (
            $user->hasAnyAccess('access_pharmacy_panel')
            || array_intersect($roleNames, ['pharmacist', 'pharmacy', 'pharmacy_manager']) !== []
        ) {
            return redirect()->route('platform.dashboard.pharmacist');
        }

        return redirect()->route('platform.main');
    }
}
