<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Medical\Record;

use App\Models\MedicalRecord;
use App\Orchid\Support\ClinicalOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Attachment\Models\Attachment;
use Orchid\Attachment\Models\Attachmentable;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Attach;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MedicalRecordEditScreen extends Screen
{
    public ?MedicalRecord $record = null;

    public function query(MedicalRecord $record): iterable
    {
        $attachments = collect();
        if ($record->exists) {
            $ids = Attachmentable::query()
                ->where('attachmentable_type', MedicalRecord::class)
                ->where('attachmentable_id', $record->id)
                ->pluck('attachment_id');
            $attachments = Attachment::query()->whereIn('id', $ids)->orderBy('id')->get();
        }

        return [
            'record' => $record,
            'attachments' => $attachments,
        ];
    }

    public function name(): ?string
    {
        return $this->record?->exists
            ? __('records.edit_title')
            : __('records.create_title');
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
                ->route('platform.records'),

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
                Select::make('record.patient_id')
                    ->title(__('records.patient'))
                    ->options(ClinicalOptions::patients())
                    ->empty()
                    ->required(),

                Select::make('record.doctor_id')
                    ->title(__('records.doctor'))
                    ->options(ClinicalOptions::doctors())
                    ->empty(),

                Select::make('record.record_type')
                    ->title(__('records.record_type'))
                    ->options([
                        'record' => __('records.type_record'),
                        'note' => __('records.type_note'),
                        'certificate' => __('records.type_certificate'),
                        'consent' => __('records.type_consent'),
                    ])
                    ->required(),

                Input::make('record.title')
                    ->title(__('records.title'))
                    ->maxlength(255)
                    ->required(),

                TextArea::make('record.description')
                    ->title(__('records.description'))
                    ->rows(5),

                Input::make('record.test_name')
                    ->title(__('records.test_name'))
                    ->maxlength(255),

                Input::make('record.result_value')
                    ->title(__('records.result_value'))
                    ->maxlength(255),

                Input::make('record.result_unit')
                    ->title(__('records.result_unit'))
                    ->maxlength(50),

                Input::make('record.reference_range')
                    ->title(__('records.reference_range'))
                    ->maxlength(255),

                Input::make('record.status')
                    ->title(__('records.status'))
                    ->maxlength(50),

                DateTimer::make('record.created_date')
                    ->title(__('records.created_date'))
                    ->format('Y-m-d')
                    ->allowInput()
                    ->required(),

                DateTimer::make('record.received_date')
                    ->title(__('records.received_date'))
                    ->format('Y-m-d')
                    ->allowInput(),

                Attach::make('record_attachments')
                    ->title(__('records.files'))
                    ->help(__('records.files_help'))
                    ->multiple()
                    ->storage('public')
                    ->group('medical_records'),
            ]),

            Layout::view('orchid.medical.record-attachments'),
        ];
    }

    public function save(MedicalRecord $record, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'record.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'record.doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'record.appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'record.record_type' => ['required', 'string', 'in:record,note,certificate,consent'],
            'record.title' => ['required', 'string', 'max:255'],
            'record.description' => ['nullable', 'string'],
            'record.test_name' => ['nullable', 'string', 'max:255'],
            'record.result_value' => ['nullable', 'string', 'max:255'],
            'record.result_unit' => ['nullable', 'string', 'max:50'],
            'record.reference_range' => ['nullable', 'string', 'max:255'],
            'record.status' => ['nullable', 'string', 'max:50'],
            'record.created_date' => ['required', 'date'],
            'record.received_date' => ['nullable', 'date'],
        ]);

        // Explicit assignment: legacy Reliese model has no fillable/guarded override.
        $input = $validated['record'];
        foreach ($input as $key => $value) {
            $record->{$key} = $value;
        }
        $record->save();

        foreach ((array) $request->input('record_attachments', []) as $attachmentId) {
            $exists = Attachmentable::query()
                ->where('attachmentable_type', MedicalRecord::class)
                ->where('attachmentable_id', $record->id)
                ->where('attachment_id', (int) $attachmentId)
                ->exists();
            if (! $exists) {
                $link = new Attachmentable;
                $link->forceFill([
                    'attachmentable_type' => MedicalRecord::class,
                    'attachmentable_id' => $record->id,
                    'attachment_id' => (int) $attachmentId,
                ])->save();
            }
        }

        Toast::success(__('records.saved'));

        return redirect()->route('platform.records');
    }

    public function deleteAttachment(Request $request): void
    {
        $attachmentId = (int) $request->input('attachment_id');
        $recordId = (int) $request->input('record_id');

        Attachmentable::query()
            ->where('attachmentable_type', MedicalRecord::class)
            ->where('attachmentable_id', $recordId)
            ->where('attachment_id', $attachmentId)
            ->delete();

        Attachment::query()->whereKey($attachmentId)->delete();

        Toast::info(__('records.file_deleted'));
    }
}
