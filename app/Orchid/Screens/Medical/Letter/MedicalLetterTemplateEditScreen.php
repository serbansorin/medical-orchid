<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Letter;

use App\Enums\TemplateKind;
use App\Models\MedicalLetterTemplate;
use App\Orchid\Layouts\Medical\MedicalLetterListLayout;
use App\Orchid\Support\ClinicalOptions;
use App\Support\MedicalLetters\MedicalLetterTemplateSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MedicalLetterTemplateEditScreen extends Screen
{
    public ?MedicalLetterTemplate $template = null;

    public function query(MedicalLetterTemplate $template): iterable
    {
        $schema = $template->schema;
        if (is_array($schema)) {
            $schema = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return [
            'template' => $template,
            'schema_json' => $schema,
        ];
    }

    public function name(): ?string
    {
        return $this->template?->exists
            ? __('letters.template_edit_title')
            : __('letters.template_create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_letter_template'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.letter-templates'),

            Link::make(__('letters.use_template'))
                ->icon('bs.file-earmark-plus')
                ->route('platform.letters.create', ['template' => $this->template?->id])
                ->canSee((bool) $this->template?->exists),

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
                    ->title(__('letters.template_name'))
                    ->maxlength(255)
                    ->required(),

                Select::make('template.type')
                    ->title(__('letters.template_type'))
                    ->options(MedicalLetterListLayout::typeOptions())
                    ->required(),

                Input::make('template.title')
                    ->title(__('letters.template_title'))
                    ->maxlength(255),

                TextArea::make('template.description')
                    ->title(__('letters.template_description'))
                    ->rows(3),

                TextArea::make('schema_json')
                    ->title(__('letters.template_schema'))
                    ->help(__('letters.template_schema_help'))
                    ->rows(10),

                Select::make('template.availability')
                    ->title(__('letters.availability'))
                    ->options([
                        'global' => __('letters.availability_global'),
                        'organization' => __('letters.availability_organization'),
                        'clinic' => __('letters.availability_clinic'),
                        'user' => __('letters.availability_user'),
                    ])
                    ->required(),

                Input::make('template.availability_id')
                    ->title(__('letters.availability_id'))
                    ->type('number'),

                Select::make('template.doctor_id')
                    ->title(__('letters.doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty(),

                Select::make('template.clinic_id')
                    ->title(__('letters.clinic'))
                    ->options(ClinicalOptions::clinics())
                    ->empty(),

                Select::make('template.template_kind')
                    ->title(__('letters.template_kind'))
                    ->options($kinds)
                    ->empty(),

                Input::make('template.sort_order')
                    ->title(__('letters.sort_order'))
                    ->type('number'),

                Input::make('template.version')
                    ->title(__('letters.version'))
                    ->type('number'),

                Switcher::make('template.is_active')
                    ->title(__('letters.is_active'))
                    ->sendTrueOrFalse(),
            ]),
        ];
    }

    public function save(MedicalLetterTemplate $template, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template.name' => ['required', 'string', 'max:255'],
            'template.type' => ['required', 'string', 'max:50'],
            'template.title' => ['nullable', 'string', 'max:255'],
            'template.description' => ['nullable', 'string'],
            'schema_json' => ['nullable', 'string'],
            'template.availability' => ['required', 'string', Rule::in(['global', 'organization', 'clinic', 'user'])],
            'template.availability_id' => ['nullable', 'integer'],
            'template.doctor_id' => ['nullable', 'integer'],
            'template.clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'template.template_kind' => ['nullable', 'string', 'max:50'],
            'template.sort_order' => ['nullable', 'integer'],
            'template.version' => ['nullable', 'integer', 'min:1'],
            'template.is_active' => ['nullable'],
        ]);

        $schema = null;
        if (! empty($validated['schema_json'])) {
            $decoded = json_decode((string) $validated['schema_json'], true);
            if (! is_array($decoded)) {
                Toast::error('Invalid JSON schema.');

                return redirect()->back()->withInput();
            }
            // Normalize through the ported legacy schema helper.
            $schema = MedicalLetterTemplateSchema::normalize($decoded);
        }

        $data = $validated['template'];
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['schema'] = $schema;

        $template->fill($data);
        $template->save();

        Toast::success(__('letters.template_saved'));

        return redirect()->route('platform.letter-templates');
    }
}
