<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Faktor emisi, dicari lewat kombinasi kode opsi (factor_key).
 */
class EmissionFactor extends Model
{
    protected $fillable = [
        'emission_category_id', 'factor_key', 'value', 'unit', 'basis_unit',
        'source', 'reference_year', 'valid_from', 'valid_to', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'reference_year' => 'integer',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmissionCategory::class, 'emission_category_id');
    }

    /**
     * Bentuk factor_key dari daftar kode opsi.
     * Urutan harus mengikuti sort_order field ber-flag is_factor_key.
     */
    public static function makeKey(array $optionCodes): string
    {
        return implode('|', array_filter($optionCodes, fn ($c) => $c !== null && $c !== ''));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForKey(Builder $query, int $categoryId, string $factorKey): Builder
    {
        return $query->where('emission_category_id', $categoryId)
            ->where('factor_key', $factorKey);
    }
}
