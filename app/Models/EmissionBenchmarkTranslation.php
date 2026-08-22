<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmissionBenchmarkTranslation extends Model
{
    protected $fillable = ['emission_benchmark_id', 'locale', 'label'];

    public function benchmark(): BelongsTo
    {
        return $this->belongsTo(EmissionBenchmark::class, 'emission_benchmark_id');
    }
}
