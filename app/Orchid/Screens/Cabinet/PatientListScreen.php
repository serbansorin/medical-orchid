<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Cabinet;

use App\Models\Patient;
use App\Orchid\Layouts\Cabinet\PatientListLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PatientListScreen extends Screen
{
    public function query(): iterable
    {
        $search = trim((string) request()->query('search', ''));

        $query = Patient::query()->orderBy('last_name')->orderBy('first_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('cnp', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return [
            'patients' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ];
    }

    public function name(): ?string
    {
        return __('patients.list_title');
    }

    public function description(): ?string
    {
        return __('patients.list_description');
    }

    public function permission(): ?iterable
    {
        return ['view_any_patient'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('patients.create_title'))
                ->route('platform.patients.create')
                ->icon('bs.plus-circle'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('search')
                    ->title(__('patients.search'))
                    ->placeholder(__('patients.search_placeholder'))
                    ->value(request()->query('search', '')),
                Button::make(__('patients.search'))->method('applyFilter')->icon('bs.search'),
            ]),
            PatientListLayout::class,
        ];
    }

    public function applyFilter(Request $request): mixed
    {
        return redirect()->route('platform.patients', ['search' => $request->input('search')]);
    }

    public function delete(Request $request): void
    {
        Patient::query()->whereKey($request->input('id'))->firstOrFail()->delete();
        Toast::success(__('patients.deleted'));
    }
}
