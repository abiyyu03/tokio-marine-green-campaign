<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmissionEquivalenceTranslation extends Model
{
    protected $fillable = ['emission_equivalence_id', 'locale', 'template'];

    public function equivalence(): BelongsTo
    {
        return $this->belongsTo(EmissionEquivalence::class, 'emission_equivalence_id');
    }
}
