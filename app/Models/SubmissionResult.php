<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil akhir yang dibekukan agar halaman hasil tidak berubah
 * saat faktor emisi diperbarui.
 */
class SubmissionResult extends Model
{
    protected $fillable = [
        'submission_id', 'result_tier_id', 'household_size',
        'total_kg_co2e_year', 'per_capita_ton_co2e_year', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'household_size' => 'integer',
            'total_kg_co2e_year' => 'float',
            'per_capita_ton_co2e_year' => 'float',
            'computed_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ResultTier::class, 'result_tier_id');
    }
}
