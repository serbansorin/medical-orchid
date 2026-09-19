<?php

declare(strict_types=1);

namespace App\Orchid\Support;

use App\Models\Clinic;
use App\Models\Condition;
use App\Models\Doctor;
use App\Models\MedicalLetterTemplate;
use App\Models\Patient;
use App\Models\Pharmacy;

class ClinicalOptions
{
    /** @return array<int,string> */
    public static function patients(): array
    {
        return Patient::query()->orderByDesc('id')->limit(200)->get()
            ->mapWithKeys(fn (Patient $p): array => [
                $p->id => trim($p->first_name.' '.$p->last_name).($p->cnp ? ' ('.$p->cnp.')' : ''),
            ])->all();
    }

    /** @return array<int,string> */
    public static function doctors(): array
    {
        return Doctor::query()->orderByDesc('id')->limit(200)->get()
            ->mapWithKeys(fn (Doctor $d): array => [
                $d->id => trim($d->first_name.' '.$d->last_name),
            ])->all();
    }

    /** @return array<int,string> */
    public static function clinics(): array
    {
        return Clinic::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all();
    }

    /** @return array<int,string> */
    public static function pharmacies(): array
    {
        return Pharmacy::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all();
    }

    /** @return array<int,string> */
    public static function conditions(): array
    {
        return Condition::query()->orderBy('name')->limit(300)->get()
            ->mapWithKeys(fn (Condition $c): array => [
                $c->id => $c->name.($c->icd_code ? ' ('.$c->icd_code.')' : ''),
            ])->all();
    }

    /** @return array<int,string> */
    public static function letterTemplates(): array
    {
        return MedicalLetterTemplate::query()->where('is_active', true)->orderBy('name')->limit(200)
            ->pluck('name', 'id')->all();
    }
}
