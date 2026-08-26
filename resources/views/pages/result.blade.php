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
 * Halaman hasil. Membaca angka yang sudah dibekukan di submission_results,
 * bukan menghitung ulang, supaya laporan yang dibagikan tidak berubah ketika
 * faktor emisi diperbarui.
 */
new #[Title('Jejak Karbon Tahunanmu | Tokio Marine Green Campaign')] class extends Component
{
    public string $uuid = '';

    /** Jumlah kartu Rumah Pilah yang sedang tampil ("Load More"). */
    public int $visibleDropOffs = 4;

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

    #[Computed]
    public function equivalences(): Collection
    {
        return EmissionEquivalence::query()->active()->ordered()->withTranslation()->get();
    }

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

    /** Kalimat pembanding: di atas atau di bawah rata-rata Indonesia. */
    #[Computed]
    public function comparison(): ?string
    {
        $benchmark = $this->benchmark;

        if (! $benchmark) {
            return null;
        }

        $ton = $this->totalKg / 1000;
        $key = $ton > $benchmark->upperValue() ? 'above' : 'below';

        $range = $benchmark->isRange()
            ? ResultText::compact($benchmark->value).' - '.ResultText::compact($benchmark->max_value)
            : ResultText::compact($benchmark->value);

        return __('result.comparison.'.$key, [
            'name' => $this->firstName,
            'benchmark' => $benchmark->tr('label'),
            'range' => $range,
        ]);
    }

    public function loadMoreDropOffs(): void
    {
        $this->visibleDropOffs += config('carbon-calculator.drop_off_page_size');
    }
};
?>

<div class="flex min-h-screen flex-col bg-slate-50">
    <x-site-header active="calculator" />

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold text-brand-500 sm:text-3xl">{{ __('result.title') }}</h1>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_16rem] lg:items-start">

            <div class="space-y-6">
                {{-- Sapaan + badge tier --}}
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-bold text-slate-900">
                            {{ __('result.greeting', ['name' => $this->firstName]) }}
                        </h2>

                        @if ($this->tier)
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[0.7rem] font-semibold"
                                style="color: {{ $this->tier->color }}; background-color: {{ $this->tier->color }}1A;"
                            >
                                <x-emission-icon :name="$this->tier->badge_icon" class="size-3.5" />
                                {{ $this->tier->tr('badge_label') }}
                            </span>
                        @endif
                    </div>

                    <p class="mt-2 text-sm text-slate-600">{{ __('result.intro') }}</p>
                </div>

                {{-- Kartu total + kartu per kategori --}}
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-card border border-brand-200 bg-brand-50 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-700">{{ __('result.total_label') }}</p>
                            @if ($this->tier)
                                <span
                                    class="rounded-full px-2 py-0.5 text-[0.6rem] font-semibold whitespace-nowrap"
                                    style="color: {{ $this->tier->color }}; background-color: {{ $this->tier->color }}1A;"
                                >{{ $this->tier->tr('label') }}</span>
                            @endif
                        </div>
                        <p class="mt-4 text-xl font-bold text-slate-900">
                            ±{{ \App\Support\ResultText::ton($this->totalKg, 1) }}
                            <span class="text-xs font-medium text-slate-500">{{ __('result.ton_per_year') }}</span>
                        </p>
                    </div>

                    @foreach ($this->categoryResults as $row)
                        <div class="rounded-card border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-semibold text-slate-700">{{ $row->category->tr('name') }}</p>
                                <span
                                    class="grid size-6 shrink-0 place-items-center rounded-md"
                                    style="color: {{ $row->category->accent_color }}; background-color: {{ $row->category->accent_color }}1A;"
                                >
                                    <x-emission-icon :name="$row->category->icon" class="size-3.5" />
                                </span>
                            </div>
                            <p class="mt-4 text-lg font-bold" style="color: {{ $row->category->accent_color }}">
                                ±{{ \App\Support\ResultText::ton($row->kg_co2e_year, 2) }}
                                <span class="text-[0.65rem] font-medium text-slate-500">{{ __('result.ton_per_year') }}</span>
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Kalimat pembanding --}}
                @if ($this->comparison)
                    <p class="rounded-r-lg border-l-4 border-brand-500 bg-white px-4 py-3 text-xs text-slate-700">
                        {{ $this->comparison }}
                    </p>
                @endif

                {{-- "Setara dengan" --}}
                @if ($this->equivalences->isNotEmpty())
                    <section>
                        <h3 class="text-sm font-bold text-slate-900">
                            {{ __('result.equivalence_heading', ['name' => $this->firstName]) }}
                        </h3>
                        <ul class="mt-3 space-y-2">
                            @foreach ($this->equivalences as $equivalence)
                                <li class="flex items-start gap-2.5 text-xs text-slate-700">
                                    <x-emission-icon :name="$equivalence->icon" class="mt-0.5 size-4 shrink-0 text-brand-500" />
                                    <span>
                                        {{ \App\Support\ResultText::render($equivalence->tr('template'), [
                                            'value' => \App\Support\ResultText::number($equivalence->unitsFor($this->totalKg), $equivalence->decimals),
                                        ]) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- Kotak biru: rekomendasi + direktori Rumah Pilah --}}
                <section class="space-y-5 rounded-card bg-brand-50 p-5">
                    @if ($this->tier)
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                {{ str_replace(':name', $this->firstName, $this->tier->tr('headline')) }}
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-slate-700">
                                {{ $this->tier->tr('description') }}
                            </p>
                        </div>
                    @endif

                    @if ($this->recommendations->isNotEmpty())
                        <div class="rounded-lg border-l-4 border-brand-500 bg-white p-4">
                            <p class="text-xs font-bold text-slate-900">
                                {{ __('result.recommendation_heading', ['name' => $this->firstName]) }}
                            </p>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($this->recommendations as $recommendation)
                                    <li class="flex gap-2 text-xs leading-relaxed text-slate-700">
                                        <span class="text-brand-500">&bull;</span>
                                        <span>{{ \App\Support\ResultText::render($recommendation->tr('body'), ['name' => $this->firstName]) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('result.drop_off.heading') }}</h3>

                        @if ($this->dropOffPoints->isEmpty())
                            <p class="mt-3 text-xs text-slate-500">{{ __('result.drop_off.empty') }}</p>
                        @else
                            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ($this->dropOffPoints->take($visibleDropOffs) as $point)
                                    <article wire:key="drop-{{ $point->id }}" class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white">
                                        <x-asset-image :src="$point->image_file" :alt="$point->name" class="h-24 w-full" />

                                        <div class="flex flex-1 flex-col gap-2 p-3">
                                            <h4 class="text-xs leading-snug font-bold text-slate-900">{{ $point->name }}</h4>

                                            <p class="flex gap-1.5 text-[0.65rem] leading-relaxed text-slate-600">
                                                <x-emission-icon name="leaf" class="mt-0.5 size-3 shrink-0 text-brand-500" />
                                                {{ $point->address }}
                                            </p>
                                            <p class="text-[0.65rem] text-slate-600">{{ $point->opening_hours }}</p>
                                            <p class="text-[0.65rem] text-slate-600">{{ $point->phone }}</p>

                                            <div class="mt-auto space-y-1.5 pt-2">
                                                <a href="{{ $point->mapsUrl() }}" target="_blank" rel="noopener"
                                                   class="block rounded-md border border-slate-300 py-1.5 text-center text-[0.65rem] font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    {{ __('result.drop_off.maps') }}
                                                </a>

                                                @if ($point->whatsappUrl())
                                                    <a href="{{ $point->whatsappUrl() }}" target="_blank" rel="noopener"
                                                       class="block rounded-md bg-deep-600 py-1.5 text-center text-[0.65rem] font-semibold text-white transition hover:bg-deep-700">
                                                        {{ __('result.drop_off.whatsapp') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            @if ($this->dropOffPoints->count() > $visibleDropOffs)
                                <div class="mt-4 text-center">
                                    <button type="button" wire:click="loadMoreDropOffs"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                        {{ __('result.drop_off.load_more') }}
                                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>
                </section>

                {{-- Dampak kolektif komunitas --}}
                <section>
                    <h3 class="text-sm font-bold text-slate-900">{{ __('result.community.heading') }}</h3>

                    <p class="mt-2 text-xs leading-relaxed text-slate-600">
                        {{ __('result.community.intro', [
                            'name' => $this->firstName,
                            'cohort' => config('carbon-calculator.community.cohort_size'),
                            'min' => config('carbon-calculator.community.avoided_ton_co2e_min'),
                            'max' => config('carbon-calculator.community.avoided_ton_co2e_max'),
                        ]) }}
                    </p>

                    @if ($this->communityImpacts->isNotEmpty())
                        <div class="mt-3 rounded-r-lg border-l-4 border-brand-500 bg-white px-4 py-3">
                            <p class="text-xs font-semibold text-slate-900">{{ __('result.community.stats_heading') }}</p>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($this->communityImpacts as $impact)
                                    <li class="flex gap-2 text-xs text-slate-700">
                                        <span class="text-brand-500">&bull;</span>
                                        <span>{{ \App\Support\ResultText::render($impact->tr('template'), [
                                            'value' => \App\Support\ResultText::compact($impact->value),
                                        ]) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <a href="{{ route('calculator') }}" wire:navigate
                       class="mt-4 inline-block rounded-lg bg-deep-600 px-5 py-2 text-xs font-semibold text-white transition hover:bg-deep-700">
                        {{ __('result.community.cta') }}
                    </a>
                </section>
            </div>

            {{-- Sidebar skor akhir --}}
            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="rounded-card border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-center text-sm font-bold text-slate-900">{{ __('result.score_card.title') }}</h2>

                    <x-score-gauge :score="$this->submission->result?->score ?? 0" :tier="$this->tier" class="mt-3" />

                    <x-tier-legend :tiers="$this->tiers" class="mt-4" />
                </div>

                <div class="rounded-card border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[0.7rem] font-bold text-slate-900">{{ __('result.score_card.breakdown') }}</p>

                    <dl class="mt-2 divide-y divide-slate-100">
                        @foreach ($this->tiers as $tier)
                            <div class="flex items-start justify-between gap-3 py-2">
                                <dt class="text-[0.65rem] whitespace-nowrap text-slate-500">
                                    {{ $tier->scoreRangeLabel() }} {{ __('result.score_card.point_suffix') }}
                                </dt>
                                <dd class="text-right text-[0.65rem] font-semibold" style="color: {{ $tier->color }}">
                                    {{ $tier->tr('label') }}
                                    <span class="block font-normal text-slate-500">
                                        {{ __('result.score_card.ton_hint', [
                                            'min' => \App\Support\ResultText::compact($tier->approx_min_ton_co2e),
                                            'max' => \App\Support\ResultText::compact($tier->approx_max_ton_co2e),
                                        ]) }}
                                    </span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </aside>
        </div>
    </main>

    <x-site-footer />
</div>
