<?php

declare(strict_types=1);

namespace App\Support\MedicalLetters;

// Ported verbatim from legacy-medical/app/Support/MedicalLetters/MedicalLetterTemplateSchema.php
// (framework-free helper; no Filament/Livewire coupling to adapt).
class MedicalLetterTemplateSchema
{
    public static function normalize(?array $schema): array
    {
        $schema ??= [];

        $fields = array_values(array_filter($schema['fields'] ?? [], static function ($field): bool {
            $key = $field['key'] ?? null;

            return is_string($key) && trim($key) !== '';
        }));
        $defaults = is_array($schema['defaults'] ?? null) ? $schema['defaults'] : [];
        $sections = is_array($schema['sections'] ?? null) ? $schema['sections'] : [];

        if ($sections === [] && $fields !== []) {
            $sections = [
                [
                    'key' => 'document_body',
                    'title' => 'Document body',
                    'fields' => array_map(fn (array $field): string => (string) $field['key'], $fields),
                ],
            ];
        }

        return [
            'version' => (int) ($schema['version'] ?? 1),
            'country' => (string) ($schema['country'] ?? 'RO'),
            'document_key' => (string) ($schema['document_key'] ?? 'medical-letter'),
            'defaults' => [
                'title' => $defaults['title'] ?? null,
                'content' => $defaults['content'] ?? null,
                'diagnosis' => $defaults['diagnosis'] ?? null,
                'recommendations' => $defaults['recommendations'] ?? null,
                'structured_data' => is_array($defaults['structured_data'] ?? null) ? $defaults['structured_data'] : [],
            ],
            'sections' => array_values(array_map(static function (array $section): array {
                return [
                    'key' => (string) ($section['key'] ?? 'section'),
                    'title' => (string) ($section['title'] ?? 'Section'),
                    'description' => $section['description'] ?? null,
                    'fields' => array_values(array_map('strval', $section['fields'] ?? [])),
                ];
            }, $sections)),
            'fields' => array_values(array_map(static function (array $field): array {
                return [
                    'key' => (string) $field['key'],
                    'type' => (string) ($field['type'] ?? 'text'),
                    'label' => (string) ($field['label'] ?? $field['key']),
                    'required' => (bool) ($field['required'] ?? false),
                    'rows' => isset($field['rows']) ? (int) $field['rows'] : null,
                    'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
                    'placeholder' => $field['placeholder'] ?? null,
                    'help_text' => $field['help_text'] ?? null,
                    'default' => $field['default'] ?? null,
                    'section' => $field['section'] ?? null,
                ];
            }, $fields)),
        ];
    }

    public static function fields(?array $schema): array
    {
        return self::normalize($schema)['fields'];
    }

    public static function sections(?array $schema): array
    {
        $normalized = self::normalize($schema);
        $fieldsByKey = [];
        foreach ($normalized['fields'] as $field) {
            $fieldsByKey[$field['key']] = $field;
        }

        return array_values(array_map(static function (array $section) use ($fieldsByKey): array {
            return [
                ...$section,
                'field_definitions' => array_values(array_filter(array_map(
                    static fn (string $fieldKey): ?array => $fieldsByKey[$fieldKey] ?? null,
                    $section['fields']
                ))),
            ];
        }, $normalized['sections']));
    }

    public static function defaults(?array $schema): array
    {
        $normalized = self::normalize($schema);
        $defaults = $normalized['defaults'];

        foreach ($normalized['fields'] as $field) {
            if (array_key_exists('default', $field) && $field['default'] !== null) {
                $defaults['structured_data'][$field['key']] = $field['default'];
            }
        }

        return $defaults;
    }
}
