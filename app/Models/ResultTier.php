<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kategori hasil berdasarkan skor poin 0-100.
 */
class ResultTier extends Model
{
    use HasTranslations;

    protected string $translationClass = ResultTierTranslation::class;

    protected $fillable = [
        'code', 'min_score', 'max_score',
        'approx_min_ton_co2e', 'approx_max_ton_co2e',
        'community_avoided_min_ton_co2e', 'community_avoided_max_ton_co2e',
        'badge_icon', 'color', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'integer',
            'max_score' => 'integer',
            'approx_min_ton_co2e' => 'float',
            'approx_max_ton_co2e' => 'float',
            'community_avoided_min_ton_co2e' => 'float',
            'community_avoided_max_ton_co2e' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Tier untuk sebuah skor. Rentang tertutup di kedua ujung agar cocok
     * dengan legenda gauge di desain: 0-30, 31-60, 61-100.
     */
    public static function forScore(int $score): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }

    /** "0-30" untuk legenda dan tabel "Total poin dikategorikan". */
    public function scoreRangeLabel(): string
    {
        return $this->min_score.'-'.$this->max_score;
    }

    /**
     * Estimasi emisi tahunan (ton CO2e) untuk sebuah skor di dalam tier ini.
     *
     * Dokumen menetapkan tiap tier sebagai rentang emisi, bukan satu angka:
     * 0-30 poin -> 1,5-2 ton | 31-60 -> 2-3 ton | 61-100 -> 3-5 ton.
     * Skor dipetakan secara linier ke posisinya di dalam rentang itu,
     * sehingga 61 poin jatuh tepat di 3 ton dan 100 poin di 5 ton.
     */
    public function estimatedTonForScore(int $score): ?float
    {
        if ($this->approx_min_ton_co2e === null) {
            return null;
        }

        $min = (float) $this->approx_min_ton_co2e;
        $max = (float) ($this->approx_max_ton_co2e ?? $this->approx_min_ton_co2e);
        $span = $this->max_score - $this->min_score;

        if ($span <= 0) {
            return $min;
        }

        $position = ($score - $this->min_score) / $span;
        $position = max(0.0, min(1.0, $position));

        return $min + ($max - $min) * $position;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
