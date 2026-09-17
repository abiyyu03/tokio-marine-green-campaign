<?php

namespace Tests\Unit;

use App\Models\EmissionCategory;
use App\Models\EmissionField;
use App\Models\EmissionFieldOption;
use App\Models\SubmissionValue;
use App\Services\CarbonCalculator;
use App\Services\EmissionEstimator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Mengunci rumus pada dokumen "Skoring Kalkulator Karbon".
 *
 * Sama seperti EmissionEstimatorTest, tidak menyentuh database: poin ditulis
 * ulang di sini sebagai model tanpa disimpan, jadi yang diuji rumusnya.
 */
class CarbonCalculatorScoreTest extends TestCase
{
    private function calculator(): CarbonCalculator
    {
        return new CarbonCalculator(new EmissionEstimator(EmissionEstimator::STRATEGY_TIER_BAND));
    }

    private function category(int $maxPoints): EmissionCategory
    {
        return new EmissionCategory(['max_points' => $maxPoints]);
    }

    /** @param  array<int, array{0: int, 1?: float|null}>  $answers  [poin, pengali] */
    private function values(array $answers): Collection
    {
        return collect($answers)->map(fn (array $answer) => new SubmissionValue([
            'points' => $answer[0],
            'score_multiplier' => $answer[1] ?? null,
        ]));
    }

    /**
     * Skor Transport = skor kendaraan x pengali jarak, maksimum 40.
     *
     * @return array<string, array{int, float, int}>
     */
    public static function transportCases(): array
    {
        return [
            'transport umum < 10 km' => [6, 0.5, 3],
            'mobil bensin 10-25 km' => [25, 1.0, 25],
            'motor bensin 25-50 km dibulatkan' => [7, 1.5, 11],
            'mobil listrik < 10 km dibulatkan' => [13, 0.5, 7],
            'mobil bensin 25-50 km dibulatkan' => [25, 1.5, 38],
            'mobil bensin > 50 km terpotong' => [25, 2.0, 40],
            'campur > 50 km' => [11, 2.0, 22],
        ];
    }

    #[DataProvider('transportCases')]
    public function test_distance_multiplies_the_vehicle_score(int $vehicle, float $multiplier, int $expected): void
    {
        $score = $this->calculator()->categoryScore(
            $this->category(40),
            $this->values([[$vehicle], [0, $multiplier]])
        );

        $this->assertSame($expected, $score);
    }

    public function test_a_vehicle_without_distance_counts_at_face_value(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(25, $calculator->categoryScore($this->category(40), $this->values([[25]])));
        $this->assertSame(0, $calculator->categoryScore($this->category(40), $this->values([[0, 2.0]])));
    }

    public function test_electricity_is_capped_at_its_budget(): void
    {
        // AC >= 3 unit 18 + kulkas non-inverter 8 + >= 6.600 VA 15 = 41.
        $score = $this->calculator()->categoryScore($this->category(35), $this->values([[18], [8], [15]]));

        $this->assertSame(35, $score);
    }

    public function test_consumption_keeps_its_negative_incentives(): void
    {
        $calculator = $this->calculator();

        // Paling boros: 10 + 5 + 8 + 5 + 15 + 10 = 53 -> 25.
        $this->assertSame(25, $calculator->categoryScore($this->category(25), $this->values([[10], [5], [8], [5], [15], [10]])));

        // Paling hemat: 3 - 5 - 8 - 5 + 5 + 5 = -5, tidak dipotong ke nol.
        $this->assertSame(-5, $calculator->categoryScore($this->category(25), $this->values([[3], [-5], [-8], [-5], [5], [5]])));
    }

    public function test_the_total_stays_between_zero_and_one_hundred(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(100, $calculator->total([40, 35, 25]));
        $this->assertSame(0, $calculator->total([2, 0, -5]));
    }

    /**
     * Simulasi "CASE 1 — Dampak Ringan" memakai poin dari tabel dokumen.
     *
     * Dokumen menulis total 8 karena daya < 900 VA dihitung 5 di simulasinya,
     * padahal tabel Daya Listrik memberi 3. Yang dikunci di sini tabelnya:
     * 3 + 8 + (-5) = 6.
     */
    public function test_case_one_from_the_scoring_document(): void
    {
        $calculator = $this->calculator();

        $transport = $calculator->categoryScore($this->category(40), $this->values([[6], [0, 0.5]]));
        $electricity = $calculator->categoryScore($this->category(35), $this->values([[0], [5], [3]]));
        $consumption = $calculator->categoryScore($this->category(25), $this->values([[3], [-5], [-8], [-5], [5], [5]]));

        $this->assertSame([3, 8, -5], [$transport, $electricity, $consumption]);
        $this->assertSame(6, $calculator->total([$transport, $electricity, $consumption]));
    }

    /** Gauge wizard memakai opsi di layar, bukan jawaban tersimpan — hasilnya harus sama. */
    public function test_the_live_gauge_matches_the_frozen_formula(): void
    {
        $transport = $this->category(40)->setRelation('fields', collect([
            $this->field(1),
            $this->field(2),
        ]));
        $consumption = $this->category(25)->setRelation('fields', collect([
            $this->field(3),
        ]));

        $selected = collect([
            1 => new EmissionFieldOption(['points' => 25]),
            2 => new EmissionFieldOption(['points' => 0, 'score_multiplier' => 1.5]),
            3 => new EmissionFieldOption(['points' => -8]),
        ]);

        // (25 x 1,5 = 37,5 -> 38) + (-8) = 30. Pengali jarak tidak boleh
        // ikut mengalikan poin kategori lain.
        $this->assertSame(30, $this->calculator()->scoreForOptions(collect([$transport, $consumption]), $selected));
    }

    private function field(int $id): EmissionField
    {
        $field = new EmissionField;
        $field->id = $id;

        return $field;
    }
}
