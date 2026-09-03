<?php

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Kerangka Halaman Hasil (Skeleton UI).
 * Menggunakan data statis (hardcoded) untuk menyesuaikan dengan desain Figma.
 */
new #[Title('Jejak Karbon Tahunanmu | Tokio Marine Green Campaign')] class extends Component
{
    public string $uuid = '';
    public string $firstName = 'Adam'; // Diubah sesuai gambar
    public int $visibleDropOffs = 3;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
        
        // Coba ambil nama dari database jika ada, jika tidak gunakan default
        $submission = \App\Models\Submission::where('uuid', $uuid)->with('lead')->first();
        if ($submission && $submission->lead) {
            $this->firstName = explode(' ', $submission->lead->name)[0];
        }
    }

    public function loadMoreDropOffs(): void
    {
        $this->visibleDropOffs += 3;
    }
};
?>

<div class="flex min-h-screen flex-col bg-[#f4f7f9]">
    <x-site-header active="calculator" />

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header Utama --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <h1 class="text-2xl font-bold text-[#0d9488] sm:text-3xl">Jejak Karbon Tahunanmu</h1>
            <div class="flex items-center gap-3">
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-[#0d9488] bg-white px-5 py-2 text-sm font-semibold text-[#0d9488] transition hover:bg-slate-50">
                    Download Result
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                </button>
                <a href="{{ route('home') }}" wire:navigate class="rounded-lg bg-[#0d9488] px-6 py-2 text-sm font-semibold text-white transition hover:bg-teal-700">
                    Selesai
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
                            Halo, {{ $this->firstName }}! 👋
                        </h2>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold text-[#059669] bg-[#e6fbf1]">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v11.5A2.25 2.25 0 004.25 18h11.5A2.25 2.25 0 0018 15.75V4.25A2.25 2.25 0 0015.75 2H4.25zm4.03 6.28a.75.75 0 00-1.06-1.06L4.97 9.47a.75.75 0 000 1.06l2.25 2.25a.75.75 0 001.06-1.06L6.56 10l1.72-1.72zm4.5-1.06a.75.75 0 10-1.06 1.06L13.44 10l-1.72 1.72a.75.75 0 101.06 1.06l2.25-2.25a.75.75 0 000-1.06l-2.25-2.25z" clip-rule="evenodd" /></svg>
                            Climate Mover
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">Berikut adalah analisis jejak emisi tahunanmu berdasarkan data aktivitas yang telah kamu masukkan:</p>
                </div>

                {{-- 4 Kartu Emisi (Dengan Card Utama Lebih Besar) --}}
                <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1fr]">
                    
                    {{-- Kartu 1: Total (Lebih Besar) --}}
                    <div class="flex flex-col justify-between rounded-[1.25rem] bg-[#eef8f8] p-6 border border-[#d6eef0]">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[15px] font-bold text-slate-800 leading-snug">Jejak Karbon<br>Kamu</p>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold text-[#ef4444] bg-[#fee2e2]">
                                <span class="size-1.5 rounded-full bg-[#ef4444]"></span> Dampak Tinggi
                            </span>
                        </div>
                        <p class="mt-8 text-3xl font-black text-slate-900">
                            ~1,5–2 ton CO₂
                            <span class="block mt-1.5 text-sm font-medium text-slate-500">/ Tahun</span>
                        </p>
                    </div>

                    {{-- Kartu 2: Transportasi --}}
                    <div class="flex flex-col justify-between rounded-2xl bg-[#fff9ed] p-5 border border-[#ffeed5]">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold text-slate-800">Transportasi</p>
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-[#f59e0b] text-white">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H8.5a1 1 0 00-.8.4L5 11l-5.16.86a1 1 0 00-.84.99V16h3m12 0a2 2 0 100 4 2 2 0 000-4zm-12 0a2 2 0 100 4 2 2 0 000-4z"/></svg>
                            </span>
                        </div>
                        <p class="mt-6 text-xl font-bold text-[#f59e0b]">
                            ±0,6–0,8 ton CO₂
                            <span class="block mt-1 text-xs font-medium text-slate-500">/ Tahun</span>
                        </p>
                    </div>

                    {{-- Kartu 3: Listrik Rumah --}}
                    <div class="flex flex-col justify-between rounded-2xl bg-[#eefbf5] p-5 border border-[#d1f4e0]">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold text-slate-800">Listrik Rumah</p>
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-[#10b981] text-white">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </span>
                        </div>
                        <p class="mt-6 text-xl font-bold text-[#10b981]">
                            ±0,52–0,7 Ton CO₂
                            <span class="block mt-1 text-xs font-medium text-slate-500">/ Tahun</span>
                        </p>
                    </div>

                    {{-- Kartu 4: Konsumsi & Sampah --}}
                    <div class="flex flex-col justify-between rounded-2xl bg-[#fff3f3] p-5 border border-[#ffe0e0]">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-bold text-slate-800 leading-tight">Konsumsi &<br>Sampah</p>
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-[#ef4444] text-white">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </span>
                        </div>
                        <p class="mt-6 text-xl font-bold text-[#ef4444]">
                            ±0,38–0,5 Ton CO₂
                            <span class="block mt-1 text-xs font-medium text-slate-500">/ Tahun</span>
                        </p>
                    </div>

                </div>

                {{-- Kalimat Pembanding --}}
                <div class="rounded-r-xl border-l-[5px] border-[#0d9488] bg-white px-5 py-4 shadow-sm">
                    <p class="text-sm text-slate-700 leading-relaxed">
                        Halo {{ $this->firstName }}, jejak emisi tahunanmu saat ini berada di atas rata-rata per kapita masyarakat Indonesia (2 – 2,5 Ton CO₂/tahun).
                    </p>
                </div>

                {{-- Setara Dengan --}}
                <section>
                    <h3 class="text-base font-bold text-slate-900">
                        {{ $this->firstName }}, tahunnya emisi harianmu setara dengan:
                    </h3>
                    <ul class="mt-4 space-y-3">
                        <li class="flex items-center gap-3">
                            <span class="text-xl">⛽</span>
                            <span class="text-sm text-slate-700"><strong class="text-slate-900">1.600 Liter Bensin</strong> yang dikonsumsi kendaraan</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-xl">✈️</span>
                            <span class="text-sm text-slate-700"><strong class="text-slate-900">18 Kali Penerbangan</strong> domestik antarkota</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-xl">🌳</span>
                            <span class="text-sm text-slate-700">Butuh <strong class="text-slate-900">192 Pohon Dewasa</strong> selama 1 tahun penuh untuk menyerap seluruh emisimu</span>
                        </li>
                    </ul>
                </section>

                {{-- Kotak Kabar Baik & Rekomendasi --}}
                <section class="rounded-2xl bg-[#eef8f8] p-6 sm:p-8 space-y-8">
                    
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Kabar Baik Untukmu, {{ $this->firstName }}!</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-700">
                            Jejak emisi yang tinggi memberikan peluang besar bagi kamu untuk membuat perubahan berdampak signifikan. Kamu tidak perlu mengubah seluruh gaya hidupmu sekaligus, cukup mulai dari 1 kebiasaan kecil dari rumah:
                        </p>
                    </div>

                    <div class="rounded-xl border-l-[5px] border-[#0d9488] bg-white p-5 sm:p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <svg class="size-5 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                            <h3 class="text-sm font-bold text-slate-900">Rekomendasi Aksi Khusus {{ $this->firstName }}:</h3>
                        </div>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <div class="mt-1.5 size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                <span class="text-sm text-slate-700 leading-relaxed">Mulai memilah dan menyetorkan <strong class="text-slate-900">2 - 3 kg sampah/minggu</strong> (±104 - 156 kg/tahun).</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-1.5 size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                <span class="text-sm text-slate-700 leading-relaxed">Kamu berpotensi mengurangi hingga <strong class="text-slate-900">160 - 230 kg CO₂ per tahun</strong> (memotong <strong class="text-slate-900">4 - 7%</strong> dari total emisi tahunanmu!).</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Direktori Rumah Pilah --}}
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <h3 class="text-base font-bold text-slate-900">Temukan Rumah Pilah Terdekat!</h3>
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-[#0d9488] bg-white px-4 py-2 text-xs font-semibold text-[#0d9488] transition hover:bg-slate-50">
                                Lihat Lebih Banyak
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @for ($i = 0; $i < 3; $i++)
                                <article class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <div class="h-32 w-full bg-slate-200">
                                        {{-- Dummy Image Placeholder --}}
                                        <img src="https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?auto=format&fit=crop&w=400&q=80" alt="Bank Sampah" class="h-full w-full object-cover">
                                    </div>
                                    <div class="flex flex-1 flex-col gap-3 p-4">
                                        <h4 class="text-sm font-bold text-slate-900">Rumah Pilah Bersama - Bogor Selatan</h4>
                                        
                                        <div class="space-y-2 mt-2">
                                            <p class="flex items-start gap-2 text-[11px] text-slate-600">
                                                <svg class="size-3.5 shrink-0 text-[#0d9488] mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                Jl. Pajajaran No. 45, Baranangsiang, Kota Bogor
                                            </p>
                                            <p class="flex items-center gap-2 text-[11px] text-slate-600">
                                                <svg class="size-3.5 shrink-0 text-[#0d9488]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Senin - Sabtu (08.00 - 16.00 WIB)
                                            </p>
                                            <p class="flex items-center gap-2 text-[11px] text-slate-600">
                                                <svg class="size-3.5 shrink-0 text-[#0d9488]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                                012345678910
                                            </p>
                                        </div>

                                        <div class="mt-auto grid grid-cols-2 gap-2 pt-4">
                                            <button class="rounded-lg border border-[#0d9488] py-2 text-[10px] font-bold text-[#0d9488] hover:bg-slate-50">Lihat di Maps</button>
                                            <button class="rounded-lg bg-[#0d9488] py-2 text-[10px] font-bold text-white hover:bg-teal-700">Hubungi WhatsApp</button>
                                        </div>
                                    </div>
                                </article>
                            @endfor
                        </div>
                    </div>
                </section>

                {{-- Dampak Kolektif Komunitas --}}
                <section>
                    <h3 class="text-base font-bold text-slate-900">Dampak Kolektif Komunitas</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        {{ $this->firstName }}, jika <strong class="text-slate-900">100 orang</strong> dengan profil emisi sepertimu melakukan aksi memilah sampah ini, lebih dari <strong class="text-slate-900">16 - 23 Ton CO₂</strong> dapat dihindari setiap tahunnya.
                    </p>

                    <div class="mt-4 rounded-r-xl border-l-[5px] border-[#0d9488] bg-white px-6 py-5 shadow-sm">
                        <p class="text-sm font-bold text-slate-900">Tahun ini, komunitas mitra Bank Sampah telah:</p>
                        <ul class="mt-3 space-y-2">
                            <li class="flex items-center gap-3 text-sm text-slate-700">
                                <div class="size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                <span>Mengurangi <strong class="text-slate-900">25 Ton</strong> sampah langsung ke TPA</span>
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-700">
                                <div class="size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                <span>Mengolah <strong class="text-slate-900">12 Ton</strong> plastik keras</span>
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-700">
                                <div class="size-1.5 shrink-0 rounded-full bg-[#0d9488]"></div>
                                <span>Mengubah <strong class="text-slate-900">8 Ton</strong> multilayer menjadi bahan bangunan bermanfaat</span>
                            </li>
                        </ul>
                    </div>

                    <button class="mt-6 rounded-lg bg-[#0d9488] px-6 py-3 text-sm font-bold text-white transition hover:bg-teal-700">
                        Saya Mau Ikut Berkontribusi
                    </button>
                </section>
            </div>

            {{-- KOLOM KANAN: PANEL SKOR (Sticky) --}}
            <aside class="space-y-6 lg:sticky lg:top-24">
                
                {{-- Score Card (Dampak Tinggi - 71) --}}
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                    <h3 class="text-center text-sm font-bold text-slate-900">Skor Kamu</h3>

                    <div class="relative mx-auto mt-6 flex size-48 items-center justify-center">
                        <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                            <path class="text-slate-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="100, 100"/>
                            {{-- Warna Merah (Dampak Tinggi) --}}
                            <path stroke="#ef4444" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke-width="3" stroke-dasharray="71, 100" stroke-linecap="round"/>
                        </svg>
                        <div class="absolute text-center flex flex-col items-center">
                            <span class="text-4xl font-black text-slate-900">71</span>
                            <p class="text-[11px] font-medium text-slate-500 mt-1">Dampak Tinggi</p>
                        </div>
                    </div>

                    <div class="mt-8 space-y-2 pt-4">
                        <div class="flex items-center justify-center gap-2">
                            <span class="size-2.5 rounded-full bg-[#10b981]"></span>
                            <span class="text-[11px] font-medium text-slate-600">Dampak Ringan (0-30)</span>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <span class="size-2.5 rounded-full bg-[#f59e0b]"></span>
                            <span class="text-[11px] font-medium text-slate-600">Dampak Sedang (31-60)</span>
                        </div>
                        <div class="flex items-center justify-center gap-2">
                            <span class="size-2.5 rounded-full bg-[#ef4444]"></span>
                            <span class="text-[11px] font-medium text-slate-600">Dampak Tinggi (61-100)</span>
                        </div>
                    </div>
                </div>

                {{-- Breakdown Poin Dikategorikan --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-bold text-slate-900 mb-4">Total poin dikategorikan:</p>
                    <dl class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <dt class="text-[11px] text-slate-500">0-30 Point</dt>
                            <dd class="text-right text-[11px] font-bold text-slate-900">
                                Dampak Ringan
                                <span class="block font-medium text-slate-500">(~1,5-2 ton CO₂/tahun)</span>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <dt class="text-[11px] text-slate-500">31-60 Point</dt>
                            <dd class="text-right text-[11px] font-bold text-slate-900">
                                Dampak Sedang
                                <span class="block font-medium text-slate-500">(~2-3 ton CO₂/tahun)</span>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-[11px] text-slate-500">61-100 Point</dt>
                            <dd class="text-right text-[11px] font-bold text-slate-900">
                                Dampak Tinggi
                                <span class="block font-medium text-slate-500">(~3-5 ton CO₂/tahun)</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Notice Cek Email --}}
                <div class="rounded-xl bg-[#f0f9ff] p-4 border border-[#e0f2fe]">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="size-4 text-[#0284c7]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <h4 class="text-xs font-bold text-[#0284c7]">Cek Email Kamu!</h4>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        Laporan hasil perhitungan dan daftar rekomendasi Bank Sampah terdekat telah kami kirimkan ke email yang kamu daftarkan.
                    </p>
                </div>
            </aside>

        </div>
    </main>

    <x-site-footer />
</div>