<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Butir "Rekomendasi Aksi Khusus :name" di halaman hasil.
 */
class Recommendation extends Model
{
    use HasTranslations;

    protected string $translationClass = RecommendationTranslation::class;

    protected $fillable = [
        'code', 'result_tier_id', 'emission_category_id',
        'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(ResultTier::class, 'result_tier_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmissionCategory::class, 'emission_category_id');
    }

    /**
     * Rekomendasi yang berlaku untuk sebuah hasil: yang tidak menargetkan
     * apa pun (berlaku umum), plus yang cocok dengan tier dan/atau kategori
     * penyumbang emisi terbesar.
     */
    public function scopeMatching(
        Builder $query,
        ?int $tierId = null,
        ?int $topCategoryId = null
    ): Builder {
        return $query
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('result_tier_id')->orWhere('result_tier_id', $tierId))
            ->where(fn ($q) => $q->whereNull('emission_category_id')->orWhere('emission_category_id', $topCategoryId))
            ->orderBy('sort_order');
    }
}
