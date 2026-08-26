<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Angka kartu per kategori di halaman hasil.
 */
class SubmissionCategoryResult extends Model
{
    protected $fillable = [
        'submission_id', 'emission_category_id',
        'score', 'kg_co2e_year', 'percentage',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'kg_co2e_year' => 'float',
            'percentage' => 'float',
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

    public function tonCo2eYear(): float
    {
        return $this->kg_co2e_year / 1000;
    }
}
