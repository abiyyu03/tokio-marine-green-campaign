{{--
    Foto halaman depan dalam ukuran yang wajar.

    Berkas aslinya dari fotografer berukuran 1,5-14 MB dan 6000 px — kalau
    dipasang apa adanya, pengunjung mobile menunggu belasan detik dan Core Web
    Vitals halaman ini jeblok. Komponen ini menunjuk ke hasil olahan di
    public/asset/images/opt: dua lebar per foto, masing-masing WebP (dipakai
    hampir semua peramban) dengan JPG sebagai cadangan.

    `name`   nama berkas tanpa lebar dan ekstensi, mis. 'sektor-daya'
    `widths` lebar yang tersedia — harus cocok dengan berkas di folder opt/
    `sizes`  lebar tampil di layar, supaya peramban memilih berkas terkecil
             yang masih tajam
    `eager`  true untuk gambar yang terlihat tanpa menggulir

    Atribut lain (class, dll.) menempel di <img>; <picture> sengaja memakai
    `contents` supaya tidak ikut mengubah tata letak yang sudah ada.
--}}
@props([
    'name',
    'alt' => '',
    'widths' => [800, 400],
    'sizes' => '100vw',
    'eager' => false,
])

@php
    $dir = 'asset/images/opt/';
    $widths = collect($widths)->sortDesc()->values();

    $srcset = fn (string $ext) => $widths
        ->map(fn ($w) => asset($dir.$name.'-'.$w.'.'.$ext).' '.$w.'w')
        ->implode(', ');

    $fallback = $dir.$name.'-'.$widths->first().'.jpg';

    // Ukuran asli dipasang di <img> supaya tidak ada pergeseran tata letak
    // saat gambarnya baru tiba.
    $path = public_path($fallback);
    $dimensions = is_file($path) ? @getimagesize($path) : null;
@endphp

<picture class="contents">
    <source type="image/webp" srcset="{{ $srcset('webp') }}" sizes="{{ $sizes }}">
    <img
        src="{{ asset($fallback) }}"
        srcset="{{ $srcset('jpg') }}"
        sizes="{{ $sizes }}"
        alt="{{ $alt }}"
        @if ($dimensions) width="{{ $dimensions[0] }}" height="{{ $dimensions[1] }}" @endif
        loading="{{ $eager ? 'eager' : 'lazy' }}"
        @if ($eager) fetchpriority="high" @endif
        decoding="async"
        {{ $attributes->merge(['class' => 'object-cover']) }}
    >
</picture>
