<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian emisi per kategori: sumber angka pie chart dan baris
 * "Total Emisi" pada tiap tabel di halaman hasil.
 */
class SubmissionCategoryResult extends Model
{
    protected $fillable = [
        'submission_id', 'emission_category_id', 'kg_co2e_year', 'percentage',
    ];

    protected function casts(): array
    {
        return [
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
}
