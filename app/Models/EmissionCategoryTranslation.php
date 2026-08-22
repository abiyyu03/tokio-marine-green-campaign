<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmissionCategoryTranslation extends Model
{
    protected $fillable = [
        'emission_category_id', 'locale', 'name', 'title',
        'subtitle', 'summary_label', 'add_entry_label', 'description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmissionCategory::class, 'emission_category_id');
    }
}
