<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TreatmentEditScreen extends Screen
{
    public ?Treatment $treatment = null;

    public function query(Treatment $treatment): iterable
    {
        $json = [];
        foreach (['protocol', 'medications', 'duration', 'meta'] as $key) {
            $value = $treatment->{$key};
            $json[$key] = is_array($value)
                ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : null;
        }

        return array_merge(['treatment' => $treatment], $json);
    }

    public function name(): ?string
    {
        return $this->treatment?->exists
            ? __('catalogs.treatment_edit')
            : __('catalogs.treatment_create');
    }

    public function permission(): ?iterable
    {
        return ['view_any_treatment'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.catalogs.treatments'),

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
                Input::make('treatment.name')
                    ->title(__('catalogs.name'))
                    ->maxlength(255)
                    ->required(),

                Input::make('treatment.slug')
                    ->title(__('catalogs.slug'))
                    ->maxlength(255),

                Input::make('treatment.category')
                    ->title(__('catalogs.category'))
                    ->maxlength(100),

                TextArea::make('treatment.description')
                    ->title(__('catalogs.description'))
                    ->rows(4),

                TextArea::make('protocol')
                    ->title(__('catalogs.protocol'))
                    ->rows(4),

                TextArea::make('medications')
                    ->title(__('catalogs.medications'))
                    ->rows(4),

                TextArea::make('duration')
                    ->title(__('catalogs.duration'))
                    ->rows(3),

                TextArea::make('meta')
                    ->title(__('catalogs.meta'))
                    ->rows(3),
            ]),
        ];
    }

    public function save(Treatment $treatment, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'treatment.name' => ['required', 'string', 'max:255'],
            'treatment.slug' => ['nullable', 'string', 'max:255'],
            'treatment.category' => ['nullable', 'string', 'max:100'],
            'treatment.description' => ['nullable', 'string'],
            'protocol' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'duration' => ['nullable', 'string'],
            'meta' => ['nullable', 'string'],
        ]);

        $data = $validated['treatment'];
        foreach (['protocol', 'medications', 'duration', 'meta'] as $key) {
            $raw = $validated[$key] ?? null;
            if ($raw === null || $raw === '') {
                $data[$key] = null;

                continue;
            }
            $decoded = json_decode((string) $raw, true);
            if (! is_array($decoded)) {
                Toast::error('Invalid JSON: '.$key.'.');

                return redirect()->back()->withInput();
            }
            $data[$key] = $decoded;
        }
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $treatment->fill($data);
        $treatment->save();

        Toast::success(__('catalogs.saved'));

        return redirect()->route('platform.catalogs.treatments');
    }
}
