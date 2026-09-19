<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Pharmacy;
use App\Models\UserWorkplace;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class UserWorkplaceTable extends Table
{
    protected $target = 'workplaces';

    /**
     * Resolve the workplace display name without a morph map
     * (workplace_type stores plain 'clinic'/'pharmacy' strings).
     *
     * @var array<string, string>
     */
    private static array $nameCache = [];

    protected function textNotFound(): string
    {
        return __('users.no_workplaces');
    }

    protected function columns(): iterable
    {
        return [
            TD::make('organization', __('users.organization'))
                ->render(fn (UserWorkplace $w): string => e((string) ($w->organization?->name ?? '—'))),

            TD::make('workplace', __('users.workplace'))
                ->render(fn (UserWorkplace $w): string => e(self::workplaceName($w))),

            TD::make('roles', __('users.roles'))
                ->render(fn (UserWorkplace $w): string => e(implode(', ', (array) ($w->roles ?? [])) ?: '—')),

            TD::make('is_active', __('Active'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (UserWorkplace $w): string => $w->is_active ? __('Yes') : __('No')),
        ];
    }

    private static function workplaceName(UserWorkplace $workplace): string
    {
        $key = $workplace->workplace_type.':'.$workplace->workplace_id;

        if (! array_key_exists($key, self::$nameCache)) {
            $name = match ($workplace->workplace_type) {
                'clinic' => Clinic::whereKey($workplace->workplace_id)->value('name'),
                'pharmacy' => Pharmacy::whereKey($workplace->workplace_id)->value('name'),
                'organization' => Organization::whereKey($workplace->workplace_id)->value('name'),
                default => null,
            };

            self::$nameCache[$key] = (string) ($name ?? $workplace->workplace_type.' #'.$workplace->workplace_id);
        }

        return self::$nameCache[$key];
    }
}
