<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Clinic;

/**
 * Clinic Code Generator (ported verbatim from legacy-medical, no Filament/Livewire deps).
 *
 * Generates unique 5-character clinic codes:
 * - 3 consonants from clinic name
 * - 1-2 digits (00-99)
 *
 * Examples:
 * - "Pharmacy Catena" => "PCT00", "PCT01", etc.
 * - "Farmacia Dona Uverturii" => "FDU00", "FDU01", etc.
 * - "Clinica Centrala Bucuresti" => "CCB00", "CCB01", etc.
 */
class ClinicCodeGenerator
{
    /**
     * Extract consonants from text.
     */
    private static function extractConsonants(string $text): string
    {
        // Remove diacritics
        $text = self::removeDiacritics($text);

        // Keep only letters and convert to uppercase
        $letters = preg_replace('/[^a-zA-Z]/', '', $text);
        $letters = strtoupper((string) $letters);

        // Extract consonants (all letters except a, e, i, o, u)
        $consonants = '';
        foreach (str_split($letters) as $char) {
            if (! in_array($char, ['A', 'E', 'I', 'O', 'U', 'Y'], true)) {
                $consonants .= $char;
            }
        }

        return $consonants;
    }

    /**
     * Remove diacritical marks.
     */
    private static function removeDiacritics(string $text): string
    {
        $replacements = [
            'ă' => 'a',
            'Ă' => 'A',
            'â' => 'a',
            'Â' => 'A',
            'ș' => 's',
            'Ș' => 'S',
            'ț' => 't',
            'Ț' => 'T',
        ];

        return strtr($text, $replacements);
    }

    /**
     * Generate clinic code from clinic name.
     */
    public static function generate(string $clinicName, int $sequence = 0): string
    {
        // Extract consonants (take up to 3)
        $consonants = self::extractConsonants($clinicName);
        $baseCode = strtoupper(substr($consonants, 0, 3));

        // Ensure we have 3 characters (pad with 'X' if needed)
        $baseCode = str_pad($baseCode, 3, 'X');

        // Add 2-digit sequence number (00-99)
        $sequence = abs($sequence) % 100;
        $code = $baseCode.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

        return $code;
    }

    /**
     * Generate unique clinic code.
     * Check against database for uniqueness.
     */
    public static function generateUnique(string $clinicName, ?int $existingClinicId = null): string
    {
        $sequence = 0;

        while ($sequence < 100) {
            $code = self::generate($clinicName, $sequence);

            // Check if code exists in database
            $query = Clinic::where('code', $code);

            if ($existingClinicId) {
                $query->where('id', '!=', $existingClinicId);
            }

            if (! $query->exists()) {
                return $code;
            }

            $sequence++;
        }

        // Fallback: use timestamp-based code
        return self::generate($clinicName).mt_rand(10, 99);
    }

    /**
     * Example usage and test function.
     *
     * @return array<string, string>
     */
    public static function examples(): array
    {
        return [
            'Pharmacy Catena' => self::generate('Pharmacy Catena', 0),
            'Farmacia Dona Uverturii' => self::generate('Farmacia Dona Uverturii', 0),
            'Clinica Centrala Bucuresti' => self::generate('Clinica Centrala Bucuresti', 0),
            'Dr. Ion Vasile Clinic' => self::generate('Dr. Ion Vasile Clinic', 0),
            'PharmaCare Plus' => self::generate('PharmaCare Plus', 0),
        ];
    }

    /**
     * Generate sequential codes like CLN-001, PHR-001.
     */
    public static function generateSequential(string $prefix, string $modelClass, string $column = 'code', int $pad = 3): string
    {
        $latestCode = $modelClass::query()
            ->where($column, 'like', $prefix.'-%')
            ->orderByDesc($column)
            ->value($column);

        $nextNumber = 1;

        if ($latestCode) {
            $parts = explode('-', (string) $latestCode);
            $number = (int) ($parts[1] ?? 0);
            $nextNumber = $number + 1;
        }

        // NB: legacy passed STR_PAD_LEFT as the pad string (TypeError on PHP 8);
        // the intended behaviour is zero-padded sequential codes like CLN-001.
        return sprintf('%s-%s', $prefix, str_pad((string) $nextNumber, $pad, '0', STR_PAD_LEFT));
    }
}
