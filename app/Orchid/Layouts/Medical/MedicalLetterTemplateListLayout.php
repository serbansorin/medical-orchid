<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\MedicalLetterTemplate;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class MedicalLetterTemplateListLayout extends Table
{
    protected $target = 'templates';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('letters.template_name'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('letters.template_name')))
                ->render(fn (MedicalLetterTemplate $t): string => (string) Link::make(e((string) $t->name))
                    ->route('platform.letter-templates.edit', $t)),

            TD::make('type', __('letters.template_type'))
                ->filter(Select::make()->options(MedicalLetterListLayout::typeOptions())->empty())
                ->render(fn (MedicalLetterTemplate $t): string => e((string) ($t->type ?? '—'))),

            TD::make('is_active', __('letters.is_active'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),

            TD::make('version', __('letters.version'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (MedicalLetterTemplate $t): string => e((string) $t->version)),
        ];
    }
}
