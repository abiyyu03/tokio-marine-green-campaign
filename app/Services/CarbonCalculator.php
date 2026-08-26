<?php

namespace App\Services;

use App\Models\EmissionCategory;
use App\Models\EmissionFactor;
use App\Models\ResultTier;
use App\Models\Submission;
use App\Models\SubmissionResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Menghitung skor poin dan emisi tahunan dari jawaban sebuah submission.
 *
 * Skor adalah penjumlahan sederhana `points` tiap jawaban, dibatasi 0-100 —
 * inilah angka yang tampil di gauge "Skor Kamu" dan yang menentukan tier.
 * Emisi dihitung terpisah per kategori sesuai `calculator_key`-nya.
 */
class CarbonCalculator
{
    public const MAX_SCORE = 100;

    private const DAYS_PER_YEAR = 365;

    private const MONTHS_PER_YEAR = 12;

    /**
     * Skor berjalan untuk gauge di sidebar wizard. Menerima jawaban yang
     * belum lengkap, sehingga bisa dipanggil setiap kali user memilih opsi.
     */
    public function score(Submission $submission): int
    {
        return $this->clamp((int) $submission->values->sum('points'));
    }

    /**
     * Skor dari opsi yang sedang dipilih di layar, sebelum jawaban ditulis
     * ulang dari database. Dipakai wizard agar gauge langsung bergerak.
     *
     * @param  Collection<int, \App\Models\EmissionFieldOption>  $options
     */
    public function scoreForOptions(Collection $options): int
    {
        return $this->clamp((int) $options->sum('points'));
    }

    private function clamp(int $points): int
    {
        return max(0, min(self::MAX_SCORE, $points));
    }

    /** Skor parsial satu kategori, untuk kartu hasil per kategori. */
    public function scoreForCategory(Submission $submission, EmissionCategory $category): int
    {
        return (int) $submission->values
            ->where('emission_category_id', $category->id)
            ->sum('points');
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
     */
    public function finalise(Submission $submission): SubmissionResult
    {
        $submission->loadMissing(['values.field', 'values.option']);

        $categories = EmissionCategory::query()->active()->ordered()->get();

        $perCategory = $categories->mapWithKeys(fn (EmissionCategory $category) => [
            $category->id => [
                'score' => $this->scoreForCategory($submission, $category),
                'kg' => $this->emissionForCategory($submission, $category),
            ],
        ]);

        $totalKg = (float) $perCategory->sum('kg');
        $score = $this->score($submission);
        $tier = ResultTier::forScore($score);

        foreach ($perCategory as $categoryId => $row) {
            $submission->categoryResults()->updateOrCreate(
                ['emission_category_id' => $categoryId],
                [
                    'score' => $row['score'],
                    'kg_co2e_year' => $row['kg'],
                    'percentage' => $totalKg > 0 ? round($row['kg'] / $totalKg * 100, 4) : 0,
                ]
            );
        }

        $result = $submission->result()->updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'result_tier_id' => $tier?->id,
                'score' => $score,
                'total_kg_co2e_year' => $totalKg,
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
