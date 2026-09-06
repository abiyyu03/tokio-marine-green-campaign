<?php

/**
 * Konstanta kampanye yang muncul sebagai teks di halaman hasil tapi bukan
 * data master: nilainya jarang berubah dan tidak perlu tabel sendiri.
 */
return [
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

    // Jumlah kartu Rumah Pilah yang tampil sebelum tombol "Load More".
    'drop_off_page_size' => 3,

    'locales' => ['id', 'en'],
];
