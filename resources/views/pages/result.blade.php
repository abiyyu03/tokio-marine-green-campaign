<?php

use App\Models\CommunityImpact;
use App\Models\DropOffPoint;
use App\Models\EmissionBenchmark;
use App\Models\EmissionEquivalence;
use App\Models\Recommendation;
use App\Models\ResultTier;
use App\Models\Submission;
use App\Support\ResultText;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman hasil, mengikuti "Dokumentasi Logic & UI Copy Result Page".
 *
 * Seluruh angka dibaca dari submission_results / submission_category_results
 * yang sudah dibekukan saat submission selesai — halaman ini tidak menghitung
 * ulang apa pun, supaya laporan yang sudah dibagikan tidak berubah ketika
 * angka referensi diperbarui.
 *
 * Teksnya dinamis per tier: badge, kalimat pembanding, judul & pengantar
 * rekomendasi, butir aksi, dan rentang "jika 100 orang sepertimu" semuanya
 * mengikuti baris tier yang tersimpan di hasil.
 */
new #[Title('Jejak Karbon Tahunanmu | Tokio Marine Green Campaign')] class extends Component
{
    public string $uuid = '';

    /** Jumlah kartu Rumah Pilah yang sedang tampil ("Lihat Lebih Banyak"). */
    public int $visibleDropOffs = 3;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
        $this->visibleDropOffs = config('carbon-calculator.drop_off_page_size');

        abort_if($this->submission === null, 404);
    }

    #[Computed]
    public function submission(): ?Submission
    {
        return Submission::query()
            ->completed()
            ->where('uuid', $this->uuid)
            ->with([
                'lead',
                'result.tier.translations',
                'categoryResults.category.translations',
            ])
            ->first();
    }

    #[Computed]
    public function firstName(): string
    {
        return $this->submission->lead?->firstName() ?? '';
    }

    #[Computed]
    public function score(): int
    {
        return (int) ($this->submission->result?->score ?? 0);
    }

    #[Computed]
    public function tier(): ?ResultTier
    {
        return $this->submission->result?->tier;
    }

    #[Computed]
    public function tiers(): Collection
    {
        return ResultTier::query()->active()->ordered()->withTranslation()->get();
    }

    #[Computed]
    public function totalKg(): float
    {
        return (float) ($this->submission->result?->total_kg_co2e_year ?? 0);
    }

    /** Kartu per kategori, urut sesuai urutan langkah wizard. */
    #[Computed]
    public function categoryResults(): Collection
    {
        return $this->submission->categoryResults
            ->filter(fn ($row) => $row->category !== null)
            ->sortBy(fn ($row) => $row->category->sort_order)
            ->values();
    }

    #[Computed]
    public function benchmark(): ?EmissionBenchmark
    {
        return EmissionBenchmark::query()
            ->active()
            ->where('is_primary', true)
            ->withTranslation()
            ->first();
    }

    /**
     * Kalimat "[SUBHEADER / BENCHMARK NOTE]". Bunyinya berbeda per tier
     * ("sudah sangat baik" / "mendekati rata-rata" / "di atas rata-rata"),
     * jadi teksnya diambil dari tier dan hanya rentang pembandingnya yang
     * disisipkan. Bila tier belum punya teks itu, dipakai kalimat generik
     * di file bahasa yang memilih sendiri "di atas" atau "di bawah".
     */
    #[Computed]
    public function benchmarkNote(): ?string
    {
        $range = $this->benchmarkRange();
        $note = $this->tier?->tr('benchmark_note');

        if ($note) {
            return (string) ResultText::render($note, [
                'name' => $this->firstName,
                'range' => $range ?? '',
            ]);
        }

        if (! $this->benchmark) {
            return null;
        }

        $key = $this->totalKg / 1000 > $this->benchmark->upperValue() ? 'above' : 'below';

        return __('result.comparison.'.$key, [
            'name' => $this->firstName,
            'benchmark' => $this->benchmark->tr('label'),
            'range' => $range,
        ]);
    }

    /** "2 - 2,5" dari benchmark utama, atau "2,5" bila bukan rentang. */
    private function benchmarkRange(): ?string
    {
        $benchmark = $this->benchmark;

        if (! $benchmark) {
            return null;
        }

        return $benchmark->isRange()
            ? ResultText::compact($benchmark->value).' - '.ResultText::compact($benchmark->max_value)
            : ResultText::compact($benchmark->value);
    }

    #[Computed]
    public function equivalences(): Collection
    {
        return EmissionEquivalence::query()->active()->ordered()->withTranslation()->get();
    }

    /** Dua butir "Rekomendasi Aksi Khusus", dipilih berdasarkan tier. */
    #[Computed]
    public function recommendations(): Collection
    {
        $topCategoryId = $this->categoryResults->sortByDesc('kg_co2e_year')->first()?->emission_category_id;

        return Recommendation::query()
            ->matching($this->tier?->id, $topCategoryId)
            ->with('translations')
            ->get();
    }

    #[Computed]
    public function dropOffPoints(): Collection
    {
        return DropOffPoint::query()->active()->ordered()->get();
    }

    #[Computed]
    public function communityImpacts(): Collection
    {
        return CommunityImpact::query()->active()->ordered()->withTranslation()->get();
    }

    /**
     * "jika 100 orang dengan profil emisi sepertimu ..., lebih dari 16 - 23
     * Ton CO₂ dapat dihindari" — rentangnya milik tier, bukan angka tetap.
     */
    #[Computed]
    public function communityIntro(): string
    {
        $community = config('carbon-calculator.community');

        return __('result.community.intro', [
            'name' => $this->firstName,
            'cohort' => $community['cohort_size'],
            'min' => ResultText::compact(
                $this->tier?->community_avoided_min_ton_co2e ?? $community['avoided_ton_co2e_min']
            ),
            'max' => ResultText::compact(
                $this->tier?->community_avoided_max_ton_co2e ?? $community['avoided_ton_co2e_max']
            ),
        ]);
    }

    public function loadMoreDropOffs(): void
    {
        $this->visibleDropOffs += config('carbon-calculator.drop_off_page_size');
    }
};
?>

<div class="flex min-h-screen flex-col bg-[#f4f7f9]">
    <x-site-header active="calculator" />

    @php($tier = $this->tier)

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header Utama --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <h1 class="text-2xl font-bold text-[#0d9488] sm:text-3xl">{{ __('result.title') }}</h1>
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-[#0d9488] bg-white px-5 py-2 text-sm font-semibold text-[#0d9488] transition hover:bg-slate-50">
                    {{ __('result.download') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </button>
                <a href="{{ route('home') }}" wire:navigate class="rounded-lg bg-[#0d9488] px-6 py-2 text-sm font-semibold text-white transition hover:bg-teal-700">
                    {{ __('result.done') }}
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">

            {{-- KOLOM KIRI UTAMA --}}
            <div class="space-y-8">

                {{-- Sapaan & Intro --}}
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl font-bold text-slate-900">
                            {{ __('result.greeting', ['name' => $this->firstName]) }}
                        </h2>
                        @if ($tier)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                                style="color: {{ $tier->color }}; background-color: {{ $tier->color }}1A;"
                            >
                                <x-emission-icon :name="$tier->badge_icon" class="size-3.5" />
                                {{ $tier->tr('badge_label') }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ __('result.intro') }}</p>
                </div>

                {{-- Kartu total + kartu per kategori --}}
                <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">

                    {{-- Kartu Total --}}
                    <div class="flex flex-col justify-between rounded-[1.25rem] bg-[#eef8f8] p-6 border border-[#d6eef0]">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[15px] font-bold text-slate-800 leading-snug">{{ __('result.total_label') }}</p>
                            @if ($tier)
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold"
                                    style="color: {{ $tier->color }}; background-color: {{ $tier->color }}1A;"
                                >
                                    <span class="size-1.5 rounded-full" style="background-color: {{ $tier->color }}"></span>
                                    {{ $tier->tr('label') }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-8 text-3xl font-black text-slate-900">
                            {{ ResultText::tonCompact($this->totalKg) }} {{ __('result.ton_unit') }}
                            <span class="block mt-1.5 text-sm font-medium text-slate-500">{{ __('result.per_year') }}</span>
                        </p>
                    </div>

                    {{-- Kartu per kategori --}}
                    @foreach ($this->categoryResults as $row)
                        @php($accent = $row->category->accent_color ?: '#0d9488')
                        <div
                            class="flex flex-col justify-between rounded-2xl p-5 border"
                            style="background-color: {{ $accent }}0F; border-color: {{ $accent }}33;"
                            wire:key="category-{{ $row->emission_category_id }}"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-slate-800 leading-tight">{{ $row->category->tr('name') }}</p>
                                <span class="grid size-8 shrink-0 place-items-center rounded-lg text-white" style="background-color: {{ $accent }}">
                                    <x-emission-icon :name="$row->category->icon" class="size-4" />
                                </span>
                            </div>
                            <p class="mt-6 text-xl font-bold" style="color: {{ $accent }}">
                                ±{{ ResultText::tonCompact($row->kg_co2e_year) }} {{ __('result.ton_unit') }}
                                <span class="block mt-1 text-xs font-medium text-slate-500">{{ __('result.per_year') }}</span>
                            </p>
                        </div>
                    @endforeach

                </div>

                {{-- Kalimat pembanding per tier --}}
                @if ($this->benchmarkNote)
                    <div class="rounded-r-xl border-l-[5px] border-[#0d9488] bg-white px-5 py-4 shadow-sm">
                        <p class="text-sm text-slate-700 leading-relaxed">{!! $this->benchmarkNote !!}</p>
                    </div>
                @endif

                {{-- Setara Dengan --}}
                <section>
                    <h3 class="text-base font-bold text-slate-900">
                        {{ __('result.equivalence_heading', ['name' => $this->firstName]) }}
                    </h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($this->equivalences as $equivalence)
                            <li class="flex items-center gap-3" wire:key="equivalence-{{ $equivalence->id }}">
                                <x-emission-icon :name="$equivalence->icon" class="size-5 shrink-0 text-[#0d9488]" />
                                <span class="text-sm text-slate-700">
                                    {!! ResultText::render($equivalence->tr('template'), [
                                        'value' => ResultText::number(
                                            $equivalence->unitsFor($this->totalKg),
                                            $equivalence->decimals,
                                        ),
                                    ]) !!}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Rekomendasi aksi + direktori Rumah Pilah --}}
                <section class="rounded-2xl bg-[#eef8f8] p-6 sm:p-8 space-y-8">

                    @if ($tier)
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">
                                {!! ResultText::render($tier->tr('headline'), ['name' => $this->firstName]) !!}
                            </h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-700">
                                {!! ResultText::render($tier->tr('description'), ['name' => $this->firstName]) !!}
                            </p>
                        </div>
                    @endif

                    @if ($this->recommendations->isNotEmpty())
                        <div class="rounded-xl border-l-[5px] border-[#0d9488] bg-white p-5 sm:p-6 shadow-sm">
                            <div class="flex items-center gap-3 mb-4">
                                <svg class="size-5 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                <h3 class="text-sm font-bold text-slate-900">
                                    {{ __('result.recommendation_heading', ['name' => $this->firstName]) }}
                                </h3>
                            </div>
                            <ul class="space-y-3">
                                @foreach ($this->recommendations as $recommendation)
                                    <li class="flex items-start gap-3" wire:key="recommendation-{{ $recommendation->id }}">
                                        <div class="mt-1.5 size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                        <span class="text-sm text-slate-700 leading-relaxed">
                                            {!! ResultText::render($recommendation->tr('body'), ['name' => $this->firstName]) !!}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Direktori Rumah Pilah --}}
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <h3 class="text-base font-bold text-slate-900">{{ __('result.drop_off.heading') }}</h3>
                            @if ($this->dropOffPoints->count() > $visibleDropOffs)
                                <button type="button" wire:click="loadMoreDropOffs" class="inline-flex items-center gap-2 rounded-lg border border-[#0d9488] bg-white px-4 py-2 text-xs font-semibold text-[#0d9488] transition hover:bg-slate-50">
                                    {{ __('result.drop_off.see_more') }}
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            @endif
                        </div>

                        @if ($this->dropOffPoints->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('result.drop_off.empty') }}</p>
                        @else
                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach ($this->dropOffPoints->take($visibleDropOffs) as $point)
                                    <article class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white" wire:key="drop-off-{{ $point->id }}">
                                        <x-asset-image :src="$point->image_file" :alt="$point->name" class="h-32 w-full" />

                                        <div class="flex flex-1 flex-col gap-3 p-4">
                                            <h4 class="text-sm font-bold text-slate-900">{{ $point->name }}</h4>

                                            <div class="space-y-2 mt-2">
                                                <p class="flex items-start gap-2 text-[11px] text-slate-600">
                                                    <svg class="size-3.5 shrink-0 text-[#0d9488] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                    {{ $point->address }}
                                                </p>
                                                @if ($point->opening_hours)
                                                    <p class="flex items-center gap-2 text-[11px] text-slate-600">
                                                        <svg class="size-3.5 shrink-0 text-[#0d9488]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        {{ $point->opening_hours }}
                                                    </p>
                                                @endif
                                                @if ($point->phone)
                                                    <p class="flex items-center gap-2 text-[11px] text-slate-600">
                                                        <svg class="size-3.5 shrink-0 text-[#0d9488]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                        {{ $point->phone }}
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="mt-auto grid {{ $point->whatsappUrl() ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 pt-4">
                                                <a href="{{ $point->mapsUrl() }}" target="_blank" rel="noopener" class="rounded-lg border border-[#0d9488] py-2 text-center text-[10px] font-bold text-[#0d9488] hover:bg-slate-50">
                                                    {{ __('result.drop_off.maps') }}
                                                </a>
                                                @if ($point->whatsappUrl())
                                                    <a href="{{ $point->whatsappUrl() }}" target="_blank" rel="noopener" class="rounded-lg bg-[#0d9488] py-2 text-center text-[10px] font-bold text-white hover:bg-teal-700">
                                                        {{ __('result.drop_off.whatsapp') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Dampak Kolektif Komunitas --}}
                <section>
                    <h3 class="text-base font-bold text-slate-900">{{ __('result.community.heading') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $this->communityIntro }}</p>

                    <div class="mt-4 rounded-r-xl border-l-[5px] border-[#0d9488] bg-white px-6 py-5 shadow-sm">
                        <p class="text-sm font-bold text-slate-900">{{ __('result.community.stats_heading') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($this->communityImpacts as $impact)
                                <li class="flex items-center gap-3 text-sm text-slate-700" wire:key="impact-{{ $impact->id }}">
                                    <div class="size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                    <span>{!! ResultText::render($impact->tr('template'), [
                                        'value' => ResultText::compact($impact->value),
                                    ]) !!}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <button type="button" class="mt-6 rounded-lg bg-[#0d9488] px-6 py-3 text-sm font-bold text-white transition hover:bg-teal-700">
                        {{ __('result.community.cta') }}
                    </button>
                </section>
            </div>

            {{-- KOLOM KANAN: PANEL SKOR (Sticky) --}}
            <aside class="space-y-6 lg:sticky lg:top-24">

                {{-- Kartu skor --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                    <h3 class="text-center text-sm font-bold text-slate-900">{{ __('result.score_card.title') }}</h3>

                    <div class="relative mx-auto mt-6 flex size-48 items-center justify-center">
                        <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="100, 100"/>
                            <path stroke="{{ $tier?->color ?? '#cbd5e1' }}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3" stroke-dasharray="{{ max($this->score, 2) }}, 100" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center flex flex-col items-center">
                            <span class="text-4xl font-black text-slate-900">{{ $this->score }}</span>
                            <p class="text-[11px] font-medium text-slate-500 mt-1">{{ $tier?->tr('label') }}</p>
                        </div>
                    </div>

                    <div class="mt-8 space-y-2 pt-4">
                        @foreach ($this->tiers as $row)
                            <div class="flex items-center justify-center gap-2" wire:key="legend-{{ $row->id }}">
                                <span class="size-2.5 rounded-full" style="background-color: {{ $row->color }}"></span>
                                <span class="text-[11px] font-medium text-slate-600">
                                    {{ $row->tr('label') }} ({{ $row->scoreRangeLabel() }})
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tabel "Total poin dikategorikan" --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold text-slate-900 mb-4">{{ __('result.score_card.breakdown') }}</p>
                    <dl class="space-y-4">
                        @foreach ($this->tiers as $row)
                            <div class="flex items-center justify-between {{ $loop->last ? '' : 'border-b border-slate-100 pb-3' }}" wire:key="band-{{ $row->id }}">
                                <dt class="text-[11px] text-slate-500">
                                    {{ $row->scoreRangeLabel() }} {{ __('result.score_card.point_suffix') }}
                                </dt>
                                <dd class="text-right text-[11px] font-bold text-slate-900">
                                    {{ $row->tr('label') }}
                                    <span class="block font-medium text-slate-500">
                                        ({{ __('result.score_card.ton_hint', [
                                            'min' => ResultText::compact($row->approx_min_ton_co2e),
                                            'max' => ResultText::compact($row->approx_max_ton_co2e),
                                        ]) }})
                                    </span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Notice cek email --}}
                <div class="rounded-xl bg-[#f0f9ff] p-4 border border-[#e0f2fe]">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="size-4 text-[#0284c7]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h4 class="text-xs font-bold text-[#0284c7]">{{ __('result.email_notice.title') }}</h4>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">{{ __('result.email_notice.body') }}</p>
                </div>
            </aside>

        </div>
    </main>

    <x-site-footer />
</div>
