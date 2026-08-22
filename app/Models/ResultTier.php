<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kategori hasil berdasarkan emisi tahunan per kapita (ton CO2e).
 */
class ResultTier extends Model
{
    use HasTranslations;

    protected string $translationClass = ResultTierTranslation::class;

    protected $fillable = [
        'code', 'min_ton_co2e', 'max_ton_co2e',
        'badge_icon', 'color', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_ton_co2e' => 'float',
            'max_ton_co2e' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Tier yang cocok untuk sebuah nilai, dengan rentang setengah terbuka:
     * min <= value < max.
     */
    public static function forValue(float $tonPerCapitaPerYear): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('min_ton_co2e')->orWhere('min_ton_co2e', '<=', $tonPerCapitaPerYear))
            ->where(fn ($q) => $q->whereNull('max_ton_co2e')->orWhere('max_ton_co2e', '>', $tonPerCapitaPerYear))
            ->orderBy('sort_order')
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
