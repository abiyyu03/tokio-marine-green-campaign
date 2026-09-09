<?php

namespace Tests\Unit;

use App\Models\EmissionCategory;
use App\Models\EmissionEquivalence;
use App\Models\ResultTier;
use App\Services\EmissionEstimator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mengunci angka-angka pada "Dokumentasi Logic & UI Copy Result Page".
 *
 * Sengaja tidak menyentuh database: seluruh data referensi dibuat sebagai
 * model tanpa disimpan, jadi tes ini tetap jalan di mesin mana pun dan yang
 * diuji betul-betul rumusnya, bukan isi seeder.
 */
class EmissionEstimatorTest extends TestCase
{
    private function tiers(): Collection
    {
        return collect([
            $this->tier(1, 'ringan', 0, 30, 1.5, 2.0),
            $this->tier(2, 'sedang', 31, 60, 2.0, 3.0),
            $this->tier(3, 'tinggi', 61, 100, 3.0, 5.0),
        ]);
    }

    private function tier(int $id, string $code, int $min, int $max, float $tonMin, float $tonMax): ResultTier
    {
        $tier = new ResultTier([
            'code' => $code,
            'min_score' => $min,
            'max_score' => $max,
            'approx_min_ton_co2e' => $tonMin,
            'approx_max_ton_co2e' => $tonMax,
        ]);
        $tier->id = $id;

        return $tier;
    }

    private function categories(): Collection
    {
        return collect([
            ['transportasi', 35, 0.40],
            ['listrik_rumah', 30, 0.35],
            ['konsumsi_sampah', 35, 0.25],
        ])->map(function (array $row, int $index) {
            $category = new EmissionCategory([
                'code' => $row[0],
                'max_points' => $row[1],
                'emission_share' => $row[2],
            ]);
            $category->id = $index + 1;

            return $category;
        });
    }

    private function tierFor(int $score): ResultTier
    {
        return $this->tiers()->first(
            fn (ResultTier $tier) => $score >= $tier->min_score && $score <= $tier->max_score
        );
    }

    /** Hasil faktor emisi, sengaja jauh dari rentang tier. */
    private function rawKg(): array
    {
        return [1 => 4555.0, 2 => 5809.0, 3 => 1010.0];
    }

    /**
     * Bagian 1 & 2 dokumen: skor menentukan total emisi, total dibagi ke
     * sektor dengan porsi 40% / 35% / 25%.
     *
     * @return array<string, array{int, float, float, float, float}>
     */
    public static function scoreBoundaries(): array
    {
        return [
            'batas bawah ringan' => [0, 1.5, 0.60, 0.525, 0.375],
            'batas atas ringan' => [30, 2.0, 0.80, 0.700, 0.500],
            'batas bawah sedang' => [31, 2.0, 0.80, 0.700, 0.500],
            'batas atas sedang' => [60, 3.0, 1.20, 1.050, 0.750],
            'batas bawah tinggi' => [61, 3.0, 1.20, 1.050, 0.750],
            'batas atas tinggi' => [100, 5.0, 2.00, 1.750, 1.250],
        ];
    }

    #[DataProvider('scoreBoundaries')]
    public function test_it_splits_the_tier_band_across_sectors(
        int $score,
        float $total,
        float $transport,
        float $electricity,
        float $consumption
    ): void {
        $estimate = (new EmissionEstimator(EmissionEstimator::STRATEGY_TIER_BAND))
            ->estimate($score, $this->tierFor($score), $this->categories(), $this->rawKg());

        $this->assertEqualsWithDelta($total, $estimate['total'] / 1000, 0.0001);
        $this->assertEqualsWithDelta($transport, $estimate['per_category'][1] / 1000, 0.0001);
        $this->assertEqualsWithDelta($electricity, $estimate['per_category'][2] / 1000, 0.0001);
        $this->assertEqualsWithDelta($consumption, $estimate['per_category'][3] / 1000, 0.0001);
        $this->assertEqualsWithDelta($total, array_sum($estimate['per_category']) / 1000, 0.0001);
    }

    public function test_the_estimate_never_leaves_the_band_its_badge_promises(): void
    {
        $estimator = new EmissionEstimator(EmissionEstimator::STRATEGY_TIER_BAND);
        $previous = 0.0;

        for ($score = 0; $score <= 100; $score++) {
            $tier = $this->tierFor($score);
            $ton = $estimator->estimate($score, $tier, $this->categories(), $this->rawKg())['total'] / 1000;

            $this->assertGreaterThanOrEqual($tier->approx_min_ton_co2e, round($ton, 6), "skor {$score}");
            $this->assertLessThanOrEqual($tier->approx_max_ton_co2e, round($ton, 6), "skor {$score}");
            $this->assertGreaterThanOrEqual($previous, $ton, "skor {$score} turun dari skor sebelumnya");

            $previous = $ton;
        }
    }

    public function test_the_factor_strategy_publishes_the_calculated_emissions(): void
    {
        $raw = $this->rawKg();

        $estimate = (new EmissionEstimator(EmissionEstimator::STRATEGY_FACTOR))
            ->estimate(71, $this->tierFor(71), $this->categories(), $raw);

        $this->assertSame(array_sum($raw), $estimate['total']);
        $this->assertSame($raw, $estimate['per_category']);
    }

    public function test_it_falls_back_to_the_calculated_emissions_without_a_tier(): void
    {
        $raw = $this->rawKg();

        $estimate = (new EmissionEstimator(EmissionEstimator::STRATEGY_TIER_BAND))
            ->estimate(50, null, $this->categories(), $raw);

        $this->assertSame(array_sum($raw), $estimate['total']);
    }

    /** Kategori tanpa emission_share membagi porsi menurut jatah poinnya. */
    public function test_it_falls_back_to_the_point_budget_when_no_share_is_set(): void
    {
        $categories = $this->categories()->each(fn (EmissionCategory $c) => $c->emission_share = null);

        $estimate = (new EmissionEstimator(EmissionEstimator::STRATEGY_TIER_BAND))
            ->estimate(100, $this->tierFor(100), $categories, $this->rawKg());

        // Jatah poin 35 / 30 / 35 dari 100 poin, dikali total 5 ton.
        $this->assertEqualsWithDelta(1.75, $estimate['per_category'][1] / 1000, 0.0001);
        $this->assertEqualsWithDelta(1.50, $estimate['per_category'][2] / 1000, 0.0001);
        $this->assertEqualsWithDelta(1.75, $estimate['per_category'][3] / 1000, 0.0001);
    }

    /**
     * Bagian 3 dokumen: 1 Ton CO2e = 420 liter bensin = 4,7 penerbangan
     * domestik = 50 pohon dewasa.
     *
     * @return array<string, array{float, float, float}>
     */
    public static function equivalenceCoefficients(): array
    {
        return [
            'bensin' => [420.0, 1.5, 630.0],
            'penerbangan' => [4.7, 1.5, 7.0],
            'pohon' => [50.0, 1.5, 75.0],
            'bensin 5 ton' => [420.0, 5.0, 2100.0],
            'pohon 5 ton' => [50.0, 5.0, 250.0],
        ];
    }

    #[DataProvider('equivalenceCoefficients')]
    public function test_it_converts_emissions_into_relatable_units(
        float $unitsPerTon,
        float $ton,
        float $expectedUnits
    ): void {
        $equivalence = new EmissionEquivalence([
            'kg_co2e_per_unit' => round(1000 / $unitsPerTon, 6),
            'decimals' => 0,
        ]);

        $this->assertSame($expectedUnits, $equivalence->unitsFor($ton * 1000));
    }
}
