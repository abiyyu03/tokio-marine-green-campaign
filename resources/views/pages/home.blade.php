<?php

use Livewire\Component;

/**
 * Beranda Utama Tokio Marine Green Campaign.
 *
 * Judul halaman tidak ditulis lewat #[Title]: argumen atribut harus konstan
 * sehingga judulnya tidak bisa ikut berganti bahasa. Judul, deskripsi, kartu
 * WhatsApp, dan structured data halaman ini datang dari App\Support\Seo —
 * teksnya di lang/{locale}/seo.php.
 */
new class extends Component
{
    //
};
?>

<div class="flex min-h-screen flex-col bg-white font-lato">
    <x-site-header active="home" />

    <main class="w-full flex-1">
        
        <div class="mx-auto w-full max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
            {{-- ==================== HERO SECTION ==================== --}}
            <div 
                class="relative flex w-full min-h-[500px] sm:min-h-[600px] flex-col items-center justify-center overflow-hidden rounded-[2rem] bg-slate-300 bg-cover bg-center px-4 py-20 shadow-xl"
                style="background-image: url('{{ asset('asset/images/group-asian-diverse-people-volunteer-teamwork-environment-conservationvolunteer-help-picking-plastic-foam-garbage-park-areavolunteering-world-environment-day (1).jpg') }}');"
            >
                {{-- Overlay Gelap Tipis agar kontras dasarnya rata --}}
                <div class="absolute inset-0 bg-slate-900/20"></div>

                {{-- Overlay Gradient Biru (Cyan): Pekat di atas, transparan di bawah --}}
                <div class="absolute inset-0 bg-gradient-to-b from-[#00AEC7]/50 via-[#00AEC7]/35 to-transparent"></div>

                <div class="relative z-10 flex max-w-3xl flex-col items-center text-center">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-white/90 sm:text-sm">
                        Tokio Marine &times; Jaga Bumi
                    </p>

                    <h1 class="text-3xl font-black leading-tight text-white sm:text-4xl md:text-5xl">
                        Kalkulator Karbon: Hitung Jejak Karbonmu dalam 3 Menit
                    </h1>
                    
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-white/90 sm:mt-6 sm:text-base font-medium">
                        Mulai langkah nyata untuk bumi. Carbon calculator berbasis data emisi nasional untuk memahami seberapa besar dampak aktivitas harianmu terhadap lingkungan &mdash; gratis, tanpa perlu membuat akun.
                    </p>

                    <a
                        href="{{ route('calculator') }}"
                        wire:navigate
                        class="mt-8 flex items-center gap-3 rounded-lg bg-[#00AEC7] py-3 pl-6 pr-4 text-sm font-bold text-white transition hover:bg-[#0096B8] sm:text-base shadow-lg border border-white/20"
                    >
                        Hitung Skor Karbonmu untuk Lindungi Bumi!
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </a>
                </div>

                {{-- Floating Card Desktop --}}
                <div class="absolute bottom-6 left-6 z-20 hidden max-w-[320px] items-center gap-4 rounded-2xl bg-white p-4 shadow-xl sm:flex lg:bottom-10 lg:left-10">
                    <div class="h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-200">
                        <img src="{{ asset('asset/images/american-public-power-association-XGAZzyLzn18-unsplash.jpg') }}" alt="Jaringan listrik sebagai sumber jejak karbon rumah tangga" loading="lazy" decoding="async" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 leading-tight">Mengapa Ini Penting?</h3>
                        <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                            Setiap kilometer yang kamu tempuh dan setiap watt listrik yang kamu gunakan meninggalkan jejak di bumi.
                        </p>
                    </div>
                </div>

                {{-- Floating Card Mobile --}}
                <div class="relative z-10 mt-12 flex w-full max-w-xs items-center gap-4 rounded-2xl bg-white p-4 shadow-lg sm:hidden">
                    <div class="h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-200">
                        <img src="{{ asset('asset/images/american-public-power-association-XGAZzyLzn18-unsplash.jpg') }}" alt="Jaringan listrik sebagai sumber jejak karbon rumah tangga" loading="lazy" decoding="async" class="h-full w-full object-cover">
                    </div>
                    <div class="text-left">
                        <h3 class="text-sm font-bold text-slate-900 leading-tight">Mengapa Ini Penting?</h3>
                        <p class="mt-1 text-[10px] leading-relaxed text-slate-600">
                            Setiap kilometer yang tempuh dan setiap watt listrik yang digunakan meninggalkan jejak di bumi.
                        </p>
                    </div>
                </div>
            </div>

            {{-- ==================== ABOUT US SECTION ==================== --}}
            <section class="mt-16 sm:mt-24 lg:mt-32 mb-16 sm:mb-24" id="about">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="w-full">
                        <div class="rounded-3xl w-full aspect-[4/3] overflow-hidden shadow-sm">
                            <img src="{{ asset('asset/images/abdi-rahman-h-kRFzTZVN-Fc-unsplash.jpg') }}" alt="Relawan program Jaga Bumi memilah sampah bersama warga" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        </div>
                    </div>

                    <div class="flex flex-col space-y-6">
                        <div class="flex items-center gap-3 text-sm font-bold text-slate-700">
                            <span class="w-2.5 h-2.5 bg-[#00AEC7] rounded-full"></span>
                            <span>About Us</span>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 leading-tight">
                            Kenali Jejakmu. Mulai Aksimu. Ciptakan Dampaknya.
                        </h2>
                        <div class="text-slate-600 space-y-4 text-sm sm:text-base leading-relaxed text-justify sm:text-left font-medium">
                            <p>
                                Setiap hari, aktivitas yang kita lakukan—mulai dari menyalakan listrik hingga menggunakan kendaraan—turut meninggalkan jejak karbon. Mungkin terlihat kecil secara individu, tetapi ketika dilakukan bersama, dampaknya bisa menjadi besar.
                            </p>
                            <p>
                                Melalui kolaborasi Tokio Marine Life dan Dompet Dhuafa, kamu dapat menghitung dan mengenali jejak karbon dari aktivitas sehari-hari.
                            </p>
                            <p>
                                Namun, mengenali jejak hanyalah awal. Mari lanjutkan dengan aksi sederhana yang bisa dilakukan dari rumah: <strong class="font-bold text-slate-900">memilah sampah dan menyalurkan sampah terdaur ulang melalui Rumah Pilah dan Bank Sampah dalam jaringan program ini.</strong>
                            </p>
                            <p>
                                Satu langkah kecil dari setiap individu. Satu gerakan bersama untuk menciptakan dampak yang lebih besar bagi lingkungan.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- ==================== SCOPE OF CALCULATION ==================== --}}
        {{-- Menambahkan ID sektor-emisi --}}
        <section class="w-full bg-[#00AEC7] py-16 sm:py-24" id="sektor-emisi">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col items-center">
                
                <div class="flex items-center gap-3 text-sm font-bold text-white mb-4">
                    <span class="w-2.5 h-2.5 bg-white rounded-full"></span>
                    <span class="tracking-wider uppercase">SCOPE OF CALCULATION</span>
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-white text-center mb-12">
                    3 Area Utama yang Kami Hitung
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                    {{-- Card 1 --}}
                    <div class="bg-white rounded-[2rem] p-6 shadow-lg hover:-translate-y-1 transition-transform duration-300">
                        <div class="aspect-video w-full rounded-2xl bg-slate-200 mb-6 overflow-hidden">
                            <img src="{{ asset('asset/images/manki-kim-21xwHD7XZmM-unsplash.jpg') }}" alt="Transportasi darat, salah satu sektor yang dihitung kalkulator karbon" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Transportasi Darat</h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Menilai opsi kendaraan harianmu (mobil dan motor), jenis bahan bakar yang digunakan, serta perkiraan jarak tempuh harian.
                        </p>
                    </div>

                    {{-- Card 2 --}}
                    <div class="bg-white rounded-[2rem] p-6 shadow-lg hover:-translate-y-1 transition-transform duration-300">
                        <div class="aspect-video w-full rounded-2xl bg-slate-200 mb-6 overflow-hidden">
                            <img src="{{ asset('asset/images/kurasitama-2jXw1nqdGpk-unsplash.jpg') }}" alt="Daya listrik rumah tangga, salah satu sektor yang dihitung kalkulator karbon" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Daya Rumah Tangga</h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Menghitung sumber energi hunianmu (PLN atau energi terbarukan), besaran daya kWh, hingga estimasi biaya listrik per bulan.
                        </p>
                    </div>

                    {{-- Card 3 --}}
                    <div class="bg-white rounded-[2rem] p-6 shadow-lg hover:-translate-y-1 transition-transform duration-300">
                        <div class="aspect-video w-full rounded-2xl bg-slate-200 mb-6 overflow-hidden">
                            <img src="{{ asset('asset/images/lisa-anna-ZkWMfHPNWpw-unsplash.jpg') }}" alt="Peralatan rumah tangga, salah satu sektor yang dihitung kalkulator karbon" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Peralatan Rumah Tangga</h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Evaluasi penggunaan elektronik harian seperti durasi AC, jenis dan daya kulkas, hingga tipe serta intensitas pemakaian lampu.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 bg-white">
            
            {{-- ==================== HOW IT WORKS ==================== --}}
            <section class="py-16 sm:py-24">
                <div class="flex flex-col mb-10">
                    <div class="flex items-center gap-3 text-sm font-bold text-[#00AEC7] mb-2">
                        <span class="w-2.5 h-2.5 bg-[#00AEC7] rounded-full"></span>
                        <span class="tracking-wider uppercase">HOW IT WORKS</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900">
                        Cukup 5 Langkah Mudah
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    {{-- Step 1 --}}
                    <div class="bg-[#00AEC7] rounded-3xl p-6 text-white h-full flex flex-col hover:bg-[#0096B8] transition-colors">
                        <div class="bg-white text-[#00AEC7] size-12 rounded-xl flex items-center justify-center text-2xl font-black mb-5">1</div>
                        <h3 class="text-lg font-bold mb-3">Isi Kuesioner</h3>
                        <p class="text-sm text-white/90 leading-relaxed">
                            Masukkan detail sederhana seperti jenis kendaraan, tagihan listrik, dan gaya hidup.
                        </p>
                    </div>
                    {{-- Step 2 --}}
                    <div class="bg-[#00AEC7] rounded-3xl p-6 text-white h-full flex flex-col hover:bg-[#0096B8] transition-colors">
                        <div class="bg-white text-[#00AEC7] size-12 rounded-xl flex items-center justify-center text-2xl font-black mb-5">2</div>
                        <h3 class="text-lg font-bold mb-3">Kalkulasi Otomatis</h3>
                        <p class="text-sm text-white/90 leading-relaxed">
                            Sistem menghitung skor berdasarkan faktor emisi spesifik PLN dan KLHK.
                        </p>
                    </div>
                    {{-- Step 3 --}}
                    <div class="bg-[#00AEC7] rounded-3xl p-6 text-white h-full flex flex-col hover:bg-[#0096B8] transition-colors">
                        <div class="bg-white text-[#00AEC7] size-12 rounded-xl flex items-center justify-center text-2xl font-black mb-5">3</div>
                        <h3 class="text-lg font-bold mb-3">Isi Data Diri</h3>
                        <p class="text-sm text-white/90 leading-relaxed">
                            Masukkan nama dan email untuk menyimpan serta mengirimkan laporan hasil perhitunganmu.
                        </p>
                    </div>
                    {{-- Step 4 --}}
                    <div class="bg-[#00AEC7] rounded-3xl p-6 text-white h-full flex flex-col hover:bg-[#0096B8] transition-colors">
                        <div class="bg-white text-[#00AEC7] size-12 rounded-xl flex items-center justify-center text-2xl font-black mb-5">4</div>
                        <h3 class="text-lg font-bold mb-3">Lihat Hasil</h3>
                        <p class="text-sm text-white/90 leading-relaxed">
                            Ketahui kategorimu: Ringan (0-30), Sedang (31-60), atau Tinggi (61-100).
                        </p>
                    </div>
                    {{-- Step 5 --}}
                    <div class="bg-[#00AEC7] rounded-3xl p-6 text-white h-full flex flex-col hover:bg-[#0096B8] transition-colors">
                        <div class="bg-white text-[#00AEC7] size-12 rounded-xl flex items-center justify-center text-2xl font-black mb-5">5</div>
                        <h3 class="text-lg font-bold mb-3">Aksi Nyata Berdampak</h3>
                        <p class="text-sm text-white/90 leading-relaxed">
                            Dapatkan rekomendasi aksi seperti pemilahan sampah harian hingga daftar rekomendasi Bank Sampah dan Rumah Pilah terdekat.
                        </p>
                    </div>
                </div>
            </section>

            {{-- ==================== BANNER CTA ==================== --}}
            <section class="py-8">
                <div class="relative w-full rounded-[2rem] overflow-hidden min-h-[300px] flex flex-col items-center justify-center text-center px-4 py-12 bg-slate-100 shadow-md">
                    <img src="{{ asset('asset/images/group-asian-diverse-people-volunteer-teamwork-environment-conservationvolunteer-help-picking-plastic-foam-garbage-park-areavolunteering-world-environment-day (2).jpg') }}" alt="Relawan mengumpulkan sampah plastik di taman kota" class="absolute inset-0 w-full h-full object-cover">
                    
                    {{-- Overlay gelap tipis agar teks putih mudah dibaca --}}
                    <div class="absolute inset-0 bg-slate-900/40"></div>

                    <div class="relative z-10">
                        <h2 class="text-3xl sm:text-4xl font-black text-white mb-4 drop-shadow-md">
                            Ingin Tahu Di Mana Kategori Jejak Karbonmu?
                        </h2>
                        <p class="text-white/90 text-sm sm:text-base font-medium mb-8 drop-shadow-md">
                            Mulai evaluasi gaya hidupmu hari ini dan dapatkan saran aksi yang relevan.
                        </p>
                        <a
                            href="{{ route('calculator') }}"
                            wire:navigate
                            class="inline-flex items-center gap-3 rounded-lg bg-[#00AEC7] py-3 pl-6 pr-4 text-sm font-bold text-white transition hover:bg-[#0096B8] shadow-lg border border-white/20"
                        >
                            Mulai Perhitungan Gratis
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>
                </div>
            </section>

            {{-- ==================== FAQ SECTION ==================== --}}
            {{-- Menambahkan ID faq --}}
            <section class="py-16 sm:py-24 grid grid-cols-1 lg:grid-cols-[1fr_1.5fr] gap-12 lg:gap-20 items-start" id="faq">
                
                {{-- Kiri: Teks Pendahuluan FAQ --}}
                <div class="flex flex-col sticky top-24">
                    <div class="flex items-center gap-3 text-sm font-bold text-[#00AEC7] mb-4">
                        <span class="w-2.5 h-2.5 bg-[#00AEC7] rounded-full"></span>
                        <span class="tracking-wider uppercase">FAQ</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-800 leading-tight mb-6">
                        Punya Pertanyaan Seputar Kalkulator Karbon?
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed font-medium">
                        Temukan jawaban atas pertanyaan yang paling sering diajukan mengenai cara kerja perhitungan, privasi data, hingga aksi nyata yang bisa kamu lakukan.
                    </p>
                </div>

                {{-- Kanan: Accordion List.
                     Isinya dari App\Support\Seo::faq() — sumber yang sama dengan
                     structured data FAQPage di <head>, karena Google mensyaratkan
                     FAQ terstruktur sama persis dengan yang terlihat pengunjung. --}}
                <div class="flex flex-col space-y-4">
                    @foreach (App\Support\Seo::faq() as $index => $item)
                        <div x-data="{ expanded: {{ $loop->first ? 'true' : 'false' }} }" class="bg-[#f2f9f9] border border-[#d6eef0] rounded-2xl overflow-hidden transition-all duration-300">
                            <button @click="expanded = !expanded" :aria-expanded="expanded ? 'true' : 'false'" aria-controls="faq-answer-{{ $index }}" class="w-full flex items-start justify-between p-6 text-left focus:outline-none">
                                <div class="flex items-start gap-4">
                                    <svg class="size-5 text-[#00AEC7] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="text-base sm:text-lg font-bold leading-snug pr-4" :class="expanded ? 'text-[#00AEC7]' : 'text-slate-800'">{{ $item['q'] }}</h3>
                                </div>
                                <span class="shrink-0 grid place-items-center size-8 rounded-lg bg-[#00AEC7] text-white">
                                    <svg x-show="!expanded" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    <svg x-show="expanded" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" /></svg>
                                </span>
                            </button>
                            <div x-show="expanded" x-collapse id="faq-answer-{{ $index }}">
                                <div class="px-6 pb-6 pt-0 ml-9 text-sm text-slate-600 leading-relaxed font-medium">
                                    {{ $item['a'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </section>

        </div>
    </main>

    <x-site-footer />
</div>