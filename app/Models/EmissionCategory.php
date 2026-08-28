<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu langkah wizard kalkulator (Transportasi, Listrik Rumah, Konsumsi & Sampah).
 */
class EmissionCategory extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionCategoryTranslation::class;

    protected $fillable = [
        'code', 'slug', 'calculator_key', 'icon', 'image_file', 'accent_color',
        'max_points', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_points' => 'integer',
            'sort_order' => 'integer',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
