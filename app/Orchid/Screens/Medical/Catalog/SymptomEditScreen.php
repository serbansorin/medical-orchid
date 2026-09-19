<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Symptom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SymptomEditScreen extends Screen
{
    public ?Symptom $symptom = null;

    public function query(Symptom $symptom): iterable
    {
        return [
            'symptom' => $symptom,
            'severity_json' => is_array($symptom->severity_scale)
                ? json_encode($symptom->severity_scale, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : null,
        ];
    }

    public function name(): ?string
    {
        return $this->symptom?->exists
            ? __('catalogs.symptom_edit')
            : __('catalogs.symptom_create');
    }

    public function permission(): ?iterable
    {
        return ['view_any_symptom'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.catalogs.symptoms'),

            Button::make(__('Save'))
                ->icon('bs.check-circle')
                ->type(Color::PRIMARY)
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('symptom.name')
                    ->title(__('catalogs.name'))
                    ->maxlength(255)
                    ->required(),

                Input::make('symptom.slug')
                    ->title(__('catalogs.slug'))
                    ->maxlength(255),

                Input::make('symptom.code')
                    ->title(__('catalogs.code'))
                    ->maxlength(50),

                Input::make('symptom.category')
                    ->title(__('catalogs.category'))
                    ->maxlength(100),

                Switcher::make('symptom.is_common')
                    ->title(__('catalogs.is_common'))
                    ->sendTrueOrFalse(),

                TextArea::make('symptom.notes')
                    ->title(__('catalogs.notes'))
                    ->rows(3),

                TextArea::make('severity_json')
                    ->title(__('catalogs.severity_scale'))
                    ->rows(4),
            ]),
        ];
    }

    public function save(Symptom $symptom, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'symptom.name' => ['required', 'string', 'max:255'],
            'symptom.slug' => ['nullable', 'string', 'max:255'],
            'symptom.code' => ['nullable', 'string', 'max:50'],
            'symptom.category' => ['nullable', 'string', 'max:100'],
            'symptom.notes' => ['nullable', 'string'],
            'symptom.is_common' => ['nullable'],
            'severity_json' => ['nullable', 'string'],
        ]);

        $scale = null;
        if (! empty($validated['severity_json'])) {
            $decoded = json_decode((string) $validated['severity_json'], true);
            if (! is_array($decoded)) {
                Toast::error('Invalid JSON severity scale.');

                return redirect()->back()->withInput();
            }
            $scale = $decoded;
        }

        $data = $validated['symptom'];
        $data['is_common'] = (bool) ($data['is_common'] ?? false);
        $data['severity_scale'] = $scale;
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $symptom->fill($data);
        $symptom->save();

        Toast::success(__('catalogs.saved'));

        return redirect()->route('platform.catalogs.symptoms');
    }
}
