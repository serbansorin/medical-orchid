<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Organize\Specialties;

use App\Models\Specialty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SpecialtyEditScreen extends Screen
{
    public ?Specialty $specialty = null;

    public function query(Specialty $specialty): iterable
    {
        return [
            'specialty' => $specialty,
        ];
    }

    public function name(): ?string
    {
        return $this->specialty && $this->specialty->exists
            ? __('specialties.edit_title')
            : __('specialties.create_title');
    }

    public function description(): ?string
    {
        return __('specialties.form_description');
    }

    public function permission(): ?iterable
    {
        return ['access_admin_panel'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('specialties.back'))
                ->icon('bs.arrow-left')
                ->route('platform.specialties'),

            Button::make(__('specialties.remove'))
                ->icon('bs.trash3')
                ->confirm(__('specialties.removed'))
                ->method('remove')
                ->canSee((bool) ($this->specialty && $this->specialty->exists)),

            Button::make(__('specialties.save'))
                ->icon('bs.check-circle')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                // Per-locale inputs bound through the name_ro/name_en and
                // description_ro/description_en mutators on the model.
                Input::make('specialty.name_ro')
                    ->title(__('specialties.name_ro'))
                    ->required()
                    ->maxlength(255),

                Input::make('specialty.name_en')
                    ->title(__('specialties.name_en'))
                    ->required()
                    ->maxlength(255),

                TextArea::make('specialty.description_ro')
                    ->title(__('specialties.description_ro'))
                    ->rows(3),

                TextArea::make('specialty.description_en')
                    ->title(__('specialties.description_en'))
                    ->rows(3),

                Switcher::make('specialty.status')
                    ->title(__('specialties.status'))
                    ->sendTrueOrFalse(),
            ]),
        ];
    }

    public function save(Request $request, Specialty $specialty): RedirectResponse
    {
        $data = $request->validate([
            'specialty.name_ro' => 'required|string|max:255',
            'specialty.name_en' => 'required|string|max:255',
            'specialty.description_ro' => 'nullable|string',
            'specialty.description_en' => 'nullable|string',
            'specialty.status' => 'nullable|boolean',
        ])['specialty'];

        $specialty->name_ro = $data['name_ro'];
        $specialty->name_en = $data['name_en'];
        $specialty->description_ro = $data['description_ro'] ?? null;
        $specialty->description_en = $data['description_en'] ?? null;
        $specialty->status = (bool) ($data['status'] ?? false);
        $specialty->save();

        Toast::info(__('specialties.saved'));

        return redirect()->route('platform.specialties');
    }

    public function remove(Specialty $specialty): RedirectResponse
    {
        $specialty->delete();

        Toast::info(__('specialties.removed'));

        return redirect()->route('platform.specialties');
    }
}
