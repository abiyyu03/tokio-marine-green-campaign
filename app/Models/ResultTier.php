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
        'badge_icon', 'color', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'integer',
            'max_score' => 'integer',
            'approx_min_ton_co2e' => 'float',
            'approx_max_ton_co2e' => 'float',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
