<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Letter;

use App\Models\MedicalLetterTemplate;
use App\Orchid\Layouts\Medical\MedicalLetterTemplateListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class MedicalLetterTemplateListScreen extends Screen
{
    public function query(): iterable
    {
        $filter = (array) request()->get('filter', []);

        $query = MedicalLetterTemplate::query()
            ->when(isset($filter['name']) && $filter['name'] !== '',
                fn ($q) => $q->where('name', 'ilike', '%'.$filter['name'].'%'))
            ->when(isset($filter['type']) && $filter['type'] !== '',
                fn ($q) => $q->where('type', $filter['type']))
            ->orderBy('sort_order')
            ->orderBy('name');

        return ['templates' => $query->paginate(25)];
    }

    public function name(): ?string
    {
        return __('letters.templates_title');
    }

    public function description(): ?string
    {
        return __('letters.templates_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_letter_template'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('letters.template_create_title'))
                ->icon('bs.plus-circle')
                ->route('platform.letter-templates.create'),
        ];
    }

    public function layout(): iterable
    {
        return [MedicalLetterTemplateListLayout::class];
    }
}
