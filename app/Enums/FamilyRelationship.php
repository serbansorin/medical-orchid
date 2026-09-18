<?php

namespace App\Enums;

enum FamilyRelationship: string
{
    case MOTHER = 'mother';
    case FATHER = 'father';
    case SIBLING = 'sibling';
    case GRANDPARENT = 'grandparent';
    case CHILD = 'child';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MOTHER => 'Mamă',
            self::FATHER => 'Tată',
            self::SIBLING => 'Frate/Soră',
            self::GRANDPARENT => 'Bunic/Bunică',
            self::CHILD => 'Copil',
            self::OTHER => 'Altă rudă',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MOTHER => 'danger',
            self::FATHER => 'info',
            self::SIBLING => 'primary',
            self::GRANDPARENT => 'warning',
            self::CHILD => 'success',
            self::OTHER => 'gray',
        };
    }

    public static function options(): array
    {
        return [
            self::MOTHER->value => self::MOTHER->label(),
            self::FATHER->value => self::FATHER->label(),
            self::SIBLING->value => self::SIBLING->label(),
            self::GRANDPARENT->value => self::GRANDPARENT->label(),
            self::CHILD->value => self::CHILD->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }
}
