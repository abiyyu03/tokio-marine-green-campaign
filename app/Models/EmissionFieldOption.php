<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pilihan sebuah field. `numeric_value` / `meta` menampung angka bawaan opsi
 * (tarif per kWh, watt alat, porsi energi bersih).
 */
class EmissionFieldOption extends Model
{
    use HasTranslations;

    protected string $translationClass = EmissionFieldOptionTranslation::class;

    protected $fillable = [
        'emission_field_id', 'code', 'image_file', 'icon',
        'numeric_value', 'numeric_unit', 'meta', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'numeric_value' => 'float',
            'meta' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(EmissionField::class, 'emission_field_id');
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
