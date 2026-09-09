<?php

/**
 * Teks yang dibaca mesin pencari dan pratinjau tautan (WhatsApp, LinkedIn,
 * Facebook, X). Dikumpulkan di satu tempat supaya judul di tab browser,
 * judul di hasil pencarian, dan judul di kartu WhatsApp tidak bisa berbeda.
 *
 * Panduan panjang yang dipakai di bawah:
 * - title       : <= 60 karakter, kata kunci di depan, brand di belakang.
 * - description : 140-160 karakter; ini yang muncul sebagai dua baris teks di
 *                 kartu WhatsApp, jadi tulis sebagai ajakan, bukan ringkasan.
 * - share_title : lebih pendek dari title karena WhatsApp memotong lebih awal.
 */
return [
    'site_name' => 'Tokio Marine Jaga Bumi',

    'default' => [
        'title' => 'Kalkulator Karbon Tokio Marine Jaga Bumi',
        'description' => 'Hitung jejak karbon harianmu dalam 3 menit lewat kalkulator karbon Tokio Marine Jaga Bumi, lalu mulai aksi nyata dari rumah.',
        'keywords' => 'tokio marine jaga bumi, jaga bumi, kalkulator karbon, carbon calculator, jejak karbon, hitung emisi karbon, kalkulator emisi karbon, bank sampah',
    ],

    'home' => [
        'title' => 'Kalkulator Karbon | Tokio Marine Jaga Bumi',
        'share_title' => 'Kalkulator Karbon Tokio Marine Jaga Bumi',
        'description' => 'Kalkulator karbon Tokio Marine Jaga Bumi: hitung jejak karbon dari transportasi, listrik, dan sampah rumah tanggamu dalam 3 menit — gratis, tanpa daftar akun.',
        'keywords' => 'tokio marine jaga bumi, jaga bumi, kalkulator karbon, carbon calculator, hitung jejak karbon, kalkulator jejak karbon indonesia, emisi karbon harian',
    ],

    'calculator' => [
        'title' => 'Hitung Jejak Karbonmu | Kalkulator Karbon Jaga Bumi',
        'share_title' => 'Hitung Jejak Karbonmu — Jaga Bumi',
        'description' => 'Jawab pertanyaan singkat soal transportasi, listrik rumah, dan kebiasaan memilah sampah. Skor jejak karbon tahunanmu keluar di akhir, lengkap rekomendasi aksinya.',
        'keywords' => 'kalkulator karbon, carbon calculator, hitung jejak karbon, kalkulator emisi, tokio marine jaga bumi',
    ],

    /**
     * Halaman hasil dan laporan memakai uuid peserta dan berisi data pribadi:
     * keduanya tidak boleh masuk indeks mesin pencari. Judulnya tetap diisi
     * karena tautan hasil sering dibagikan lewat WhatsApp oleh pesertanya
     * sendiri, dan kartu pratinjau yang kosong terlihat seperti tautan palsu.
     */
    'result' => [
        'title' => 'Hasil Jejak Karbon Tahunanmu | Tokio Marine Jaga Bumi',
        'share_title' => 'Hasil Jejak Karbonku',
        'description' => 'Ini hasil perhitungan jejak karbon tahunanku di kalkulator karbon Tokio Marine Jaga Bumi. Hitung punyamu juga, cuma butuh 3 menit.',
    ],

    'report' => [
        'title' => 'Laporan Jejak Karbon | Tokio Marine Jaga Bumi',
        'share_title' => 'Laporan Jejak Karbon',
        'description' => 'Laporan lengkap hasil perhitungan jejak karbon tahunan dari kalkulator karbon Tokio Marine Jaga Bumi.',
    ],

    'admin' => [
        'title' => 'Admin | Tokio Marine Jaga Bumi',
        'description' => '',
    ],

    /** Teks alternatif gambar pratinjau; ikut dibaca pembaca layar. */
    'image_alt' => 'Kalkulator Karbon Tokio Marine Jaga Bumi — hitung jejak karbonmu dalam 3 menit',

    /**
     * Isi blok FAQ di beranda.
     *
     * Sumber tunggal: bagian FAQ halaman dan structured data FAQPage sama-sama
     * membaca array ini. Google mensyaratkan isi FAQ terstruktur sama persis
     * dengan yang terlihat pengunjung — dua salinan terpisah pasti bergeser.
     */
    'faq' => [
        [
            'q' => 'Apakah hasil perhitungan kalkulator karbon ini 100% akurat?',
            'a' => 'Perhitungan di kalkulator ini bersifat estimasi berdasarkan standar faktor emisi rata-rata (seperti standar IPCC dan US EPA). Hasil ini dirancang untuk memberikan gambaran umum dan kesadaran mengenai besaran jejak emisi harianmu, bukan sebagai angka pengukuran absolut.',
        ],
        [
            'q' => 'Mengapa variabel yang dihitung mencakup transportasi darat, daya, dan peralatan rumah tangga?',
            'a' => 'Ketiga sektor ini merupakan kontributor utama emisi harian tingkat individu. Mengukur konsumsi BBM/jarak tempuh, daya listrik hunian, serta intensitas alat elektronik membantu kita memetakan potensi penghematan energi secara lebih efektif.',
        ],
        [
            'q' => 'Bagaimana jika saya menggunakan kendaraan listrik (EV) atau panel surya di rumah?',
            'a' => 'Kalkulator telah menyesuaikan faktor emisi untuk opsi ramah lingkungan. Penggunaan kendaraan listrik atau sumber energi terbarukan akan menghasilkan estimasi emisi yang jauh lebih rendah dibandingkan penggunaan BBM atau listrik PLN konvensional.',
        ],
        [
            'q' => 'Apakah data pribadi yang saya masukkan saat pengisian aman?',
            'a' => 'Sangat aman. Data yang kamu masukkan hanya digunakan untuk keperluan kalkulasi emisi dan personalisasi rekomendasi pada result page. Kami menjaga privasi pengguna dan tidak pernah membagikan data pribadi kamu kepada pihak ketiga.',
        ],
        [
            'q' => 'Apa yang bisa saya lakukan setelah mengetahui hasil estimasi emisi karbon saya?',
            'a' => 'Pada result page, kamu akan mendapatkan rekomendasi aksi nyata sesuai kategori dampakmu (ringan, sedang, atau tinggi), seperti saran pemilahan sampah harian hingga lokasi Rumah Pilah/Bank Sampah terdekat untuk mulai menekan sisa emisi harian.',
        ],
    ],
];
