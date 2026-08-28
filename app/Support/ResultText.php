<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Merender template teks dari database untuk halaman hasil.
 *
 * Template ditulis oleh tim konten, bukan developer, sehingga sengaja hanya
 * mendukung dua hal: placeholder ":kunci" dan penanda tebal "**...**".
 * Seluruh isinya di-escape lebih dulu supaya aman dirender sebagai HTML.
 */
class ResultText
{
    /**
     * @param  array<string, string|int|float>  $replacements
     */
    public static function render(?string $template, array $replacements = []): HtmlString
    {
        if (! $template) {
            return new HtmlString('');
        }

        $text = e($template);

        foreach ($replacements as $key => $value) {
            $text = str_replace(':'.$key, e((string) $value), $text);
        }

        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);

        return new HtmlString($text);
    }

    /**
     * Angka dengan pemisah sesuai locale: 1.600 (id) vs 1,600 (en).
     */
    public static function number(float $value, int $decimals = 0, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'en'
            ? number_format($value, $decimals, '.', ',')
            : number_format($value, $decimals, ',', '.');
    }

    /**
     * "3,8" dari 3800 kg. Dipakai untuk semua tampilan "± X Ton CO2 / Tahun".
     */
    public static function ton(float $kgCo2e, int $decimals = 2, ?string $locale = null): string
    {
        return self::number($kgCo2e / 1000, $decimals, $locale);
    }

    /**
     * Angka tanpa nol di belakang koma: 2,0 -> "2" tapi 2,5 tetap "2,5".
     * Dipakai pada rentang pembanding "(2 - 2,5 Ton CO2/tahun)".
     */
    public static function compact(float $value, int $decimals = 1, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $separator = $locale === 'en' ? '.' : ',';

        $text = self::number($value, $decimals, $locale);

        if (! str_contains($text, $separator)) {
            return $text;
        }

        return rtrim(rtrim($text, '0'), $separator);
    }
}
