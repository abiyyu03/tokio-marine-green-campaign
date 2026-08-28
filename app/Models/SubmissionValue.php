<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu jawaban pengguna. `points` dan `kg_co2e_year` disalin dari opsi saat
 * disimpan supaya hasil yang sudah selesai tidak berubah ketika angka
 * referensi diperbarui.
 */
class SubmissionValue extends Model
{
    protected $fillable = [
        'submission_id', 'emission_category_id', 'emission_field_id',
        'emission_field_option_id', 'points', 'value_numeric',
        'kg_co2e_year', 'value_text',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'value_numeric' => 'float',
            'kg_co2e_year' => 'float',
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

    public function field(): BelongsTo
    {
        return $this->belongsTo(EmissionField::class, 'emission_field_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(EmissionFieldOption::class, 'emission_field_option_id');
    }
}
