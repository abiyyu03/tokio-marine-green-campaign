<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Pola i18n bersama untuk seluruh master data.
 *
 * Teks dipisah ke tabel `<entitas>_translations` agar baris induk punya id yang
 * stabil lintas bahasa. Ini memperbaiki kelemahan skema lama yang menaruh kolom
 * `lang` langsung di tabel utama: di sana versi ID dan EN menjadi dua baris
 * dengan id berbeda, sehingga jawaban user tidak bisa diagregasi lintas bahasa.
 *
 * Model pemakai cukup mendeklarasikan:
 *   protected string $translationClass = FooTranslation::class;
 */
trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->translationClass, $this->translationForeignKey());
    }

    /**
     * Terjemahan untuk locale yang diminta, mundur ke fallback_locale,
     * lalu ke baris pertama yang ada.
     */
    public function translation(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->firstWhere('locale', config('app.fallback_locale'))
            ?? $this->translations->first();
    }

    /**
     * Ambil satu atribut terjemahan, mis. $category->tr('title').
     */
    public function tr(string $attribute, ?string $locale = null): ?string
    {
        return $this->translation($locale)?->{$attribute};
    }

    public function scopeWithTranslation(Builder $query, ?string $locale = null): Builder
    {
        $locales = array_values(array_unique(array_filter([
            $locale ?? app()->getLocale(),
            config('app.fallback_locale'),
        ])));

        return $query->with([
            'translations' => fn ($q) => $q->whereIn('locale', $locales),
        ]);
    }

    protected function translationForeignKey(): string
    {
        return Str::snake(class_basename(static::class)).'_id';
    }
}
