<?php

namespace App\Services;

use App\Models\EmissionCategory;
use App\Models\ResultTier;
use Illuminate\Support\Collection;

/**
 * Menentukan angka emisi yang DITAMPILKAN di halaman hasil.
 *
 * Ada dua model yang sah dan keduanya disediakan lewat
 * config('carbon-calculator.estimation.strategy'):
 *
 * - "tier_band" (default, mengikuti dokumen logic Result Page)
 *   Total emisi diturunkan dari skor: skor menentukan tier, tier menentukan
 *   rentang ton (1,5-2 | 2-3 | 3-5), lalu skor dipetakan linier ke dalam
 *   rentang itu. Rincian per sektor memakai porsi tetap yang juga ditetapkan
 *   dokumen: Transportasi 40%, Listrik Rumah 35%, Konsumsi & Sampah 25%.
 *   Konsekuensinya angka di kartu total, kartu sektor, blok "setara dengan",
 *   dan badge tier tidak akan pernah saling bertentangan.
 *
 * - "factor"
 *   Total emisi = hasil hitung faktor emisi per kategori apa adanya
 *   (App\Services\CarbonCalculator). Lebih jujur secara teknis, tapi hasilnya
 *   bisa jatuh di luar rentang yang dijanjikan badge tier selama faktor emisi
 *   di seeder masih placeholder dan basisnya rumah tangga, bukan per kapita.
 *
 * Apa pun strateginya, angka faktor tetap dihitung dan disimpan di kolom
 * `raw_kg_co2e_year` supaya bisa dibandingkan nanti.
 */
class EmissionEstimator
{
    public const STRATEGY_TIER_BAND = 'tier_band';

    public const STRATEGY_FACTOR = 'factor';

    private const KG_PER_TON = 1000;

    public function __construct(private readonly ?string $strategy = null) {}

    private function strategy(): string
    {
        return $this->strategy
            ?? config('carbon-calculator.estimation.strategy', self::STRATEGY_TIER_BAND);
    }

    /**
     * @param  Collection<int, EmissionCategory>  $categories
     * @param  array<int, float>  $rawKgByCategory  id kategori => kg CO2e/tahun dari faktor
     * @return array{total: float, per_category: array<int, float>}  dalam kg CO2e/tahun
     */
    public function estimate(
        int $score,
        ?ResultTier $tier,
        Collection $categories,
        array $rawKgByCategory
    ): array {
        $tonFromTier = $this->strategy() === self::STRATEGY_TIER_BAND
            ? $tier?->estimatedTonForScore($score)
            : null;

        // Tanpa tier atau tanpa rentang ton, satu-satunya angka yang tersedia
        // adalah hasil faktor emisi.
        if ($tonFromTier === null) {
            return [
                'total' => (float) array_sum($rawKgByCategory),
                'per_category' => array_map(static fn ($kg) => (float) $kg, $rawKgByCategory),
            ];
        }

        $total = $tonFromTier * self::KG_PER_TON;
        $shares = $this->shares($categories);

        $perCategory = [];

        foreach ($categories as $category) {
            $perCategory[$category->id] = $total * ($shares[$category->id] ?? 0.0);
        }

        return ['total' => $total, 'per_category' => $perCategory];
    }

    /**
     * Porsi emisi tiap kategori, dinormalisasi agar berjumlah 1.
     *
     * Sumber utamanya kolom `emission_share`; kategori yang belum diisi
     * memakai proporsi `max_points` sebagai cadangan supaya kategori baru
     * tetap kebagian tanpa harus mengubah kode.
     *
     * @param  Collection<int, EmissionCategory>  $categories
     * @return array<int, float>
     */
    private function shares(Collection $categories): array
    {
        $weights = $categories->mapWithKeys(fn (EmissionCategory $category) => [
            $category->id => (float) ($category->emission_share ?? $category->max_points),
        ]);

        $total = (float) $weights->sum();

        if ($total <= 0) {
            $count = max(1, $categories->count());

            return $categories->mapWithKeys(fn ($category) => [$category->id => 1 / $count])->all();
        }

        return $weights->map(fn (float $weight) => $weight / $total)->all();
    }
}
