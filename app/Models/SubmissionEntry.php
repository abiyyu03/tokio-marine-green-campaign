<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu kartu isian dalam sebuah kategori. `entry_index` memisahkan
 * kendaraan ke-1 dari kendaraan ke-2 pada fitur "Tambah Kendaraan Lain".
 */
class SubmissionEntry extends Model
{
    protected $fillable = [
        'submission_id',
        'emission_category_id',
        'entry_index',
        'emission_factor_id',
        'kg_co2e_year',
        'calc_meta',
    ];

    protected function casts(): array
    {
        return [
            'entry_index' => 'integer',
            'kg_co2e_year' => 'float',
            'calc_meta' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmissionCategory::class, 'emission_category_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(SubmissionValue::class);
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(EmissionFactor::class, 'emission_factor_id');
    }

    /**
     * Kunci pencarian faktor emisi untuk entry ini, mis. "mobil|bensin".
     * Butuh relasi values.field dan values.option sudah dimuat.
     */
    public function factorKey(): string
    {
        return EmissionFactor::makeKey(
            $this->values
                ->filter(fn(SubmissionValue $v) => (bool) $v->field?->is_factor_key)
                ->sortBy(fn(SubmissionValue $v) => $v->field->sort_order)
                ->map(fn(SubmissionValue $v) => $v->option?->code)
                ->values()
                ->all()
        );
    }

    /** Nilai numerik yang dikalikan faktor (field ber-flag is_basis). */
    public function basisValue(): ?float
    {
        return $this->values
            ->first(fn(SubmissionValue $v) => (bool) $v->field?->is_basis)
            ?->value_numeric;
    }
}
