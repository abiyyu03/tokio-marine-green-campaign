<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultTierTranslation extends Model
{
    protected $fillable = [
        'result_tier_id', 'locale', 'label', 'badge_label', 'headline',
        'benchmark_note', 'description',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ResultTier::class, 'result_tier_id');
    }
}
