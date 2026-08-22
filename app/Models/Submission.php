<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'uuid', 'lead_id', 'locale', 'status',
        'completed_at', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
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

    public function entries(): HasMany
    {
        return $this->hasMany(SubmissionEntry::class)->orderBy('entry_index');
    }

    public function result(): HasOne
    {
        return $this->hasOne(SubmissionResult::class);
    }

    public function categoryResults(): HasMany
    {
        return $this->hasMany(SubmissionCategoryResult::class);
    }

    /** Entry milik satu kategori, mis. semua kendaraan yang ditambahkan user. */
    public function entriesFor(EmissionCategory $category): HasMany
    {
        return $this->entries()->where('emission_category_id', $category->id);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
