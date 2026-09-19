<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Rows;

class UserAccountTypeLayout extends Rows
{
    /**
     * The legacy `users.role` varchar marker (single account type).
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Select::make('user.role')
                ->options([
                    'patient' => 'patient',
                    'doctor' => 'doctor',
                    'pharmacist' => 'pharmacist',
                    'receptionist' => 'receptionist',
                    'nurse' => 'nurse',
                    'admin' => 'admin',
                    'org_admin' => 'org_admin',
                    'super_admin' => 'super_admin',
                ])
                ->empty()
                ->title(__('users.role_marker')),
        ];
    }
}
