@props(['active' => null])

@php
    $locale = app()->getLocale();
@endphp

<header x-data="{
    mobileMenuOpen: false,
    activeSection: '{{ $active }}',
    updateActive() {
        // Ambil elemen berdasarkan ID
        const about = document.getElementById('about');
        const sektor = document.getElementById('sektor-emisi');
        const faq = document.getElementById('faq');

        // Hanya jalankan pendeteksian scroll jika kita berada di halaman Beranda
        if (about || sektor || faq) {
            // Dapatkan jarak elemen dari atas layar
            const faqPos = faq ? faq.getBoundingClientRect().top : 9999;
            const sektorPos = sektor ? sektor.getBoundingClientRect().top : 9999;
            const aboutPos = about ? about.getBoundingClientRect().top : 9999;

            // Angka 300 adalah batas offset (titik tengah atas layar) agar lebih responsif
            if (faqPos < 300) {
                this.activeSection = 'faq';
            } else if (sektorPos < 300) {
                this.activeSection = 'sektor-emisi';
            } else if (aboutPos < 300) {
                this.activeSection = 'about';
            } else {
                this.activeSection = 'home';
            }
        }
    }
}" @scroll.window="updateActive()" x-init="updateActive()"
    class="sticky top-0 z-50 border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        {{-- 1. Logo Kiri --}}
        <a href="{{ url('/') }}" wire:navigate class="flex shrink-0 items-center">
            <img src="{{ asset('asset/images/logo.png') }}" alt="Tokio Marine, TM Life Peduli, Dompet Dhuafa"
                class="h-10 sm:h-12 w-auto object-contain">
            <span class="sr-only">Tokio Marine Group</span>
        </a>

        {{-- 2. Menu Tengah Desktop (Menggunakan Binding Class dari Alpine) --}}
        <nav class="hidden items-center gap-8 lg:flex">
            <a href="{{ url('/') }}" wire:navigate class="text-sm transition pb-1"
                :class="activeSection === 'home' ? 'font-bold text-[#00AEC7] border-b-2 border-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">Beranda</a>

            <a href="{{ url('/#about') }}" class="text-sm transition pb-1"
                :class="activeSection === 'about' ? 'font-bold text-[#00AEC7] border-b-2 border-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">Tentang
                Kami</a>

            <a href="{{ url('/#sektor-emisi') }}" class="text-sm transition pb-1"
                :class="activeSection === 'sektor-emisi' ? 'font-bold text-[#00AEC7] border-b-2 border-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">Sektor
                Emisi</a>

            <a href="{{ url('/#faq') }}" class="text-sm transition pb-1"
                :class="activeSection === 'faq' ? 'font-bold text-[#00AEC7] border-b-2 border-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">FAQ</a>
        </nav>

        {{-- 3. Area Kanan: Tombol Hitung, Login & Bahasa --}}
        <div class="flex items-center gap-4">

            <a href="{{ route('calculator') }}" wire:navigate
                class="hidden md:inline-flex rounded-md bg-[#00AEC7] px-6 py-2.5 text-sm font-bold text-white transition hover:bg-[#0096B8]">
                Mulai Hitung Emisi
            </a>

            <div class="hidden sm:block h-8 w-px bg-slate-200"></div>

            <div class="flex items-center gap-2 sm:gap-3">

                <form method="GET" action="{{ route('locale.switch') }}" class="relative">
                    <select name="locale" onchange="this.form.submit()" aria-label="{{ __('nav.language') }}"
                        class="cursor-pointer appearance-none rounded-md border border-slate-300 py-2 pr-8 pl-3 text-sm font-medium text-slate-700 focus:border-[#00AEC7] focus:ring-2 focus:ring-[#00AEC7]/20 focus:outline-none">
                        @foreach (config('carbon-calculator.locales') as $option)
                            <option value="{{ $option }}" @selected($locale === $option)>{{ strtoupper($option) }}
                            </option>
                        @endforeach
                    </select>
                    <svg class="pointer-events-none absolute top-1/2 right-2 size-4 -translate-y-1/2 text-slate-500"
                        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </form>

                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="block rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Toggle menu">
                    <svg x-show="!mobileMenuOpen" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg x-show="mobileMenuOpen" style="display: none;" class="size-6" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

            </div>
        </div>
    </div>

    {{-- Mobile Menu Dropdown --}}
    <div x-show="mobileMenuOpen" x-collapse style="display: none;"
        class="border-t border-slate-200 bg-white px-4 py-4 lg:hidden">
        <div class="flex flex-col gap-4">
            <a href="{{ url('/') }}" @click="mobileMenuOpen = false" class="block text-base transition"
                :class="activeSection === 'home' ? 'font-bold text-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">Beranda</a>

            <a href="{{ url('/#about') }}" @click="mobileMenuOpen = false" class="block text-base transition"
                :class="activeSection === 'about' ? 'font-bold text-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">Tentang
                Kami</a>

            <a href="{{ url('/#sektor-emisi') }}" @click="mobileMenuOpen = false" class="block text-base transition"
                :class="activeSection === 'sektor-emisi' ? 'font-bold text-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">Sektor
                Emisi</a>

            <a href="{{ url('/#faq') }}" @click="mobileMenuOpen = false" class="block text-base transition"
                :class="activeSection === 'faq' ? 'font-bold text-[#00AEC7]' :
                    'font-medium text-slate-600 hover:text-[#00AEC7]'">FAQ</a>

            <div class="border-t border-slate-100 pt-4 mt-2 flex flex-col gap-3">
                <a href="{{ route('calculator') }}"
                    class="block w-full rounded-md bg-[#00AEC7] px-5 py-2.5 text-center text-sm font-bold text-white transition hover:bg-[#0096B8]">
                    Mulai Hitung Emisi
                </a>
                <a href="{{ Route::has('login') ? route('login') : url('/') }}"
                    class="block sm:hidden w-full rounded-md border border-[#00AEC7] px-5 py-2.5 text-center text-sm font-bold text-[#00AEC7] transition hover:bg-slate-50">
                    {{ __('nav.login') }}
                </a>
            </div>
        </div>
    </div>
</header>
