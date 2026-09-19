<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Questionnaire;

use App\Enums\TemplateKind;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class QuestionnaireTemplateEditScreen extends Screen
{
    public ?QuestionnaireTemplate $template = null;

    public function query(QuestionnaireTemplate $template): iterable
    {
        $questions = $template->schema['questions'] ?? [];
        if (! is_array($questions)) {
            $questions = [];
        }
        // Matrix options cell stores comma-separated text; normalize arrays back.
        $questions = array_map(static function ($q): array {
            $q = is_array($q) ? $q : [];
            if (isset($q['options']) && is_array($q['options'])) {
                $q['options'] = implode(', ', $q['options']);
            }

            return [
                'label' => (string) ($q['label'] ?? ''),
                'type' => (string) ($q['type'] ?? 'text'),
                'required' => (string) ($q['required'] ?? ''),
                'options' => (string) ($q['options'] ?? ''),
            ];
        }, array_values($questions));

        return [
            'template' => $template,
            'questions' => $questions,
        ];
    }

    public function name(): ?string
    {
        return $this->template?->exists
            ? __('questionnaires.edit_title')
            : __('questionnaires.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_questionnaire_template'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.questionnaires'),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $kinds = [];
        foreach (TemplateKind::cases() as $case) {
            $kinds[$case->value] = $case->label();
        }

        return [
            Layout::rows([
                Input::make('template.name')
                    ->title(__('questionnaires.name'))
                    ->maxlength(255)
                    ->required(),

                Input::make('template.slug')
                    ->title(__('questionnaires.slug'))
                    ->maxlength(255),

                TextArea::make('template.description')
                    ->title(__('questionnaires.description'))
                    ->rows(3),

                Select::make('template_kind')
                    ->title(__('questionnaires.template_kind'))
                    ->options($kinds)
                    ->empty(),
            ]),

            Layout::block(Layout::rows([
                Matrix::make('questions')
                    ->title(__('questionnaires.questions'))
                    ->columns(['label', 'type', 'required', 'options'])
                    ->fields([
                        'label' => Input::make()->placeholder(__('questionnaires.q_label')),
                        'type' => Select::make()->options([
                            'text' => 'text',
                            'number' => 'number',
                            'select' => 'select',
                            'scale' => 'scale',
                            'date' => 'date',
                        ]),
                        'required' => Select::make()->options(['1' => __('Yes'), '0' => __('No')])->empty(),
                        'options' => Input::make()->placeholder(__('questionnaires.q_options')),
                    ])
                    ->maxRows(50),
            ]))->title(__('questionnaires.questions'))->description(__('questionnaires.questions_help')),
        ];
    }

    public function save(QuestionnaireTemplate $template, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template.name' => ['required', 'string', 'max:255'],
            'template.slug' => ['nullable', 'string', 'max:255'],
            'template.description' => ['nullable', 'string'],
            'template_kind' => ['nullable', 'string', 'max:50'],
            'questions' => ['nullable', 'array'],
        ]);

        $questions = [];
        foreach ((array) ($validated['questions'] ?? []) as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $options = array_values(array_filter(array_map(
                static fn (string $o): string => trim($o),
                explode(',', (string) ($row['options'] ?? ''))
            )));
            $questions[] = [
                'label' => $label,
                'type' => (string) ($row['type'] ?? 'text'),
                'required' => (string) ($row['required'] ?? '') === '1',
                'options' => $options,
            ];
        }

        $data = $validated['template'];
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['schema'] = ['questions' => $questions];

        $template->fill($data);
        // NOT NULL columns outside the verbatim fillable: assign explicitly.
        $template->template_kind = $validated['template_kind'] ?: 'questionnaire';
        $template->version = $template->exists ? ((int) $template->version + 1) : 1;
        $template->save();

        Toast::success(__('questionnaires.saved').' (v'.$template->version.')');

        return redirect()->route('platform.questionnaires');
    }
}
