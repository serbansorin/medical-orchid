<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Models\QuestionnaireTemplate;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class QuestionnaireTemplateListLayout extends Table
{
    protected $target = 'templates';

    protected function columns(): iterable
    {
        return [
            TD::make('name', __('questionnaires.name'))
                ->cantHide()
                ->sort()
                ->filter(Input::make()->placeholder(__('questionnaires.search_name')))
                ->render(fn (QuestionnaireTemplate $t): string => (string) Link::make(e((string) $t->name))
                    ->route('platform.questionnaires.edit', $t)),

            TD::make('version', __('questionnaires.version'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (QuestionnaireTemplate $t): string => e('v'.(string) $t->version)),

            TD::make('questions_count', __('questionnaires.questions_count'))
                ->align(TD::ALIGN_CENTER)
                ->render(fn (QuestionnaireTemplate $t): int => is_array($t->schema) ? count($t->schema['questions'] ?? []) : 0),
        ];
    }
}
