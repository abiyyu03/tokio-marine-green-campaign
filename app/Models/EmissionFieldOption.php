<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pilihan sebuah field. Membawa poin untuk gauge skor sekaligus angka
 * untuk perhitungan emisi (basis lewat `numeric_value`, atau emisi tahunan
 * langsung lewat `kg_co2e_year`).
 */
class EmissionFieldOption extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionFieldOptionTranslation::class;

    protected $fillable = [
        'emission_field_id', 'code', 'image_file', 'icon', 'points',
        'numeric_value', 'numeric_unit', 'kg_co2e_year', 'factor_key',
        'meta', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'numeric_value' => 'float',
            'kg_co2e_year' => 'float',
            'meta' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(EmissionField::class, 'emission_field_id');
    }

    /** Label pendek untuk ringkasan sidebar, mundur ke label penuh. */
    public function summaryLabel(?string $locale = null): ?string
    {
        return $this->tr('summary_label', $locale) ?: $this->tr('label', $locale);
    }

    /**
     * Ambil satu angka dari kolom meta, mis. metaValue('tariff_per_kwh').
     * Sengaja tidak dinamai meta() agar tidak bentrok dengan atribut kolom.
     */
    public function metaValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta, $key, $default);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
