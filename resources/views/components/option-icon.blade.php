{{--
    Ikon garis 24x24 untuk kartu wizard: kartu moda transportasi dan lingkaran
    penanda langkah. Dipisah dari <x-emission-icon> karena yang itu ikon padat
    20x20 untuk halaman hasil.

    `name` diisi kolom `icon` di emission_categories / emission_field_options,
    sehingga menambah opsi baru cukup menambah satu entri di peta ini.
--}}
@props(['name' => null])

@php
    $paths = [
        'car' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H8.5a1 1 0 00-.8.4L5 11l-5.16.86a1 1 0 00-.84.99V16h3m12 0a2 2 0 100 4 2 2 0 000-4zm-12 0a2 2 0 100 4 2 2 0 000-4z"/>',
        'motorcycle' => '<circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/><path d="M14 17h-4m-3.5-2.5L10 8h4l1.5 3H20l-1.5 3H17"/>',
        'car-electric' => '<path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H8.5a1 1 0 00-.8.4L5 11l-5.16.86a1 1 0 00-.84.99V16h3m12 0a2 2 0 100 4 2 2 0 000-4zm-12 0a2 2 0 100 4 2 2 0 000-4z"/><path d="M3 10v4h2"/><path d="M5 12h2l1-2"/>',
        'motorcycle-electric' => '<circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/><path d="M14 17h-4m-3.5-2.5L10 8h4l1.5 3H20l-1.5 3H17"/><path d="M3 14v-2h2l1-2"/>',
        'bus' => '<path d="M4 10h16M4 14h16m-2 4H6a2 2 0 01-2-2V8a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2z"/><path d="M8 22L6 18M16 22l2-4"/>',
        'shuffle' => '<path d="M4 8h10M4 12h10m4-4h2M18 12h2M6 16h12"/>',
        'bolt' => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',
        'recycle' => '<path d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 8V11m0-5.5v-1a1.5 1.5 0 113 0v1m0 4V11"/>',
    ];
    $path = $paths[$name] ?? $paths['recycle'];
@endphp

<svg {{ $attributes->merge(['class' => 'size-6']) }} viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true">{!! $path !!}</svg>
