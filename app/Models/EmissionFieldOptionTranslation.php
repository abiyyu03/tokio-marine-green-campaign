<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmissionFieldOptionTranslation extends Model
{
    protected $fillable = ['emission_field_option_id', 'locale', 'label', 'description'];

    public function option(): BelongsTo
    {
        return $this->belongsTo(EmissionFieldOption::class, 'emission_field_option_id');
    }
}
