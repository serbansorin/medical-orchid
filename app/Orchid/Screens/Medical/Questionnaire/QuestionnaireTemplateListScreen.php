<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Questionnaire;

use App\Models\QuestionnaireTemplate;
use App\Orchid\Layouts\Medical\QuestionnaireTemplateListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class QuestionnaireTemplateListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = QuestionnaireTemplate::query()
            ->when(isset($filter['name']) && $filter['name'] !== '',
                fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
            ->orderBy('name');

        return ['templates' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('questionnaires.list_title');
    }

    public function description(): ?string
    {
        return __('questionnaires.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_questionnaire_template'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('questionnaires.create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.questionnaires.create'),
        ];
    }

    public function layout(): iterable
    {
        return [QuestionnaireTemplateListLayout::class];
    }
}
