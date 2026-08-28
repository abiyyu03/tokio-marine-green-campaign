<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityImpactTranslation extends Model
{
    protected $fillable = ['community_impact_id', 'locale', 'template'];

    public function impact(): BelongsTo
    {
        return $this->belongsTo(CommunityImpact::class, 'community_impact_id');
    }
}
