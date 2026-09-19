<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Condition;
use App\Models\Symptom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ConditionEditScreen extends Screen
{
    public ?Condition $condition = null;

    public function query(Condition $condition): iterable
    {
        return [
            'condition' => $condition->loadMissing('symptoms'),
            'symptom_ids' => $condition->symptoms->pluck('id')->all(),
            'symptom_options' => Symptom::query()->orderBy('name')->limit(300)->pluck('name', 'id')->all(),
            'meta_json' => is_array($condition->meta) ? json_encode($condition->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : null,
        ];
    }

    public function name(): ?string
    {
        return $this->condition?->exists
            ? __('catalogs.condition_edit')
            : __('catalogs.condition_create');
    }

    public function permission(): ?iterable
    {
        return ['view_any_condition'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.catalogs.conditions'),

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
                Input::make('condition.name')
                    ->title(__('catalogs.name'))
                    ->maxlength(255)
                    ->required(),

                Input::make('condition.slug')
                    ->title(__('catalogs.slug'))
                    ->maxlength(255),

                Input::make('condition.icd_code')
                    ->title(__('catalogs.icd_code'))
                    ->maxlength(50),

                Input::make('condition.category')
                    ->title(__('catalogs.category'))
                    ->maxlength(100),

                TextArea::make('condition.description')
                    ->title(__('catalogs.description'))
                    ->rows(4),

                TextArea::make('meta_json')
                    ->title(__('catalogs.meta'))
                    ->rows(4),

                Select::make('symptom_ids.')
                    ->title(__('catalogs.symptoms'))
                    ->fromModel(Symptom::class, 'name')
                    ->multiple(),
            ]),
        ];
    }

    public function save(Condition $condition, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'condition.name' => ['required', 'string', 'max:255'],
            'condition.slug' => ['nullable', 'string', 'max:255'],
            'condition.icd_code' => ['nullable', 'string', 'max:50'],
            'condition.category' => ['nullable', 'string', 'max:100'],
            'condition.description' => ['nullable', 'string'],
            'meta_json' => ['nullable', 'string'],
            'symptom_ids' => ['nullable', 'array'],
            'symptom_ids.*' => ['integer', 'exists:symptoms,id'],
        ]);

        $meta = null;
        if (! empty($validated['meta_json'])) {
            $decoded = json_decode((string) $validated['meta_json'], true);
            if (! is_array($decoded)) {
                Toast::error('Invalid JSON meta.');

                return redirect()->back()->withInput();
            }
            $meta = $decoded;
        }

        $data = $validated['condition'];
        $data['meta'] = $meta;
        if (empty($data['slug'])) {
            $data['slug'] = str()->slug($data['name']);
        }

        $condition->fill($data);
        $condition->save();
        $condition->symptoms()->sync($validated['symptom_ids'] ?? []);

        Toast::success(__('catalogs.saved'));

        return redirect()->route('platform.catalogs.conditions');
    }
}
