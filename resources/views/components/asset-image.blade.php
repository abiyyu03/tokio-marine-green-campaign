{{--
    Gambar dari /public dengan penampung cadangan bila filenya belum diunggah,
    supaya halaman tetap rapi meski asetnya belum ada.

    Bila di sebelah berkasnya ada versi .webp dengan nama yang sama (mis.
    opt/foto-640.jpg + opt/foto-640.webp), versi itu yang dipakai peramban dan
    JPG/PNG-nya jadi cadangan.
--}}
@props(['src' => null, 'alt' => ''])

@php
    $exists = $src && is_file(public_path($src));
    $webp = $exists ? preg_replace('/\.(jpe?g|png)$/i', '.webp', $src) : null;
    $hasWebp = $webp && $webp !== $src && is_file(public_path($webp));
@endphp

@if ($exists)
    <picture class="contents">
        @if ($hasWebp)
            <source type="image/webp" srcset="{{ asset($webp) }}">
        @endif
        <img src="{{ asset($src) }}" alt="{{ $alt }}" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'object-cover']) }}>
    </picture>
@else
    <div {{ $attributes->merge(['class' => 'grid place-items-center bg-linear-to-br from-brand-100 to-brand-200 text-brand-400']) }} role="img" aria-label="{{ $alt }}">
        <svg class="size-6" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 0 1 3.25 3h13.5A2.25 2.25 0 0 1 19 5.25v9.5A2.25 2.25 0 0 1 16.75 17H3.25A2.25 2.25 0 0 1 1 14.75v-9.5Zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 0 0 .75-.75v-2.69l-2.22-2.219a.75.75 0 0 0-1.06 0l-1.91 1.909.47.47a.75.75 0 1 1-1.06 1.06L6.53 8.091a.75.75 0 0 0-1.06 0l-2.97 2.97ZM12 7a1 1 0 1 1 2 0 1 1 0 0 1-2 0Z" clip-rule="evenodd" />
        </svg>
    </div>
@endif
