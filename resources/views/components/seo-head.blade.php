{{--
    Seluruh tag <head> yang dibaca mesin pencari dan pratinjau tautan.

    Sumbernya App\Support\Seo (teksnya di lang/{locale}/seo.php), jadi judul di
    tab browser, judul di hasil pencarian, dan judul di kartu WhatsApp selalu
    berasal dari satu tempat.

    Catatan WhatsApp: yang dibacanya hanya og:title, og:description, dan
    og:image — bukan twitter:*, bukan pula <title>. Gambarnya harus URL
    absolut dan sebaiknya di bawah ~300 KB, kalau tidak kartunya tampil tanpa
    gambar.

    @props(['title']) — judul dari atribut #[Title] komponen Livewire, dipakai
    kalau ada; halaman yang terdaftar di Seo memakai judul terjemahannya.
--}}
@props(['title' => null])

@php($seo = App\Support\Seo::current())
@php($pageTitle = $title ?: $seo->title())

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $seo->description() }}">
<meta name="keywords" content="{{ $seo->keywords() }}">
<link rel="canonical" href="{{ $seo->canonical() }}">

@if ($seo->noindex())
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large">
@endif

{{-- Open Graph: dipakai WhatsApp, Facebook, LinkedIn, Telegram --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $seo->siteName() }}">
<meta property="og:locale" content="{{ $seo->ogLocale() }}">
<meta property="og:url" content="{{ $seo->canonical() }}">
<meta property="og:title" content="{{ $seo->shareTitle() }}">
<meta property="og:description" content="{{ $seo->description() }}">
<meta property="og:image" content="{{ $seo->image() }}">
<meta property="og:image:secure_url" content="{{ $seo->image() }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seo->imageAlt() }}">

{{-- X/Twitter memakai namespace sendiri dan mengabaikan og:* untuk kartunya --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->shareTitle() }}">
<meta name="twitter:description" content="{{ $seo->description() }}">
<meta name="twitter:image" content="{{ $seo->image() }}">
<meta name="twitter:image:alt" content="{{ $seo->imageAlt() }}">

<meta name="theme-color" content="#00AEC7">

<script type="application/ld+json">
    {!! json_encode($seo->structuredData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
