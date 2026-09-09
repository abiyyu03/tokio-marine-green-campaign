<?php

use App\Support\ResultReport;
use App\Support\ResultText;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Halaman hasil, mengikuti "Dokumentasi Logic & UI Copy Result Page".
 *
 * Angka dan kalimatnya disusun App\Support\ResultReport dari baris yang sudah
 * dibekukan saat submission selesai — halaman ini tidak menghitung ulang apa
 * pun, supaya laporan yang sudah dibagikan tidak berubah ketika angka
 * referensi diperbarui. Halaman laporan cetak memakai sumber yang sama.
 */
new class extends Component
{
    public string $uuid = '';

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;

        abort_if($this->report === null, 404);
    }

    #[Computed]
    public function report(): ?ResultReport
    {
        return ResultReport::forUuid($this->uuid);
    }
};
?>

<div class="flex min-h-screen flex-col bg-[#f4f7f9]">
    <x-site-header active="calculator" />

    @php($report = $this->report)
    @php($tier = $report->tier())

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header Utama --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <h1 class="text-2xl font-bold text-[#0d9488] sm:text-3xl">{{ __('result.title') }}</h1>
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('calculator.report', ['uuid' => $uuid, 'cetak' => 1]) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 rounded-lg border border-[#0d9488] bg-white px-5 py-2 text-sm font-semibold text-[#0d9488] transition hover:bg-slate-50"
                >
                    {{ __('result.download') }}
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </a>
                <a href="{{ route('home') }}" wire:navigate class="rounded-lg bg-[#0d9488] px-6 py-2 text-sm font-semibold text-white transition hover:bg-teal-700">
                    {{ __('result.done') }}
                </a>
            </div>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">

            {{-- KOLOM KIRI UTAMA.
                 `min-w-0` wajib: tanpa itu rail Rumah Pilah yang bisa digeser
                 melebarkan kolom ini dan seluruh halaman ikut bergeser ke samping
                 di layar sempit. --}}
            <div class="min-w-0 space-y-8">

                {{-- Sapaan & Intro --}}
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl font-bold text-slate-900">
                            {{ __('result.greeting', ['name' => $report->firstName()]) }}
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
                            {{ ResultText::tonCompact($report->totalKg()) }} {{ __('result.ton_unit') }}
                            <span class="block mt-1.5 text-sm font-medium text-slate-500">{{ __('result.per_year') }}</span>
                        </p>
                    </div>

                    {{-- Kartu per kategori --}}
                    @foreach ($report->categoryResults() as $row)
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
                @if ($report->benchmarkNote())
                    <div class="rounded-r-xl border-l-[5px] border-[#0d9488] bg-white px-5 py-4 shadow-sm">
                        <p class="text-sm text-slate-700 leading-relaxed">{!! $report->benchmarkNote() !!}</p>
                    </div>
                @endif

                {{-- Setara Dengan --}}
                <section>
                    <h3 class="text-base font-bold text-slate-900">
                        {{ __('result.equivalence_heading', ['name' => $report->firstName()]) }}
                    </h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($report->equivalences() as $equivalence)
                            <li class="flex items-center gap-3" wire:key="equivalence-{{ $equivalence->id }}">
                                <x-emission-icon :name="$equivalence->icon" class="size-5 shrink-0 text-[#0d9488]" />
                                <span class="text-sm text-slate-700">
                                    {!! $report->equivalenceLine($equivalence) !!}
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
                                {!! ResultText::render($tier->tr('headline'), ['name' => $report->firstName()]) !!}
                            </h2>
                            <p class="mt-2 text-sm leading-relaxed text-slate-700">
                                {!! ResultText::render($tier->tr('description'), ['name' => $report->firstName()]) !!}
                            </p>
                        </div>
                    @endif

                    @if ($report->recommendations()->isNotEmpty())
                        <div class="rounded-xl border-l-[5px] border-[#0d9488] bg-white p-5 sm:p-6 shadow-sm">
                            <div class="flex items-center gap-3 mb-4">
                                <svg class="size-5 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                <h3 class="text-sm font-bold text-slate-900">
                                    {{ __('result.recommendation_heading', ['name' => $report->firstName()]) }}
                                </h3>
                            </div>
                            <ul class="space-y-3">
                                @foreach ($report->recommendations() as $recommendation)
                                    <li class="flex items-start gap-3" wire:key="recommendation-{{ $recommendation->id }}">
                                        <div class="mt-1.5 size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                        <span class="text-sm text-slate-700 leading-relaxed">
                                            {!! ResultText::render($recommendation->tr('body'), ['name' => $report->firstName()]) !!}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Direktori Rumah Pilah --}}
                    @php($dropOffs = $report->dropOffPoints())
                    <div
                        x-data="{
                            atStart: true,
                            atEnd: false,
                            sync() {
                                const rail = $refs.rail;
                                this.atStart = rail.scrollLeft <= 4;
                                this.atEnd = Math.ceil(rail.scrollLeft + rail.clientWidth) >= rail.scrollWidth - 4;
                            },
                            nudge(direction) {
                                const rail = $refs.rail;
                                rail.scrollBy({ left: direction * Math.round(rail.clientWidth * 0.9), behavior: 'smooth' });
                            },
                        }"
                        x-init="$nextTick(() => sync())"
                        @resize.window="sync()"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">{{ __('result.drop_off.heading') }}</h3>
                                @if ($dropOffs->isNotEmpty())
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ __('result.drop_off.count', ['count' => $dropOffs->count()]) }}
                                    </p>
                                @endif
                            </div>

                            {{-- Tombol geser hanya berguna saat memakai tetikus;
                                 di layar sentuh railnya digeser langsung. --}}
                            @if ($dropOffs->count() > 1)
                                <div class="hidden gap-2 sm:flex">
                                    <button
                                        type="button" @click="nudge(-1)" :disabled="atStart"
                                        :class="atStart ? 'cursor-not-allowed opacity-40' : 'hover:bg-slate-50'"
                                        aria-label="{{ __('result.drop_off.scroll_prev') }}"
                                        class="grid size-9 place-items-center rounded-lg border border-[#0d9488] bg-white text-[#0d9488] transition"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
                                    </button>
                                    <button
                                        type="button" @click="nudge(1)" :disabled="atEnd"
                                        :class="atEnd ? 'cursor-not-allowed opacity-40' : 'hover:bg-slate-50'"
                                        aria-label="{{ __('result.drop_off.scroll_next') }}"
                                        class="grid size-9 place-items-center rounded-lg border border-[#0d9488] bg-white text-[#0d9488] transition"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($dropOffs->isEmpty())
                            <p class="text-sm text-slate-500">{{ __('result.drop_off.empty') }}</p>
                        @else
                            <div class="relative">
                                {{-- Rail horizontal: jumlah lokasi bisa bertambah tanpa
                                     memanjangkan halaman. Padding negatif membuat kartu
                                     bisa digeser sampai tepi layar di mobile. --}}
                                <div
                                    x-ref="rail" @scroll.passive="sync()"
                                    tabindex="0"
                                    role="group"
                                    aria-label="{{ __('result.drop_off.heading') }}"
                                    class="-mx-6 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-6 pb-3 sm:-mx-8 sm:px-8"
                                >
                                    @foreach ($dropOffs as $point)
                                        <article class="flex w-[16.5rem] shrink-0 snap-start flex-col overflow-hidden rounded-xl border border-slate-200 bg-white sm:w-[18.5rem]" wire:key="drop-off-{{ $point->id }}">
                                            <x-asset-image :src="$point->image_file" :alt="$point->name" class="h-32 w-full shrink-0" />

                                            <div class="flex flex-1 flex-col gap-3 p-4">
                                                <h4 class="text-sm font-bold text-slate-900">{{ $point->name }}</h4>

                                                <div class="space-y-2">
                                                    <p class="flex items-start gap-2 text-[11px] text-slate-600">
                                                        <svg class="size-3.5 shrink-0 text-[#0d9488] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                        <span>
                                                            {{ $point->address }}
                                                            @if ($point->city)
                                                                <span class="block text-slate-500">{{ $point->city }}</span>
                                                            @endif
                                                        </span>
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
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Dampak Kolektif Komunitas --}}
                <section>
                    <h3 class="text-base font-bold text-slate-900">{{ __('result.community.heading') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $report->communityIntro() }}</p>

                    <div class="mt-4 rounded-r-xl border-l-[5px] border-[#0d9488] bg-white px-6 py-5 shadow-sm">
                        <p class="text-sm font-bold text-slate-900">{{ __('result.community.stats_heading') }}</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($report->communityImpacts() as $impact)
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
                            <path stroke="{{ $tier?->color ?? '#cbd5e1' }}" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3" stroke-dasharray="{{ max($report->score(), 2) }}, 100" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center flex flex-col items-center">
                            <span class="text-4xl font-black text-slate-900">{{ $report->score() }}</span>
                            <p class="text-[11px] font-medium text-slate-500 mt-1">{{ $tier?->tr('label') }}</p>
                        </div>
                    </div>

                    <div class="mt-8 space-y-2 pt-4">
                        @foreach ($report->tiers() as $row)
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
                        @foreach ($report->tiers() as $row)
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

                {{-- Notice email. Isinya mengikuti kenyataan: kalimat "cek email
                     kamu" hanya muncul kalau emailnya memang berhasil dikirim,
                     kalau gagal peserta diarahkan menyimpan tautan halaman ini. --}}
                @php($emailSent = $report->emailSentAt())
                <div class="rounded-xl border p-4 {{ $emailSent ? 'bg-[#f0f9ff] border-[#e0f2fe]' : 'bg-amber-50 border-amber-200' }}">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="size-4 {{ $emailSent ? 'text-[#0284c7]' : 'text-amber-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h4 class="text-xs font-bold {{ $emailSent ? 'text-[#0284c7]' : 'text-amber-700' }}">
                            {{ $emailSent ? __('result.email_notice.title') : __('result.email_notice.title_pending') }}
                        </h4>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        @if ($emailSent)
                            {{ __('result.email_notice.body', ['email' => $report->maskedEmail() ?? __('result.email_notice.your_email')]) }}
                        @else
                            {{ __('result.email_notice.body_pending') }}
                        @endif
                    </p>
                </div>
            </aside>

        </div>
    </main>

    <x-site-footer />
</div>
