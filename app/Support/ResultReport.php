<?php

namespace App\Support;

use App\Models\CommunityImpact;
use App\Models\DropOffPoint;
use App\Models\EmissionBenchmark;
use App\Models\EmissionEquivalence;
use App\Models\Recommendation;
use App\Models\ResultTier;
use App\Models\Submission;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Satu hasil perhitungan, siap ditayangkan.
 *
 * Halaman hasil (layar) dan halaman laporan (cetak) menampilkan angka dan
 * kalimat yang sama persis dengan tata letak berbeda. Semua pembacaannya
 * dikumpulkan di sini supaya keduanya tidak bisa berbeda isi, dan supaya
 * query referensinya hanya ditulis sekali.
 *
 * Angka dibaca dari submission_results / submission_category_results yang
 * sudah dibekukan — tidak ada perhitungan ulang di sini.
 */
class ResultReport
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(public readonly Submission $submission) {}

    /** Hasil yang sudah selesai untuk sebuah uuid, atau null. */
    public static function forUuid(string $uuid): ?self
    {
        $submission = Submission::query()
            ->completed()
            ->where('uuid', $uuid)
            ->with([
                'lead',
                'result.tier.translations',
                'categoryResults.category.translations',
            ])
            ->first();

        return $submission ? new self($submission) : null;
    }

    /** @template T of mixed */
    private function once(string $key, callable $resolve): mixed
    {
        return $this->memo[$key] ??= $resolve();
    }

    // -----------------------------------------------------------------
    // Angka inti
    // -----------------------------------------------------------------

    public function firstName(): string
    {
        return $this->submission->lead?->firstName() ?? '';
    }

    public function score(): int
    {
        return (int) ($this->submission->result?->score ?? 0);
    }

    public function tier(): ?ResultTier
    {
        return $this->submission->result?->tier;
    }

    public function totalKg(): float
    {
        return (float) ($this->submission->result?->total_kg_co2e_year ?? 0);
    }

    public function totalTon(): float
    {
        return $this->totalKg() / 1000;
    }

    public function computedAt(): Carbon
    {
        return $this->submission->result?->computed_at ?? $this->submission->created_at;
    }

    /**
     * Nomor dokumen laporan, diturunkan dari uuid submission.
     * Bukan pengaman apa pun — hanya penanda supaya laporan yang dicetak
     * bisa dirujuk kembali ke satu perhitungan tertentu.
     */
    public function documentNumber(): string
    {
        $hex = strtoupper(str_replace('-', '', $this->submission->uuid));

        return 'TMGC-'.$this->computedAt()->format('Y').'-'
            .substr($hex, 0, 4).'-'.substr($hex, 4, 4);
    }

    /** Kartu per kategori, urut sesuai urutan langkah wizard. */
    public function categoryResults(): Collection
    {
        return $this->once('categories', fn () => $this->submission->categoryResults
            ->filter(fn ($row) => $row->category !== null)
            ->sortBy(fn ($row) => $row->category->sort_order)
            ->values());
    }

    // -----------------------------------------------------------------
    // Data referensi
    // -----------------------------------------------------------------

    public function tiers(): Collection
    {
        return $this->once('tiers', fn () => ResultTier::query()
            ->active()->ordered()->withTranslation()->get());
    }

    public function benchmark(): ?EmissionBenchmark
    {
        return $this->once('benchmark', fn () => EmissionBenchmark::query()
            ->active()->where('is_primary', true)->withTranslation()->first());
    }

    public function equivalences(): Collection
    {
        return $this->once('equivalences', fn () => EmissionEquivalence::query()
            ->active()->ordered()->withTranslation()->get());
    }

    /** Dua butir "Rekomendasi Aksi Khusus", dipilih berdasarkan tier. */
    public function recommendations(): Collection
    {
        return $this->once('recommendations', function () {
            $topCategoryId = $this->categoryResults()
                ->sortByDesc('kg_co2e_year')->first()?->emission_category_id;

            return Recommendation::query()
                ->matching($this->tier()?->id, $topCategoryId)
                ->with('translations')
                ->get();
        });
    }

    public function dropOffPoints(): Collection
    {
        return $this->once('dropOffs', fn () => DropOffPoint::query()->active()->ordered()->get());
    }

    public function communityImpacts(): Collection
    {
        return $this->once('impacts', fn () => CommunityImpact::query()
            ->active()->ordered()->withTranslation()->get());
    }

    // -----------------------------------------------------------------
    // Kalimat
    // -----------------------------------------------------------------

    /** "2 - 2,5" dari benchmark utama, atau "2,5" bila bukan rentang. */
    public function benchmarkRange(): ?string
    {
        $benchmark = $this->benchmark();

        if (! $benchmark) {
            return null;
        }

        return $benchmark->isRange()
            ? ResultText::compact($benchmark->value).' - '.ResultText::compact($benchmark->max_value)
            : ResultText::compact($benchmark->value);
    }

    /**
     * Kalimat pembanding. Bunyinya berbeda per tier ("sudah sangat baik" /
     * "mendekati rata-rata" / "di atas rata-rata"), jadi teksnya diambil dari
     * tier dan hanya rentang pembandingnya yang disisipkan. Bila tier belum
     * punya teks itu, dipakai kalimat generik di file bahasa yang memilih
     * sendiri "di atas" atau "di bawah".
     */
    public function benchmarkNote(): ?string
    {
        $note = $this->tier()?->tr('benchmark_note');

        if ($note) {
            return (string) ResultText::render($note, [
                'name' => $this->firstName(),
                'range' => $this->benchmarkRange() ?? '',
            ]);
        }

        if (! $this->benchmark()) {
            return null;
        }

        $key = $this->totalTon() > $this->benchmark()->upperValue() ? 'above' : 'below';

        return __('result.comparison.'.$key, [
            'name' => $this->firstName(),
            'benchmark' => $this->benchmark()->tr('label'),
            'range' => $this->benchmarkRange(),
        ]);
    }

    /**
     * "jika 100 orang dengan profil emisi sepertimu ..., lebih dari 16 - 23
     * Ton CO₂ dapat dihindari" — rentangnya milik tier, bukan angka tetap.
     */
    public function communityIntro(): string
    {
        $community = config('carbon-calculator.community');

        return __('result.community.intro', [
            'name' => $this->firstName(),
            'cohort' => $community['cohort_size'],
            'min' => ResultText::compact(
                $this->tier()?->community_avoided_min_ton_co2e ?? $community['avoided_ton_co2e_min']
            ),
            'max' => ResultText::compact(
                $this->tier()?->community_avoided_max_ton_co2e ?? $community['avoided_ton_co2e_max']
            ),
        ]);
    }

    /** Satu baris blok "setara dengan", angkanya sudah diformat. */
    public function equivalenceLine(EmissionEquivalence $equivalence): \Illuminate\Support\HtmlString
    {
        return ResultText::render($equivalence->tr('template'), [
            'value' => $this->equivalenceValue($equivalence),
        ]);
    }

    /**
     * Label satuan pendek tanpa angka di dalamnya ("Liter bensin kendaraan"),
     * untuk tata letak yang memisah angka dari keterangannya.
     */
    public function equivalenceUnit(EmissionEquivalence $equivalence): string
    {
        return $equivalence->tr('unit_label')
            ?: trim(strip_tags((string) $this->equivalenceLine($equivalence)));
    }

    /** Angka satuannya saja, untuk tata letak yang memisah angka dan label. */
    public function equivalenceValue(EmissionEquivalence $equivalence): string
    {
        return ResultText::number(
            $equivalence->unitsFor($this->totalKg()),
            $equivalence->decimals
        );
    }

    // -----------------------------------------------------------------
    // Skala pembanding di laporan
    // -----------------------------------------------------------------

    /**
     * Posisi emisi pengguna pada sumbu 0 - 5 ton, berikut pita rata-rata per
     * kapita Indonesia. Dipakai laporan cetak untuk menunjukkan "di mana saya
     * berdiri" — satu hal yang tidak bisa disampaikan angka tunggal.
     *
     * @return array{max: float, position: float, band_start: ?float, band_width: ?float, ticks: array<int, array{value: float, position: float}>}
     */
    public function benchmarkScale(): array
    {
        $ton = $this->totalTon();
        $max = max(5.0, ceil($ton));
        $benchmark = $this->benchmark();

        $ticks = [];
        for ($value = 0; $value <= $max; $value++) {
            $ticks[] = ['value' => (float) $value, 'position' => $value / $max * 100];
        }

        return [
            'max' => $max,
            'position' => max(0.0, min(100.0, $ton / $max * 100)),
            'band_start' => $benchmark ? $benchmark->value / $max * 100 : null,
            // Pita minimal 1% supaya tetap terlihat kalau benchmark-nya angka tunggal.
            'band_width' => $benchmark
                ? max(1.0, ($benchmark->upperValue() - $benchmark->value) / $max * 100)
                : null,
            'ticks' => $ticks,
        ];
    }
}
