<?php

namespace App\Services;

use App\Models\EmissionCategory;
use App\Models\EmissionFactor;
use App\Models\EmissionFieldOption;
use App\Models\ResultTier;
use App\Models\Submission;
use App\Models\SubmissionResult;
use App\Models\SubmissionValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menghitung skor poin dan emisi tahunan dari jawaban sebuah submission.
 *
 * Skor mengikuti dokumen "Skoring Kalkulator Karbon" (behavior-based scoring):
 *
 *   skor kategori = (jumlah `points` jawaban) x (hasil kali `score_multiplier`
 *                   jawaban), dibulatkan, lalu dibatasi `max_points` kategori
 *   skor total    = jumlah skor kategori, dibatasi 0-100
 *
 * Contoh: Transportasi = skor kendaraan x pengali jarak, maksimum 40.
 * Skor kategori sengaja tidak dibatasi di bawah nol — poin negatif Konsumsi &
 * Sampah adalah insentif yang memang mengurangi total. Skor total inilah yang
 * tampil di gauge "Skor Kamu" dan menentukan tier.
 *
 * Emisi dihitung terpisah per kategori sesuai `calculator_key`-nya.
 */
class CarbonCalculator
{
    public const MAX_SCORE = 100;

    private const DAYS_PER_YEAR = 365;

    private const MONTHS_PER_YEAR = 12;

    public function __construct(private readonly EmissionEstimator $estimator) {}

    /**
     * Skor satu kategori dari jawaban-jawabannya.
     *
     * Jawaban boleh berupa SubmissionValue (hasil yang dibekukan) atau
     * EmissionFieldOption (pilihan di layar) — keduanya membawa `points` dan
     * `score_multiplier`, jadi gauge wizard dan hasil akhir memakai rumus
     * yang sama persis. Jawaban yang belum lengkap tetap dihitung: kendaraan
     * tanpa jarak memakai pengali 1, jarak tanpa kendaraan bernilai 0.
     *
     * @param  Collection<int, SubmissionValue|EmissionFieldOption>  $answers
     */
    public function categoryScore(EmissionCategory $category, Collection $answers): int
    {
        $multiplier = $answers->reduce(
            fn (float $carry, $answer) => $carry * ($answer->score_multiplier ?? 1.0),
            1.0
        );

        $score = (int) round($answers->sum('points') * $multiplier);

        return $category->max_points > 0 ? min($score, $category->max_points) : $score;
    }

    /**
     * Skor berjalan dari opsi yang sedang dipilih di layar. Dipakai wizard
     * agar gauge langsung bergerak setiap kali user memilih opsi.
     *
     * @param  Collection<int, EmissionCategory>  $categories  dengan relasi fields
     * @param  Collection<int, EmissionFieldOption>  $optionsByField  id field => opsi
     */
    public function scoreForOptions(Collection $categories, Collection $optionsByField): int
    {
        return $this->total($categories->map(fn (EmissionCategory $category) => $this->categoryScore(
            $category,
            $optionsByField->only($category->fields->pluck('id')->all())
        )));
    }

    /** @param  Collection<int, int>|array<int, int>  $categoryScores */
    public function total(Collection|array $categoryScores): int
    {
        return max(0, min(self::MAX_SCORE, (int) collect($categoryScores)->sum()));
    }

    /**
     * Emisi tahunan satu kategori dalam kg CO2e.
     */
    public function emissionForCategory(Submission $submission, EmissionCategory $category): float
    {
        $values = $submission->values->where('emission_category_id', $category->id);

        if ($values->isEmpty()) {
            return 0.0;
        }

        return match ($category->calculator_key) {
            'factor_basis' => $this->factorBasis($category, $values),
            'direct_sum' => $this->directSum($values),
            default => 0.0,
        };
    }

    /**
     * Faktor emisi dikalikan jumlah basis dari opsi terpilih.
     *
     * Kunci faktornya diambil dari opsi pada field ber-flag is_factor_key
     * (Transportasi). Kategori tanpa field itu memakai faktor tunggal dengan
     * factor_key kosong (Listrik Rumah: faktor grid PLN).
     */
    private function factorBasis(EmissionCategory $category, Collection $values): float
    {
        $factorKey = $values
            ->first(fn ($value) => $value->field?->is_factor_key && $value->option?->factor_key)
            ?->option?->factor_key ?? '';

        $factor = EmissionFactor::query()
            ->where('emission_category_id', $category->id)
            ->where('factor_key', $factorKey)
            ->where('is_active', true)
            ->first();

        if (! $factor) {
            return 0.0;
        }

        $basis = (float) $values
            ->filter(fn ($value) => $value->field?->is_basis)
            ->sum(fn ($value) => $value->value_numeric ?? 0);

        return $basis * (float) $factor->value * $this->annualise($factor->basis_unit);
    }

    /** Penjumlahan emisi tahunan yang sudah melekat di tiap opsi. */
    private function directSum(Collection $values): float
    {
        return (float) $values->sum(fn ($value) => $value->kg_co2e_year ?? 0);
    }

    /**
     * Pengali agar basis selalu menjadi per tahun.
     * "km/day" -> 365, "kg/month" -> 12, "kwh/year" -> 1.
     */
    private function annualise(?string $basisUnit): int
    {
        return match (true) {
            $basisUnit === null => 1,
            str_ends_with($basisUnit, '/day') => self::DAYS_PER_YEAR,
            str_ends_with($basisUnit, '/month') => self::MONTHS_PER_YEAR,
            default => 1,
        };
    }

    /**
     * Membekukan hasil akhir: total emisi, skor, tier, dan rincian per
     * kategori. Dipanggil sekali saat submission diselesaikan.
     *
     * Dua angka emisi disimpan berdampingan: `raw_kg_co2e_year` murni hasil
     * faktor emisi, sedangkan `kg_co2e_year` adalah angka yang ditayangkan —
     * keduanya bisa berbeda karena halaman hasil mengikuti rentang tier
     * (lihat App\Services\EmissionEstimator).
     */
    public function finalise(Submission $submission): SubmissionResult
    {
        $submission->loadMissing(['values.field', 'values.option']);

        $categories = EmissionCategory::query()->active()->ordered()->get();

        $scores = $categories->mapWithKeys(fn (EmissionCategory $category) => [
            $category->id => $this->categoryScore(
                $category,
                $submission->values->where('emission_category_id', $category->id)
            ),
        ]);

        $rawKg = $categories->mapWithKeys(fn (EmissionCategory $category) => [
            $category->id => $this->emissionForCategory($submission, $category),
        ])->all();

        $score = $this->total($scores);
        $tier = ResultTier::forScore($score);

        $estimate = $this->estimator->estimate($score, $tier, $categories, $rawKg);
        $totalKg = (float) $estimate['total'];

        foreach ($categories as $category) {
            $kg = (float) ($estimate['per_category'][$category->id] ?? 0);

            $submission->categoryResults()->updateOrCreate(
                ['emission_category_id' => $category->id],
                [
                    'score' => $scores[$category->id],
                    'kg_co2e_year' => $kg,
                    'raw_kg_co2e_year' => $rawKg[$category->id] ?? 0,
                    'percentage' => $totalKg > 0 ? round($kg / $totalKg * 100, 4) : 0,
                ]
            );
        }

        $result = $submission->result()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'result_tier_id' => $tier?->id,
                'score' => $score,
                'total_kg_co2e_year' => $totalKg,
                'raw_kg_co2e_year' => array_sum($rawKg),
                'computed_at' => Carbon::now(),
            ]
        );

        $submission->forceFill([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ])->save();

        return $result;
    }
}
