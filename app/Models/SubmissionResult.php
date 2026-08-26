<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hasil akhir yang dibekukan saat submission selesai.
 */
class SubmissionResult extends Model
{
    protected $fillable = [
        'submission_id', 'result_tier_id', 'score',
        'total_kg_co2e_year', 'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total_kg_co2e_year' => 'float',
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

    public function totalTonCo2eYear(): float
    {
        return $this->total_kg_co2e_year / 1000;
    }
}
