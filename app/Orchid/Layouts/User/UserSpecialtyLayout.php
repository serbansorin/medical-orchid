<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\Specialty;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class UserSpecialtyLayout extends Rows
{
    /**
     * Specialty options labelled in the current locale.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        $locale = (string) app()->getLocale();

        $options = Specialty::orderBy('id')->limit(200)->get()
            ->mapWithKeys(fn (Specialty $s): array => [
                $s->id => $s->getTranslation('name', $locale) ?: $s->getTranslation('name', 'en') ?: '#'.$s->id,
            ])
            ->toArray();

        return [
            Select::make('user.specialty_ids.')
                ->options($options)
                ->multiple()
                ->title(__('users.specialties')),
        ];
    }
}
