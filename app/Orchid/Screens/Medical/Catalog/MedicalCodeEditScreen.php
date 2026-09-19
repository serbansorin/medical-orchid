<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\MedicalCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MedicalCodeEditScreen extends Screen
{
    public ?MedicalCode $code = null;

    public function query(MedicalCode $code): iterable
    {
        abort_unless((bool) request()->user()?->inRole('super_admin'), 403);

        return ['code' => $code];
    }

    public function name(): ?string
    {
        return $this->code?->exists
            ? __('catalogs.code_edit')
            : __('catalogs.code_create');
    }

    public function description(): ?string
    {
        return __('catalogs.codes_readonly');
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
                ->route('platform.catalogs.codes'),

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
                Input::make('code.code')
                    ->title(__('catalogs.code'))
                    ->maxlength(100)
                    ->required(),

                Input::make('code.code_system')
                    ->title(__('catalogs.code_system'))
                    ->maxlength(100)
                    ->required(),

                TextArea::make('code.code_text')
                    ->title(__('catalogs.code_text'))
                    ->rows(3)
                    ->required(),

                Input::make('code.code_text_short')
                    ->title(__('catalogs.code_text').' (short)')
                    ->maxlength(255),

                Input::make('code.category')
                    ->title(__('catalogs.category'))
                    ->maxlength(100),

                Input::make('code.subcategory')
                    ->title(__('catalogs.category').' (sub)')
                    ->maxlength(100),

                Switcher::make('code.active')
                    ->title(__('catalogs.active'))
                    ->sendTrueOrFalse(),

                Switcher::make('code.reportable')
                    ->title(__('catalogs.reportable'))
                    ->sendTrueOrFalse(),

                TextArea::make('code.notes')
                    ->title(__('catalogs.notes'))
                    ->rows(3),
            ]),
        ];
    }

    public function save(MedicalCode $code, Request $request): RedirectResponse
    {
        abort_unless((bool) $request->user()?->inRole('super_admin'), 403);

        $validated = $request->validate([
            'code.code' => ['required', 'string', 'max:100'],
            'code.code_system' => ['required', 'string', 'max:100'],
            'code.code_text' => ['required', 'string'],
            'code.code_text_short' => ['nullable', 'string', 'max:255'],
            'code.category' => ['nullable', 'string', 'max:100'],
            'code.subcategory' => ['nullable', 'string', 'max:100'],
            'code.active' => ['nullable'],
            'code.reportable' => ['nullable'],
            'code.notes' => ['nullable', 'string'],
        ]);

        $data = $validated['code'];
        $data['active'] = (bool) ($data['active'] ?? false);
        $data['reportable'] = (bool) ($data['reportable'] ?? false);

        $code->fill($data);
        $code->save();

        Toast::success(__('catalogs.saved'));

        return redirect()->route('platform.catalogs.codes');
    }
}
