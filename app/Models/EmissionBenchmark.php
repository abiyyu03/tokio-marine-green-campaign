<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pembanding di sidebar halaman hasil (Global / ASEAN / Indonesia).
 */
class EmissionBenchmark extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionBenchmarkTranslation::class;

    protected $fillable = [
        'code', 'value', 'unit', 'source', 'reference_year', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'reference_year' => 'integer',
            'is_active' => 'boolean',
        ];
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
