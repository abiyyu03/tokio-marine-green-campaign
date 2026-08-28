<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris blok "setiap tahunnya emisi harianmu setara dengan:".
 */
class EmissionEquivalence extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionEquivalenceTranslation::class;

    protected $fillable = [
        'code', 'kg_co2e_per_unit', 'decimals', 'icon',
        'source', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kg_co2e_per_unit' => 'float',
            'decimals' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** Berapa banyak satuan ini yang setara dengan emisi tahunan pengguna. */
    public function unitsFor(float $kgCo2eYear): float
    {
        if ($this->kg_co2e_per_unit <= 0) {
            return 0.0;
        }

        return round($kgCo2eYear / $this->kg_co2e_per_unit, $this->decimals);
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
