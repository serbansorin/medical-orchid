<?php

declare(strict_types=1);

namespace App\Services\Cabinet;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

/**
 * Patient create/update (Step 3 — Cabinet). Thin persistence only;
 * validation lives in the screens.
 */
class PatientService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Patient
    {
        return DB::transaction(
            fn (): Patient => Patient::query()->create($this->attributes($data))
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data): Patient {
            $patient->update($this->attributes($data));

            return $patient->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'county' => $data['county'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'cnp' => $data['cnp'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
        ];
    }
}
