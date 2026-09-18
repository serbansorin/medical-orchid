<?php

namespace App\Enums;

enum TemplateBlockKind: string
{
    case StaticText = 'static_text';
    case Checkbox = 'checkbox';
    case CheckboxGroup = 'checkbox_group';
    case Radio = 'radio';
    case Input = 'input';
    case Textarea = 'textarea';
    case Number = 'number';
    case Scale = 'scale';
    case Date = 'date';
    case Signature = 'signature';

    public function label(): string
    {
        return match ($this) {
            self::StaticText => 'Text static',
            self::Checkbox => 'Bifă',
            self::CheckboxGroup => 'Grup bife',
            self::Radio => 'Opțiune unică',
            self::Input => 'Câmp text',
            self::Textarea => 'Text lung',
            self::Number => 'Număr',
            self::Scale => 'Scală',
            self::Date => 'Dată',
            self::Signature => 'Semnătură',
        };
    }
}
