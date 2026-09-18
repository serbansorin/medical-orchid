<?php

namespace App\Models;

use App\Enums\DrugInteractionSeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class DrugInteraction
 *
 * Interacțiuni între medicamente
 *
 * @property int $id
 * @property string $drug_a_code
 * @property string $drug_a_name
 * @property string $drug_b_code
 * @property string $drug_b_name
 * @property DrugInteractionSeverity $severity
 * @property string|null $description
 * @property string|null $mechanism
 * @property string|null $clinical_effects
 * @property string|null $recommendation
 * @property string|null $source
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class DrugInteraction extends Model
{
    use HasFactory;

    protected $table = 'drug_interactions';

    protected $fillable = [
        'drug_a_code',
        'drug_a_name',
        'drug_b_code',
        'drug_b_name',
        'severity',
        'description',
        'mechanism',
        'clinical_effects',
        'recommendation',
        'source',
    ];

    protected $casts = [
        'severity' => DrugInteractionSeverity::class,
    ];

    /**
     * Verifică interacțiunile dintre o listă de medicamente
     *
     * @param  array  $medications  Array de coduri sau nume de medicamente
     * @return Collection Colecție de interacțiuni găsite
     */
    public static function checkInteractions(array $medications): Collection
    {
        if (count($medications) < 2) {
            return collect();
        }

        return self::query()
            ->where(function ($query) use ($medications) {
                $query->whereIn('drug_a_code', $medications)
                    ->orWhereIn('drug_a_name', $medications);
            })
            ->where(function ($query) use ($medications) {
                $query->whereIn('drug_b_code', $medications)
                    ->orWhereIn('drug_b_name', $medications);
            })
            ->orderByRaw("FIELD(severity, 'contraindicated', 'severe', 'moderate', 'mild')")
            ->get();
    }

    /**
     * Verifică dacă există interacțiuni severe sau contraindicate
     */
    public static function hasCriticalInteractions(array $medications): bool
    {
        return self::checkInteractions($medications)
            ->filter(fn ($interaction) => in_array($interaction->severity, [
                DrugInteractionSeverity::Severe,
                DrugInteractionSeverity::Contraindicated,
            ]))
            ->isNotEmpty();
    }

    /**
     * Scope pentru interacțiuni de o anumită severitate
     */
    public function scopeBySeverity($query, DrugInteractionSeverity $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope pentru interacțiuni care necesită alertă
     */
    public function scopeRequiringAlert($query)
    {
        return $query->whereIn('severity', [
            DrugInteractionSeverity::Moderate,
            DrugInteractionSeverity::Severe,
            DrugInteractionSeverity::Contraindicated,
        ]);
    }
}
