<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu jawaban: pilihan (option_id), angka (value_numeric), atau teks (value_text).
 */
class SubmissionValue extends Model
{
    protected $fillable = [
        'submission_entry_id', 'emission_field_id',
        'emission_field_option_id', 'value_numeric', 'value_text',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'float',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(SubmissionEntry::class, 'submission_entry_id');
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
