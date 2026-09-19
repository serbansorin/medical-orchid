<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Organize\Specialties;

use App\Models\Specialty;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class SpecialtyListLayout extends Table
{
    protected $target = 'specialties';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('specialties.name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()->placeholder(__('specialties.search_name')))
                ->render(function (Specialty $s): string {
                    $locale = (string) app()->getLocale();
                    $name = $s->getTranslation('name', $locale)
                        ?: $s->getTranslation('name', 'en')
                        ?: $s->getTranslation('name', 'ro');

                    return (string) Link::make(e((string) $name))
                        ->route('platform.specialties.edit', $s);
                }),

            TD::make('doctors_count', __('specialties.doctors'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (Specialty $s): int => (int) ($s->doctors_count ?? 0)),

            TD::make('status', __('specialties.status'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }
}
