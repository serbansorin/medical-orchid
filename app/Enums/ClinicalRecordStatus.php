<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ClinicalRecordStatus: string implements HasColor, HasIcon, HasLabel
{
    // Investigation / event / narrative lifecycle
    case Draft = 'draft';
    case Ordered = 'ordered';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    // Clinical-fact lifecycle
    case Active = 'active';
    case Chronic = 'chronic';
    case InRemission = 'in_remission';
    case Resolved = 'resolved';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function getIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return $this->icon();
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Ordered => 'Ordered',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Active => 'Active',
            self::Chronic => 'Chronic',
            self::InRemission => 'In remission',
            self::Resolved => 'Resolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Ordered => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Active => 'success',
            self::Chronic => 'warning',
            self::InRemission => 'info',
            self::Resolved => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Ordered => 'heroicon-o-clipboard-document-list',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Completed => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Active => 'heroicon-o-bolt',
            self::Chronic => 'heroicon-o-clock',
            self::InRemission => 'heroicon-o-pause-circle',
            self::Resolved => 'heroicon-o-check-badge',
        };
    }

    /**
     * Statuses allowed for a given clinical record type group.
     *
     * @return list<self>
     */
    public static function allowedFor(ClinicalRecordType|string|null $type): array
    {
        $enum = $type instanceof ClinicalRecordType
            ? $type
            : (is_string($type) ? ClinicalRecordType::tryFrom(ClinicalRecordType::normalize($type)) : null);

        if ($enum === null) {
            return self::cases();
        }

        if (in_array($enum, ClinicalRecordType::clinicalFactTypes(), true)) {
            return [
                self::Active,
                self::Chronic,
                self::InRemission,
                self::Resolved,
                self::Cancelled,
            ];
        }

        // investigation, event, narrative, other
        return [
            self::Draft,
            self::Ordered,
            self::InProgress,
            self::Completed,
            self::Cancelled,
        ];
    }

    /** @return list<string> */
    public static function allowedValuesFor(ClinicalRecordType|string|null $type): array
    {
        return array_map(static fn (self $s): string => $s->value, self::allowedFor($type));
    }
}
