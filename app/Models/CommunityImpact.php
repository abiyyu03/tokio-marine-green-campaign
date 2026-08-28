<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris blok "Dampak Kolektif Komunitas".
 */
class CommunityImpact extends Model
{
    use HasTranslations;

    protected string $translationClass = CommunityImpactTranslation::class;

    protected $fillable = [
        'code', 'value', 'unit', 'reference_year', 'sort_order', 'is_active',
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
