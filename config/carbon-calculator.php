<?php

/**
 * Konstanta kampanye yang muncul sebagai teks di halaman hasil tapi bukan
 * data master: nilainya jarang berubah dan tidak perlu tabel sendiri.
 */
return [
    // Nama kampanye, satu sumber untuk seluruh aplikasi: judul & deskripsi SEO,
    // kartu pratinjau WhatsApp, structured data, eyebrow di beranda, kop email
    // hasil, kop laporan cetak, dan header area admin. Kalau penyebutan
    // resminya berubah, cukup ubah di sini (atau lewat .env).
    //
    // `lockup` dipakai saat kedua pihak ditulis berdampingan sebagai kolaborasi.
    'brand' => [
        'name' => env('APP_BRAND_NAME', 'Tokio Marine Jaga Bumi'),
        'lockup' => env('APP_BRAND_LOCKUP', 'Tokio Marine × Jaga Bumi'),
    ],

    // Halaman "sedang dalam pengembangan" selama situs belum diluncurkan.
    //
    // Daftarnya sengaja daftar IZIN, bukan daftar blokir: yang tidak terdaftar
    // ditahan. Kalau logikanya dibalik, setiap host yang belum terpikirkan —
    // subdomain cPanel, wildcard DNS, akses lewat alamat IP, hostname bawaan
    // server — akan menyajikan situs asli tanpa disadari.
    //
    // localhost dan 127.0.0.1 ikut diizinkan supaya pengembangan lokal dan
    // `php artisan serve` tidak pernah tertahan.
    'coming_soon' => [
        // Saklar peluncuran: isi COMING_SOON=false di .env untuk membuka
        // situs bagi semua domain. Tidak ada kode yang perlu diubah.
        'enabled' => filter_var(env('COMING_SOON', true), FILTER_VALIDATE_BOOL),

        'allowed_hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'COMING_SOON_ALLOWED_HOSTS',
                'ujicoba.jejakbumi.id,localhost,127.0.0.1',
            )),
        ))),

        // Pintu pratinjau: buka ?lihat=<kunci> sekali dari host yang ditahan,
        // lalu situs terbuka di peramban itu selama 8 jam.
        'secret' => env('COMING_SOON_SECRET'),
    ],

    // Versi teks Syarat & Ketentuan + Kebijakan Privasi yang disetujui lead.
    // Naikkan setiap kali isi dokumennya berubah.
    'consent_version' => '2026-08',

    // Blok "Dampak Kolektif Komunitas":
    // "jika 100 orang dengan profil sepertimu ..., 16 - 23 Ton CO2 dapat
    //  dihindari setiap tahunnya"
    //
    // Rentang tonnya berbeda per tier (7,5-8 | 8-16 | 16-23) dan tersimpan di
    // kolom result_tiers.community_avoided_*; angka di bawah hanya dipakai
    // bila tier belum punya nilainya.
    'community' => [
        'cohort_size' => 100,
        'avoided_ton_co2e_min' => 16,
        'avoided_ton_co2e_max' => 23,
    ],

    // Cara menentukan angka emisi yang ditayangkan di halaman hasil.
    //
    // 'tier_band' mengikuti dokumen logic Result Page: skor -> tier -> rentang
    //             ton, lalu dibagi ke sektor memakai emission_categories
    //             .emission_share (40% / 35% / 25%). Angka total, kartu sektor,
    //             dan badge tier dijamin konsisten satu sama lain.
    // 'factor'    memakai hasil hitung faktor emisi apa adanya. Pilih ini
    //             setelah faktor emisi diverifikasi dan rentang tier di
    //             result_tiers ikut disesuaikan.
    'estimation' => [
        'strategy' => env('CARBON_ESTIMATION_STRATEGY', 'tier_band'),
    ],

    // Zona waktu untuk menampilkan dan menyaring tanggal di area admin.
    // config('app.timezone') sengaja dibiarkan UTC: mengubahnya akan
    // menggeser makna seluruh kolom created_at yang sudah tersimpan.
    'display_timezone' => env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    'locales' => ['id', 'en'],
];
