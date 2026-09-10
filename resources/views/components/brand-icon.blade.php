{{--
    Ikon bergambar dari tim desain (public/asset/Icon), pengganti ikon garis
    generik <x-option-icon> / <x-emission-icon> di tiga tempat yang asetnya
    tersedia: kartu moda transportasi, lingkaran penanda langkah, dan kartu
    sektor di halaman hasil.

    `name` diisi kolom `icon` di emission_categories / emission_field_options —
    sama seperti komponen ikon lainnya, jadi menambah opsi baru cukup lewat
    seeder. Kalau nama itu belum punya aset, komponen ini jatuh kembali ke ikon
    SVG lama, sehingga kategori atau opsi baru tidak pernah tampil kosong.

    @props:
      set   - 'option' (kartu moda), 'step' (penanda langkah), 'result' (kartu sektor)
      name  - nilai kolom icon
      state - hanya untuk set 'step': active | passed | disabled
--}}
@props([
    'set' => 'option',
    'name' => null,
    'state' => 'active',
])

@php
    $files = [
        // Kartu moda transportasi: gambar teal, sudah punya lingkaran pucat
        // sendiri, jadi dipakai tanpa latar tambahan.
        'option' => [
            'car' => 'icon-mobil.png',
            'motorcycle' => 'icon-motor.png',
            'car-electric' => 'icon-mobilEv.png',
            'motorcycle-electric' => 'icon-motorListrik.png',
            'bus' => 'icon-transum.png',
            'shuffle' => 'icon-kombinasi.png',
        ],

        // Penanda langkah: satu berkas per keadaan, karena warnanya sudah
        // dibakar di gambar (putih di atas lingkaran teal, teal di atas
        // lingkaran putih, abu untuk langkah yang belum dibuka).
        //
        // Dua berkas bertanda "derived/" dibuat dari saudaranya karena tim
        // desain belum mengirimkannya; begitu aslinya datang, ganti dua baris
        // ini saja.
        'step' => [
            'car' => [
                'active' => 'Transportasi - active.png',
                'passed' => 'Transportasi - passed.png',
                'disabled' => 'derived/transportasi-disabled.png',
            ],
            'bolt' => [
                'active' => 'listrik - active.png',
                'passed' => 'listrik - passed.png',
                'disabled' => 'listrik - disabled.png',
            ],
            'recycle' => [
                // Berkas kiriman "konsumsi - active.png" berwarna teal seperti
                // ikon 'passed' kategori lain, jadi dipakai sebagai 'passed';
                // versi putihnya diturunkan untuk keadaan 'active'.
                'active' => 'derived/konsumsi-active-putih.png',
                'passed' => 'konsumsi - active.png',
                'disabled' => 'konsumsi - disabled.png',
            ],
        ],

        // Kartu sektor halaman hasil: ikon putih di atas kotak berwarna aksen.
        'result' => [
            'car' => 'result page/transportasi.png',
            'bolt' => 'result page/listrik.png',
            'recycle' => 'result page/konsumsi & sampah.png',
        ],
    ];

    $file = $set === 'step'
        ? ($files['step'][$name][$state] ?? null)
        : ($files[$set][$name] ?? null);

    // Nama berkas kiriman desain mengandung spasi dan "&", jadi tiap ruas
    // dikodekan sebelum jadi URL.
    $src = $file
        ? asset('asset/Icon/'.collect(explode('/', $file))->map(fn ($part) => rawurlencode($part))->implode('/'))
        : null;
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        alt=""
        aria-hidden="true"
        {{-- Berkasnya 1-3 KB dan semuanya di paruh atas layar; lazy-load hanya
             menambah kedip tanpa menghemat apa pun. --}}
        decoding="async"
        {{ $attributes->merge(['class' => 'object-contain']) }}
    >
@elseif ($set === 'result')
    <x-emission-icon :name="$name" {{ $attributes }} />
@else
    <x-option-icon :name="$name" {{ $attributes }} />
@endif
