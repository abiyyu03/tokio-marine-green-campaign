<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu input di layar.
 */
class EmissionField extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionFieldTranslation::class;

    protected $fillable = [
        'emission_category_id', 'code', 'input_type', 'display_style',
        'unit', 'unit_position', 'decimals', 'min_value', 'max_value', 'step',
        'is_required', 'is_factor_key', 'is_basis',
        'depends_on_field_id', 'depends_on_option_code',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
            'min_value' => 'float',
            'max_value' => 'float',
            'step' => 'float',
            'is_required' => 'boolean',
            'is_factor_key' => 'boolean',
            'is_basis' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmissionCategory::class, 'emission_category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(EmissionFieldOption::class)->orderBy('sort_order');
    }

    public function dependsOnField(): BelongsTo
    {
        return $this->belongsTo(self::class, 'depends_on_field_id');
    }

    public function expectsOption(): bool
    {
        return in_array($this->input_type, ['single_choice', 'multiple_choice', 'select'], true);
    }

    public function expectsNumber(): bool
    {
        return in_array($this->input_type, ['number', 'currency'], true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
