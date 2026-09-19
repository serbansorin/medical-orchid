<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Letter;

use App\Models\MedicalLetter;
use App\Models\MedicalLetterTemplate;
use App\Orchid\Layouts\Medical\MedicalLetterListLayout;
use App\Orchid\Support\ClinicalOptions;
use App\Support\MedicalLetters\MedicalLetterTemplateSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MedicalLetterEditScreen extends Screen
{
    public ?MedicalLetter $letter = null;

    public function query(MedicalLetter $letter): iterable
    {
        // New letter from a template (?template=ID): prefill via ported schema defaults.
        if (! $letter->exists && ($templateId = (int) request()->query('template', 0)) > 0) {
            $template = MedicalLetterTemplate::query()->find($templateId);
            if ($template) {
                $defaults = MedicalLetterTemplateSchema::defaults($template->schema);
                $letter->template_id = $template->id;
                $letter->letter_type = $letter->letter_type ?? $template->type;
                $letter->title = $letter->title ?? $defaults['title'];
                $letter->content = $letter->content ?? $defaults['content'];
                $letter->diagnosis = $letter->diagnosis ?? $defaults['diagnosis'];
                $letter->recommendations = $letter->recommendations ?? $defaults['recommendations'];
            }
        }

        return ['letter' => $letter];
    }

    public function name(): ?string
    {
        return $this->letter?->exists
            ? __('letters.edit_title')
            : __('letters.create_title');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_letter'];
    }

    public function commandBar(): iterable
    {
        $locked = (bool) $this->letter?->is_signed;

        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.letters'),

            Button::make(__('letters.sign'))
                ->icon('bs.pen')
                ->type(Color::WARNING)
                ->method('sign')
                ->canSee((bool) $this->letter?->exists && ! $locked),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save')
                ->canSee(! $locked),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Select::make('letter.patient_id')
                    ->title(__('letters.patient'))
                    ->options(ClinicalOptions::patients())
                    ->empty()
                    ->required(),

                Select::make('letter.doctor_id')
                    ->title(__('letters.doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty()
                    ->required(),

                Select::make('letter.clinic_id')
                    ->title(__('letters.clinic'))
                    ->options(ClinicalOptions::clinics())
                    ->empty(),

                Select::make('letter.template_id')
                    ->title(__('letters.template'))
                    ->options(ClinicalOptions::letterTemplates())
                    ->empty()
                    ->help(__('letters.templates_description')),

                Select::make('letter.letter_type')
                    ->title(__('letters.letter_type'))
                    ->options(MedicalLetterListLayout::typeOptions())
                    ->required(),

                Input::make('letter.title')
                    ->title(__('letters.title'))
                    ->maxlength(255)
                    ->required(),

                Quill::make('letter.content')
                    ->title(__('letters.content'))
                    ->required(),

                TextArea::make('letter.diagnosis')
                    ->title(__('letters.diagnosis'))
                    ->rows(3),

                TextArea::make('letter.recommendations')
                    ->title(__('letters.recommendations'))
                    ->rows(3),

                DateTimer::make('letter.letter_date')
                    ->title(__('letters.letter_date'))
                    ->format('Y-m-d')
                    ->allowInput()
                    ->required(),

                Input::make('letter.reference_number')
                    ->title(__('letters.reference_number'))
                    ->maxlength(100),

                Input::make('letter.referred_to_doctor')
                    ->title(__('letters.referred_to_doctor'))
                    ->maxlength(255),

                Input::make('letter.referred_to_clinic')
                    ->title(__('letters.referred_to_clinic'))
                    ->maxlength(255),

                Input::make('letter.referred_specialty')
                    ->title(__('letters.referred_specialty'))
                    ->maxlength(255),
            ]),
        ];
    }

    public function save(MedicalLetter $letter, Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        // Prefill empty body fields from the chosen template schema defaults.
        if (! empty($data['template_id']) && empty($data['content'])) {
            $template = MedicalLetterTemplate::query()->find((int) $data['template_id']);
            if ($template) {
                $defaults = MedicalLetterTemplateSchema::defaults($template->schema);
                foreach (['title', 'content', 'diagnosis', 'recommendations'] as $key) {
                    if (empty($data[$key]) && ! empty($defaults[$key])) {
                        $data[$key] = $defaults[$key];
                    }
                }
            }
        }

        $letter->fill($data);
        $letter->letter_date ??= now()->toDateString();
        $letter->save();

        Toast::success(__('letters.saved'));

        return redirect()->route('platform.letters');
    }

    public function sign(MedicalLetter $letter): RedirectResponse
    {
        $letter->is_signed = true;
        $letter->signed_at = now();
        $letter->save();

        Toast::success(__('letters.signed_msg'));

        return redirect()->route('platform.letters');
    }

    /** @return array<string,mixed> */
    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'letter.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'letter.doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'letter.clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'letter.appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'letter.template_id' => ['nullable', 'integer', 'exists:medical_letter_templates,id'],
            'letter.letter_type' => ['required', 'string', 'max:50'],
            'letter.title' => ['required', 'string', 'max:255'],
            'letter.content' => ['nullable', 'string'],
            'letter.diagnosis' => ['nullable', 'string'],
            'letter.recommendations' => ['nullable', 'string'],
            'letter.letter_date' => ['required', 'date'],
            'letter.reference_number' => ['nullable', 'string', 'max:100'],
            'letter.referred_to_doctor' => ['nullable', 'string', 'max:255'],
            'letter.referred_to_clinic' => ['nullable', 'string', 'max:255'],
            'letter.referred_specialty' => ['nullable', 'string', 'max:255'],
        ]);

        return $validated['letter'];
    }
}
