<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Satu sesi pengisian kalkulator. Bisa berstatus draft tanpa lead,
 * karena form identitas baru muncul di langkah terakhir.
 */
class Submission extends Model
{
    protected $fillable = [
        'uuid', 'lead_id', 'locale', 'status', 'current_step',
        'completed_at', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $submission) {
            $submission->uuid ??= (string) Str::uuid();
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Leads::class, 'lead_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(SubmissionValue::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(SubmissionResult::class);
    }

    public function categoryResults(): HasMany
    {
        return $this->hasMany(SubmissionCategoryResult::class);
    }

    /** Jawaban milik satu kategori. */
    public function valuesFor(EmissionCategory $category): Collection
    {
        return $this->values->where('emission_category_id', $category->id);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
