{{--
    `minimal` menyembunyikan menu tengah dan tombol "Mulai Hitung Emisi",
    menyisakan logo saja. `auto-hide` membuat topbar-nya sendiri disembunyikan
    di luar layar secara default dan hanya muncul saat kursor didekatkan ke
    tepi atas — dipakai bersama di halaman kalkulator supaya peserta lebih
    fokus mengisi wizard, tapi navigasi tetap bisa dijangkau kalau perlu.
--}}
@props(['active' => null, 'minimal' => false, 'autoHide' => false])

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
    @class([
        'z-50 border-b border-slate-200 bg-white',
        'sticky top-0' => ! $autoHide,
        // Header sendiri diciutkan ke tinggi pemicunya saja (lihat strip di
        // bawah); baris navigasi yang sebenarnya jadi anak `absolute` supaya
        // tidak ikut menambah tinggi header saat disembunyikan.
        'group/topbar fixed inset-x-0 top-0' => $autoHide,
    ])
>
    @if ($autoHide)
        {{-- Jalur pemicu: strip tipis yang selalu ada di ujung atas layar,
             tempat tetikus bisa memicu topbar muncul (lihat selektor
             `.group\/topbar:hover` di bawah). Setelah topbar terbuka, mengarahkan
             tetikus ke topbar itu sendiri tetap menjaganya terbuka karena
             strip ini adalah anak dari elemen `group/topbar` yang sama. --}}
        <div class="h-2 w-full"></div>
    @endif

    <div
        @class([
            'border-b border-slate-200 bg-white' => $autoHide,
            // Selektor kurung siku dipakai (bukan utilitas group-hover: bawaan)
            // karena group-hover: Tailwind dibungkus @media (hover: hover) —
            // di perangkat yang tidak lolos deteksi itu topbar tidak akan
            // pernah muncul lagi. :focus-within ikut disertakan supaya
            // navigasi keyboard (Tab) juga bisa memunculkannya.
            'absolute inset-x-0 top-0 -translate-y-full shadow-sm transition-transform duration-300 ease-out [.group\/topbar:hover_&]:translate-y-0 [.group\/topbar:focus-within_&]:translate-y-0' => $autoHide,
        ])
    >
        <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

            {{-- 1. Logo Kiri --}}
            <a href="{{ url('/') }}" wire:navigate class="flex shrink-0 items-center">
                <img src="{{ asset('asset/images/logo.png') }}" alt="Tokio Marine, TM Life Peduli, Dompet Dhuafa"
                    class="h-10 sm:h-12 w-auto object-contain">
                <span class="sr-only">Tokio Marine Group</span>
            </a>

            {{-- 2. Menu Tengah Desktop (Menggunakan Binding Class dari Alpine) --}}
            @unless ($minimal)
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
            @endunless

            {{-- 3. Area Kanan: Tombol Hitung, Login & Bahasa --}}
            <div class="flex items-center gap-4">

                @unless ($minimal)
                <a href="{{ route('calculator') }}" wire:navigate
                    class="hidden md:inline-flex rounded-md bg-[#00AEC7] px-6 py-2.5 text-sm font-bold text-white transition hover:bg-[#0096B8]">
                    Mulai Hitung Emisi
                </a>
                @endunless

                {{-- Bahasa dimatikan sementara (lihat form GET locale.switch di
                     riwayat) — belum ada versi Inggris yang siap ditampilkan.
                     Tombol hamburger TIDAK ikut dimatikan: dulu ia ada satu
                     <div> dengan form bahasa itu, jadi mengomentari
                     keduanya sekaligus membuat menu mobile (Beranda/Tentang
                     Kami/Sektor Emisi/FAQ) tidak bisa dibuka sama sekali di
                     HP — tidak ada elemen lain yang men-toggle
                     `mobileMenuOpen`. --}}
                @unless ($minimal)
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
                @endunless
            </div>
        </div>

        {{-- Mobile Menu Dropdown --}}
        @unless ($minimal)
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
                    {{-- <a href="{{ Route::has('login') ? route('login') : url('/') }}"
                        class="block sm:hidden w-full rounded-md border border-[#00AEC7] px-5 py-2.5 text-center text-sm font-bold text-[#00AEC7] transition hover:bg-slate-50">
                        {{ __('nav.login') }}
                    </a> --}}
                </div>
            </div>
        </div>
        @endunless
    </div>
</header>
