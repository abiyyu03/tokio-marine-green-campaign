<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pembanding di halaman hasil. `max_value` mengubahnya jadi rentang
 * ("2 - 2,5 Ton CO2/tahun"); bila null, `value` adalah angka tunggal.
 */
class EmissionBenchmark extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionBenchmarkTranslation::class;

    protected $fillable = [
        'code', 'value', 'max_value', 'unit', 'is_primary',
        'source', 'reference_year', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'max_value' => 'float',
            'is_primary' => 'boolean',
            'reference_year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isRange(): bool
    {
        return $this->max_value !== null && $this->max_value > $this->value;
    }

    /** Batas atas rentang; sama dengan `value` bila bukan rentang. */
    public function upperValue(): float
    {
        return $this->isRange() ? $this->max_value : $this->value;
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
