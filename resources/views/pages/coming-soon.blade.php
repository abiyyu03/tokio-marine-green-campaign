{{--
    Halaman penahan untuk domain yang belum diluncurkan.

    Sengaja berdiri sendiri, tidak memakai layouts/app.blade.php: layout itu
    memasang <x-seo-head> yang mengiklankan judul, deskripsi, dan kartu
    WhatsApp situs sungguhan — persis yang tidak boleh tersebar selama situsnya
    belum siap. Di sini hanya ada noindex.

    Dilayani App\Http\Middleware\ComingSoon dengan status 503.
--}}
@php($brand = config('carbon-calculator.brand.name'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#00AEC7">

    <title>{{ __('coming_soon.title') }} | {{ $brand }}</title>

    <link rel="icon" href="{{ asset('asset/images/logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">

    <main class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 py-16">

        {{-- Latar: foto kampanye yang sudah diperkecil, ditutup gradasi brand --}}
        <div
            class="absolute inset-0 bg-cover bg-center"
            style="
                background-image: url('{{ asset('asset/images/opt/hero-relawan-1600.jpg') }}');
                background-image: image-set(
                    url('{{ asset('asset/images/opt/hero-relawan-1600.webp') }}') type('image/webp'),
                    url('{{ asset('asset/images/opt/hero-relawan-1600.jpg') }}') type('image/jpeg')
                );
            "
        ></div>
        <div class="absolute inset-0 bg-gradient-to-br from-[#032c35]/95 via-[#00788c]/90 to-[#00AEC7]/70"></div>

        <div class="relative z-10 w-full max-w-2xl text-center">

            {{-- Pembungkus flex, bukan mx-auto pada elemen inline: tanpa ini
                 logo dan lencana "Segera Hadir" jatuh di baris yang sama. --}}
            <div class="mb-10 flex justify-center">
                <span class="inline-flex rounded-2xl bg-white px-5 py-3 shadow-lg">
                    <img src="{{ asset('asset/images/logo.png') }}" alt="{{ $brand }}" class="h-9 w-auto sm:h-11">
                </span>
            </div>

            <p class="mb-4 inline-block rounded-full border border-white/40 px-4 py-1.5 text-[11px] font-bold tracking-[0.22em] text-white uppercase sm:text-xs">
                {{ __('coming_soon.badge') }}
            </p>

            <h1 class="text-3xl leading-tight font-black text-white sm:text-4xl md:text-5xl">
                {{ __('coming_soon.title') }}
            </h1>

            <p class="mx-auto mt-5 max-w-xl text-sm leading-relaxed font-medium text-white/90 sm:text-base">
                {{ __('coming_soon.body', ['brand' => $brand]) }}
            </p>

            <p class="mt-8 text-xs text-white/70 sm:text-sm">
                {{ __('coming_soon.note') }}
            </p>

            <div class="mt-10 border-t border-white/20 pt-6">
                <p class="text-xs text-white/70">
                    {{ __('coming_soon.contact') }}
                    <a href="mailto:marketing@tokiomarine.com" class="font-semibold text-white underline underline-offset-4 transition hover:text-white/80">
                        {{ __('coming_soon.contact_action') }}
                    </a>
                </p>
            </div>

            <p class="mt-10 text-[11px] tracking-[0.2em] text-white/50 uppercase">
                {{ config('carbon-calculator.brand.lockup') }}
            </p>
        </div>
    </main>

</body>
</html>
