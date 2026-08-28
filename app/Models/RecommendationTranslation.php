<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationTranslation extends Model
{
    protected $fillable = ['recommendation_id', 'locale', 'body'];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(Recommendation::class, 'recommendation_id');
    }
}
