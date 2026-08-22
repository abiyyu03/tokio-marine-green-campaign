<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu langkah wizard kalkulator.
 */
class EmissionCategory extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionCategoryTranslation::class;

    protected $fillable = [
        'code', 'slug', 'calculator_key', 'icon',
        'is_repeatable', 'max_entries', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_repeatable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(EmissionField::class)->orderBy('sort_order');
    }

    public function factors(): HasMany
    {
        return $this->hasMany(EmissionFactor::class);
    }

    /** Field yang nilainya ikut membentuk kunci pencarian faktor emisi. */
    public function factorKeyFields(): HasMany
    {
        return $this->fields()->where('is_factor_key', true);
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
