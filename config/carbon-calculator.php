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
    'community' => [
        'cohort_size' => 100,
        'avoided_ton_co2e_min' => 16,
        'avoided_ton_co2e_max' => 23,
    ],

    // Jumlah kartu Rumah Pilah yang tampil sebelum tombol "Load More".
    'drop_off_page_size' => 4,

    'locales' => ['id', 'en'],
];
