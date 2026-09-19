<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Catalog;

use App\Models\Procedure;
use App\Orchid\Support\ClinicalOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ProcedureEditScreen extends Screen
{
    public ?Procedure $procedure = null;

    public function query(Procedure $procedure): iterable
    {
        return ['procedure' => $procedure];
    }

    public function name(): ?string
    {
        return $this->procedure?->exists
            ? __('catalogs.procedure_edit')
            : __('catalogs.procedure_create');
    }

    public function permission(): ?iterable
    {
        return ['view_any_medical_record'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Cancel'))
                ->icon('bs.arrow-left')
                ->route('platform.catalogs.procedures'),

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
                Select::make('procedure.patient_id')
                    ->title(__('catalogs.patient'))
                    ->options(ClinicalOptions::patients())
                    ->empty()
                    ->required(),

                Select::make('procedure.doctor_id')
                    ->title(__('catalogs.doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty()
                    ->required(),

                Select::make('procedure.clinic_id')
                    ->title(__('catalogs.clinic'))
                    ->options(ClinicalOptions::clinics())
                    ->empty(),

                Input::make('procedure.procedure_name')
                    ->title(__('catalogs.procedure_name'))
                    ->maxlength(255)
                    ->required(),

                Input::make('procedure.procedure_code')
                    ->title(__('catalogs.procedure_code'))
                    ->maxlength(100),

                Select::make('procedure.status')
                    ->title(__('catalogs.status'))
                    ->options([
                        'scheduled' => __('catalogs.status_scheduled'),
                        'in_progress' => __('catalogs.status_in_progress'),
                        'completed' => __('catalogs.status_completed'),
                        'cancelled' => __('catalogs.status_cancelled'),
                    ])
                    ->required(),

                Select::make('procedure.priority')
                    ->title(__('catalogs.priority'))
                    ->options([
                        'routine' => __('catalogs.priority_routine'),
                        'urgent' => __('catalogs.priority_urgent'),
                        'elective' => __('catalogs.priority_elective'),
                    ]),

                Select::make('procedure.category')
                    ->title(__('catalogs.category'))
                    ->options([
                        'diagnostic' => 'diagnostic',
                        'therapeutic' => 'therapeutic',
                        'surgical' => 'surgical',
                        'preventive' => 'preventive',
                    ]),

                DateTimer::make('procedure.scheduled_at')
                    ->title(__('catalogs.scheduled_at'))
                    ->format('Y-m-d H:i')
                    ->enableTime()
                    ->allowInput(),

                TextArea::make('procedure.procedure_description')
                    ->title(__('catalogs.description'))
                    ->rows(3),

                TextArea::make('procedure.notes')
                    ->title(__('catalogs.notes'))
                    ->rows(3),
            ]),
        ];
    }

    public function save(Procedure $procedure, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'procedure.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'procedure.doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'procedure.clinic_id' => ['nullable', 'integer', 'exists:clinics,id'],
            'procedure.procedure_name' => ['required', 'string', 'max:255'],
            'procedure.procedure_code' => ['nullable', 'string', 'max:100'],
            'procedure.status' => ['required', 'string', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'procedure.priority' => ['nullable', 'string', 'max:50'],
            'procedure.category' => ['nullable', 'string', 'max:50'],
            'procedure.scheduled_at' => ['nullable', 'date'],
            'procedure.procedure_description' => ['nullable', 'string'],
            'procedure.notes' => ['nullable', 'string'],
        ]);

        $data = $validated['procedure'];
        $userId = $request->user()?->id;
        if (! $procedure->exists) {
            $data['created_by'] = $userId;
        }
        $data['updated_by'] = $userId;

        $procedure->fill($data);
        $procedure->save();

        Toast::success(__('catalogs.saved'));

        return redirect()->route('platform.catalogs.procedures');
    }
}
