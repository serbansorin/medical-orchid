<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Medical;

use App\Enums\MedicalLetterType;
use App\Models\MedicalLetter;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\Boolean;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class MedicalLetterListLayout extends Table
{
    protected $target = 'letters';

    protected function columns(): iterable
    {
        return [
            TD::make('letter_date', __('letters.letter_date'))
                ->sort()
                ->render(fn (MedicalLetter $l): string => (string) Link::make(e($l->letter_date?->format('Y-m-d') ?? '—'))
                    ->route('platform.letters.edit', $l)),

            TD::make('patient', __('letters.patient'))
                ->filter(Input::make()->placeholder(__('letters.search_patient')))
                ->render(fn (MedicalLetter $l): string => e(trim((string) ($l->patient?->first_name.' '.$l->patient?->last_name)) ?: '—')),

            TD::make('title', __('letters.title'))
                ->cantHide()
                ->render(fn (MedicalLetter $l): string => e((string) $l->title)),

            TD::make('letter_type', __('letters.letter_type'))
                ->filter(Select::make()->options(self::typeOptions())->empty())
                ->render(fn (MedicalLetter $l): string => e((string) ($l->letter_type ?? '—'))),

            TD::make('is_signed', __('letters.signed'))
                ->align(TD::ALIGN_CENTER)
                ->usingComponent(Boolean::class, true: __('Yes'), false: __('No')),
        ];
    }

    /** @return array<string,string> */
    public static function typeOptions(): array
    {
        $options = [];
        foreach (MedicalLetterType::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
