<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu pertanyaan di layar. Seluruhnya berbasis pilihan; tidak ada input
 * angka bebas pada desain saat ini.
 */
class EmissionField extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionFieldTranslation::class;

    protected $fillable = [
        'emission_category_id', 'code', 'input_type', 'display_style', 'unit',
        'is_required', 'is_factor_key', 'is_basis',
        'depends_on_field_id', 'depends_on_option_code',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
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

    public function allowsMultiple(): bool
    {
        return $this->input_type === 'multiple_choice';
    }

    /** Kartu bergambar dua kolom vs tombol teks. */
    public function isCardStyle(): bool
    {
        return $this->display_style === 'card';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
