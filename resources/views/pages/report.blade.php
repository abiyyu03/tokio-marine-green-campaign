<?php

use App\Support\ResultReport;
use App\Support\ResultText;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Laporan hasil siap cetak.
 *
 * Isinya sama persis dengan halaman hasil — keduanya membaca
 * App\Support\ResultReport — tapi tata letaknya dokumen, bukan dasbor:
 * lembar A4, rata kiri, angka rata kanan dengan angka tabular, garis rambut
 * sebagai pemisah, tanpa kartu dan bayangan yang tidak berarti apa-apa di
 * atas kertas.
 *
 * Dibuka dengan ?cetak=1 dari tombol "Download Result" supaya dialog cetak
 * langsung muncul; tanpa parameter itu halaman tetap bisa dibaca biasa.
 */
new #[Title('Laporan Jejak Karbon | Tokio Marine Green Campaign')] class extends Component
{
    public string $uuid = '';

    /** Membuka dialog cetak begitu halaman siap. */
    public bool $autoPrint = false;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
        $this->autoPrint = request()->boolean('cetak');

        abort_if($this->report === null, 404);
    }

    #[Computed]
    public function report(): ?ResultReport
    {
        return ResultReport::forUuid($this->uuid);
    }
};
?>

@php
    $report = $this->report;
    $tier = $report->tier();
    $scale = $report->benchmarkScale();
    $accent = $tier?->color ?: '#0B3B36';
    $dropOffs = $report->dropOffPoints()->take(3);
@endphp

<div class="report" style="--tier: {{ $accent }}">
    <style>
        /* ---------------------------------------------------------------
           Laporan cetak. Warna sengaja lebih gelap dari halaman hasil:
           pine untuk garis & judul, teal kampanye hanya untuk satu aksen,
           sisanya tinta. Semuanya masih terbaca di printer hitam-putih.
           --------------------------------------------------------------- */
        .report {
            --ink: #1f2937;
            --pine: #0b3b36;
            --teal: #0d9488;
            --wash: #f1f6f4;
            --rule: #d8e3df;
            --muted: #5f7370;
            color: var(--ink);
            background: #e8edeb;
            min-height: 100vh;
        }

        .report-sheet { width: 210mm; }
        .report-page { min-height: 297mm; padding: 16mm 18mm; }

        /* Di layar yang lebih sempit dari selembar A4, lembarnya mengikuti
           lebar layar; ukuran kertas hanya berlaku saat dicetak. */
        @media screen and (max-width: 230mm) {
            .report-sheet { width: 100%; }
            .report-page { min-height: auto; padding: 7vw 6vw; }
        }

        /* Angka dokumen selalu tabular supaya kolomnya lurus. */
        .report figure, .report .figure { font-variant-numeric: tabular-nums; }

        .rule-hair { border-color: var(--rule); }

        /* Judul bagian: teks kecil pine dengan garis rambut penuh di bawahnya. */
        .section-title {
            color: var(--pine);
            font-weight: 600;
            letter-spacing: 0.01em;
            border-bottom: 1px solid var(--rule);
            padding-bottom: 0.4rem;
        }

        @media print {
            @page { size: A4 portrait; margin: 0; }

            html, body { background: #fff !important; }
            .report { background: #fff !important; min-height: auto; }

            /* Pita, warna tier, dan latar tabel harus ikut tercetak. */
            .report, .report * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            .report-screen-only { display: none !important; }
            .report-sheet { width: auto; box-shadow: none !important; }
            /* Setinggi satu lembar A4 (dikurangi 1px agar tidak melimpah ke halaman berikutnya),
               supaya nomor halaman jatuh di dasar kertas. */
            .report-page { min-height: calc(297mm - 1px); padding: 14mm 16mm; }
            .report-page + .report-page { break-before: page; }
            .avoid-break { break-inside: avoid; }
        }
    </style>

    {{-- Bilah aksi, hanya di layar --}}
    <div class="report-screen-only sticky top-0 z-10 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a
                href="{{ route('calculator.result', ['uuid' => $uuid]) }}"
                wire:navigate
                class="inline-flex items-center gap-2 text-sm font-semibold text-[#0b3b36] transition hover:text-[#0d9488]"
            >
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1 0 1.06L9.06 10l3.73 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.24a.75.75 0 0 1 0-1.06l4.25-4.24a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" /></svg>
                {{ __('result.report.back') }}
            </a>

            <div class="flex items-center gap-4">
                <p class="hidden text-xs text-slate-500 sm:block">{{ __('result.report.hint') }}</p>
                <button
                    type="button"
                    onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#0d9488] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#0b7c72] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0b3b36]"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('result.report.save') }}
                </button>
            </div>
        </div>
    </div>

    <div class="report-sheet mx-auto my-8 bg-white print:my-0 shadow-[0_1px_24px_rgba(11,59,54,0.12)] print:shadow-none">

        {{-- ============================ HALAMAN 1 ============================ --}}
        <div class="report-page flex flex-col">

            {{-- Kop dokumen --}}
            <header class="flex flex-col gap-4 border-b-2 pb-4 sm:flex-row sm:items-start sm:justify-between sm:gap-8" style="border-color: var(--pine)">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-[#0b3b36] text-[13px] font-bold text-white">TM</span>
                    <div class="leading-tight">
                        <p class="text-[13px] font-bold text-[#0b3b36]">Tokio Marine Insurance Group</p>
                        <p class="text-[11px] text-[#5f7370]">{{ __('result.report.campaign') }}</p>
                    </div>
                </div>

                <dl class="figure grid grid-cols-[auto_auto] gap-x-3 gap-y-1 text-left text-[10px] leading-tight whitespace-nowrap sm:text-right">
                    <dt class="text-[#5f7370]">{{ __('result.report.document') }}</dt>
                    <dd class="font-semibold text-[#0b3b36]">{{ $report->documentNumber() }}</dd>
                    <dt class="text-[#5f7370]">{{ __('result.report.issued') }}</dt>
                    <dd class="font-semibold text-[#0b3b36]">{{ $report->computedAt()->translatedFormat('j F Y') }}</dd>
                </dl>
            </header>

            {{-- Judul + penerima --}}
            <div class="mt-8">
                <h1 class="max-w-[16ch] text-[30px] leading-[1.15] font-bold tracking-[-0.015em] text-[#0b3b36]">
                    {{ __('result.report.title') }}
                </h1>
                <p class="mt-3 text-[13px] text-[#5f7370]">
                    {{ __('result.report.prepared_for') }}
                    <span class="ml-1 font-semibold text-[#1f2937]">{{ $report->submission->lead?->name ?? $report->firstName() }}</span>
                </p>
            </div>

            {{-- Angka utama + skor --}}
            <section class="avoid-break mt-6 grid grid-cols-1 gap-6 rounded bg-[#f1f6f4] px-7 py-6 sm:grid-cols-[1fr_auto] sm:items-end sm:gap-8 print:grid-cols-[1fr_auto] print:items-end print:gap-8">
                <div>
                    <p class="text-[11px] font-semibold text-[#5f7370]">{{ __('result.report.total_heading') }}</p>
                    <p class="figure mt-1 flex items-baseline gap-2">
                        <span class="text-[56px] leading-none font-bold tracking-[-0.03em] text-[#0b3b36]">
                            {{ ResultText::tonCompact($report->totalKg()) }}
                        </span>
                        <span class="text-[13px] font-medium text-[#5f7370]">{{ __('result.ton_unit') }} {{ __('result.per_year') }}</span>
                    </p>
                </div>

                <div class="border-t pt-4 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-7 sm:text-right print:border-t-0 print:border-l print:pt-0 print:pl-7 print:text-right" style="border-color: var(--rule)">
                    <p class="text-[11px] font-semibold text-[#5f7370]">{{ __('result.report.score_heading') }}</p>
                    <p class="figure mt-1 text-[34px] leading-none font-bold text-[#0b3b36]">{{ $report->score() }}</p>
                    @if ($tier)
                        <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-bold" style="color: var(--tier)">
                            <span class="size-1.5 rounded-full" style="background-color: var(--tier)"></span>
                            {{ $tier->tr('label') }}
                        </p>
                        <p class="text-[11px] text-[#5f7370]">{{ $tier->tr('badge_label') }}</p>
                    @endif
                </div>
            </section>

            {{-- Skala pembanding: satu-satunya hal yang tak bisa disampaikan angka tunggal --}}
            <section class="avoid-break mt-9">
                <h2 class="section-title text-[12px]">{{ __('result.report.scale_heading') }}</h2>

                <div class="mt-8 pb-1">
                    <div class="relative mx-5 h-px" style="background-color: var(--rule)">
                        {{-- Pita rata-rata per kapita Indonesia --}}
                        @if ($scale['band_start'] !== null)
                            <div
                                class="absolute -top-2.5 h-6 rounded-[2px] bg-[#0d9488]/15"
                                style="left: {{ $scale['band_start'] }}%; width: {{ $scale['band_width'] }}%;"
                            ></div>
                        @endif

                        {{-- Penanda posisi pengguna --}}
                        <div class="absolute -top-9 flex flex-col items-center" style="left: {{ $scale['position'] }}%; transform: translateX(-50%);">
                            <span class="figure text-[12px] font-bold whitespace-nowrap" style="color: var(--tier)">
                                {{ ResultText::tonCompact($report->totalKg()) }}
                            </span>
                            <span class="mt-1 block h-4 w-px" style="background-color: var(--tier)"></span>
                        </div>

                        {{-- Garis skala --}}
                        @foreach ($scale['ticks'] as $tick)
                            <span class="absolute top-0 h-1.5 w-px" style="left: {{ $tick['position'] }}%; background-color: var(--rule)"></span>
                            <span
                                class="figure absolute top-3 text-[10px] text-[#5f7370]"
                                style="left: {{ $tick['position'] }}%; transform: translateX(-50%);"
                            >{{ ResultText::compact($tick['value'], 0) }}</span>
                        @endforeach
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-1 text-[10px] text-[#5f7370]">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2.5 w-3 rounded-[2px] bg-[#0d9488]/15"></span>
                            {{ __('result.report.scale_benchmark') }} ({{ $report->benchmarkRange() }})
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-px" style="background-color: var(--tier)"></span>
                            {{ __('result.report.scale_you') }}
                        </span>
                        <span class="ml-auto">{{ __('result.report.scale_unit') }}</span>
                    </div>
                </div>

                @if ($report->benchmarkNote())
                    <p class="mt-5 border-l-2 pl-4 text-[12px] leading-relaxed text-[#1f2937]" style="border-color: var(--tier)">
                        {!! $report->benchmarkNote() !!}
                    </p>
                @endif
            </section>

            {{-- Rincian per sektor sebagai baris rekening, bukan kartu --}}
            <section class="avoid-break mt-9">
                <h2 class="section-title text-[12px]">{{ __('result.report.sector_heading') }}</h2>

                <table class="mt-3 w-full text-[12px]">
                    <thead>
                        <tr class="text-[10px] text-[#5f7370]">
                            <th scope="col" class="pb-2 text-left font-medium">{{ __('result.report.sector_column') }}</th>
                            <th scope="col" class="pb-2 text-right font-medium">{{ __('result.report.share_column') }}</th>
                            <th scope="col" class="pb-2 text-right font-medium">{{ __('result.report.emission_column') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->categoryResults() as $row)
                            <tr class="border-t rule-hair">
                                <th scope="row" class="py-2.5 text-left font-semibold text-[#1f2937]">
                                    {{ $row->category->tr('name') }}
                                </th>
                                <td class="figure py-2.5 text-right text-[#5f7370]">
                                    {{ ResultText::compact($row->percentage, 0) }}%
                                </td>
                                <td class="figure py-2.5 text-right font-semibold text-[#0b3b36]">
                                    {{ ResultText::tonCompact($row->kg_co2e_year) }} {{ __('result.ton_unit') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="border-t-2" style="border-color: var(--pine)">
                            <th scope="row" class="pt-3 text-left font-bold text-[#0b3b36]">
                                {{ __('result.report.total_row') }}
                            </th>
                            <td></td>
                            <td class="figure pt-3 text-right text-[15px] font-bold text-[#0b3b36]">
                                {{ ResultText::tonCompact($report->totalKg()) }} {{ __('result.ton_unit') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            {{-- Padanan visual --}}
            <section class="avoid-break mt-9">
                <h2 class="section-title text-[12px]">{{ __('result.report.equivalence_heading') }}</h2>

                <div class="mt-4 grid grid-cols-3 gap-6">
                    @foreach ($report->equivalences() as $equivalence)
                        <div>
                            <p class="figure text-[26px] leading-none font-bold text-[#0b3b36]">
                                {{ $report->equivalenceValue($equivalence) }}
                            </p>
                            <p class="mt-2 max-w-[22ch] text-[11px] leading-snug text-[#5f7370]">
                                {{ $report->equivalenceUnit($equivalence) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            <p class="figure mt-auto pt-8 text-[10px] text-[#5f7370]">
                {{ __('result.report.page', ['number' => 1, 'total' => 2]) }}
            </p>
        </div>

        {{-- ============================ HALAMAN 2 ============================ --}}
        <div class="report-page flex flex-col border-t border-dashed rule-hair print:border-0">

            {{-- Langkah lanjutan --}}
            <section class="avoid-break">
                <h2 class="section-title text-[12px]">{{ __('result.report.action_heading') }}</h2>

                @if ($tier)
                    <h3 class="mt-4 text-[18px] font-bold text-[#0b3b36]">
                        {!! ResultText::render($tier->tr('headline'), ['name' => $report->firstName()]) !!}
                    </h3>
                    <p class="mt-2 max-w-[68ch] text-[12px] leading-relaxed text-[#1f2937]">
                        {!! ResultText::render($tier->tr('description'), ['name' => $report->firstName()]) !!}
                    </p>
                @endif

                @if ($report->recommendations()->isNotEmpty())
                    <ul class="mt-5 space-y-2.5 border-l-2 pl-5" style="border-color: var(--tier)">
                        @foreach ($report->recommendations() as $recommendation)
                            <li class="max-w-[68ch] text-[12px] leading-relaxed text-[#1f2937]">
                                {!! ResultText::render($recommendation->tr('body'), ['name' => $report->firstName()]) !!}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Rumah Pilah --}}
            @if ($dropOffs->isNotEmpty())
                <section class="avoid-break mt-9">
                    <h2 class="section-title text-[12px]">{{ __('result.drop_off.heading') }}</h2>
                    <p class="mt-3 text-[11px] text-[#5f7370]">{{ __('result.report.drop_off_note') }}</p>

                    <div class="mt-4 grid grid-cols-3 gap-5">
                        @foreach ($dropOffs as $point)
                            <div class="pt-3" style="border-top: 2px solid var(--pine)">
                                <p class="text-[12px] leading-snug font-bold text-[#0b3b36]">{{ $point->name }}</p>
                                <p class="mt-2 text-[10px] leading-relaxed text-[#5f7370]">{{ $point->address }}</p>
                                @if ($point->opening_hours)
                                    <p class="mt-1.5 text-[10px] text-[#5f7370]">{{ $point->opening_hours }}</p>
                                @endif
                                @if ($point->phone)
                                    <p class="figure mt-1.5 text-[10px] font-semibold text-[#1f2937]">{{ $point->phone }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Dampak kolektif --}}
            <section class="avoid-break mt-9">
                <h2 class="section-title text-[12px]">{{ __('result.community.heading') }}</h2>

                <p class="mt-3 max-w-[68ch] text-[12px] leading-relaxed text-[#1f2937]">{{ $report->communityIntro() }}</p>

                <p class="mt-5 text-[11px] font-semibold text-[#0b3b36]">{{ __('result.community.stats_heading') }}</p>
                <ul class="mt-2 divide-y rule-hair border-y rule-hair">
                    @foreach ($report->communityImpacts() as $impact)
                        <li class="py-2 text-[12px] text-[#1f2937]">
                            {!! ResultText::render($impact->tr('template'), ['value' => ResultText::compact($impact->value)]) !!}
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Penutup --}}
            <footer class="mt-auto border-t-2 pt-4" style="border-color: var(--pine)">
                <p class="max-w-[74ch] text-[10px] leading-relaxed text-[#5f7370]">
                    {{ __('result.report.closing', ['name' => $report->firstName()]) }}
                </p>
                <p class="figure mt-2 flex items-center justify-between gap-4 text-[10px]">
                    <span class="font-semibold text-[#0b3b36]">{{ route('calculator.result', ['uuid' => $uuid]) }}</span>
                    <span class="text-[#5f7370]">{{ __('result.report.page', ['number' => 2, 'total' => 2]) }}</span>
                </p>
            </footer>
        </div>
    </div>

    @if ($autoPrint)
        <script>
            // Dibuka dari tombol "Download Result": langsung tawarkan simpan PDF.
            addEventListener('load', () => setTimeout(() => window.print(), 300), { once: true });
        </script>
    @endif
</div>
