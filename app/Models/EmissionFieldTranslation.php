<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmissionFieldTranslation extends Model
{
    protected $fillable = [
        'emission_field_id', 'locale', 'label', 'summary_label', 'helper_text',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(EmissionField::class, 'emission_field_id');
    }
}
