<?php

declare(strict_types=1);

namespace App\Orchid\Filters;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class RoleFilter extends Filter
{
    /**
     * The displayable name of the filter.
     */
    public function name(): string
    {
        // NB: plain __('Roles') would load the whole lang `roles` group as
        // an array on case-insensitive filesystems — always use a grouped key.
        return __('users.roles');
    }

    /**
     * The array of matched parameters.
     */
    public function parameters(): array
    {
        return ['role'];
    }

    /**
     * Apply to a given Eloquent query builder.
     *
     * Legacy `roles` table is keyed by `name` (no `slug` column), so the
     * filter matches the role id carried in the request.
     */
    public function run(Builder $builder): Builder
    {
        return $builder->whereHas('roles', function (Builder $query) {
            $query->where('roles.id', $this->request->get('role'));
        });
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Select::make('role')
                ->fromModel(Role::class, 'name', 'id')
                ->empty()
                ->value($this->request->get('role'))
                ->title(__('users.roles')),
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        return $this->name().': '.(Role::whereKey($this->request->get('role'))->first()?->name ?? '');
    }
}
