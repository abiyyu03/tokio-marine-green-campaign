<?php

/**
 * Teks email otomatis. Bahasanya mengikuti `locale` yang tersimpan di
 * submission, bukan bahasa yang sedang dipakai admin atau server.
 */
return [
    'result' => [
        'subject' => 'Hasil jejak karbonmu sudah siap, :name!',
        'preheader' => 'Skor, estimasi emisi tahunan, dan tautan untuk membuka atau mengunduh laporanmu.',

        'brand' => 'Tokio Marine Green Campaign',
        'greeting' => 'Halo, :name! 👋',
        'intro' => 'Terima kasih sudah menghitung jejak karbonmu. Ini ringkasan hasilnya:',

        'score_label' => 'Skor kamu',
        'total_label' => 'Estimasi emisi tahunan',
        'ton_unit' => 'Ton CO₂ / tahun',

        'recommendation_heading' => 'Rekomendasi aksi untukmu:',

        'cta_result' => 'Lihat Hasil Lengkap',
        'cta_report' => 'Unduh Laporan (PDF)',
        'cta_help' => 'Tombol tidak bisa diklik? Salin tautan ini ke browser:',

        'download_hint' => 'Tautan "Unduh Laporan" membuka lembar laporanmu lalu menampilkan dialog cetak — pilih :action untuk menyimpannya sebagai berkas.',
        'download_hint_action' => 'Save as PDF',
        'keep_link' => 'Simpan email ini. Tautan di atas adalah satu-satunya cara membuka kembali hasilmu, dan berlaku selama kampanye berjalan.',
        'private_link' => 'Tautan ini bersifat pribadi — siapa pun yang memilikinya bisa melihat hasilmu, jadi bagikan seperlunya saja.',

        'footer_auto' => 'Email ini dikirim otomatis, mohon tidak membalas.',
        'footer_reason' => 'Kamu menerima email ini karena mengisi kalkulator karbon di :app.',
    ],
];
