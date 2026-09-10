<?php

/**
 * Teks area admin.
 *
 * Sengaja hanya versi Indonesia: APP_FALLBACK_LOCALE=id, jadi __('admin.*')
 * tetap resolve walau locale sesi sedang `en`. Area ini internal dan dipakai
 * satu tim, sehingga menggandakan seluruh kunci ke `en` tidak ada gunanya.
 *
 * `columns` dipakai bersama oleh header tabel DAN header CSV supaya keduanya
 * tidak pernah menyebut kolom yang sama dengan nama berbeda.
 */
return [
    'title' => 'Admin',
    'brand' => ':brand',

    'auth' => [
        'heading' => 'Masuk ke Admin',
        'subtitle' => 'Area internal tim kampanye.',
        'email' => 'Email',
        'password' => 'Kata sandi',
        'remember' => 'Ingat saya di perangkat ini',
        'submit' => 'Masuk',
        'processing' => 'Memeriksa...',
        'failed' => 'Email atau kata sandi tidak cocok.',
        'throttled' => 'Terlalu banyak percobaan. Coba lagi dalam :seconds detik.',
        'logout' => 'Keluar',
    ],

    'nav' => [
        'participants' => 'Peserta',
    ],

    'list' => [
        'title' => 'Peserta',
        'search_placeholder' => 'Cari nama, email, atau nomor WhatsApp',
        'search_label' => 'Cari peserta',
        'status_all' => 'Semua status',
        'tier_all' => 'Semua kategori',
        'from' => 'Dari tanggal',
        'until' => 'Sampai tanggal',
        'reset' => 'Reset filter',
        'export' => 'Unduh',
        'export_csv' => 'CSV',
        'export_xlsx' => 'Excel',
        'export_csv_title' => 'Unduh sebagai CSV (teks polos)',
        'export_xlsx_title' => 'Unduh sebagai Excel (.xlsx) — angka dan tanggal siap disortir',
        'showing' => 'Menampilkan :count dari :total peserta',
        'empty' => 'Belum ada peserta yang mengisi kalkulator.',
        'empty_filtered' => 'Tidak ada peserta yang cocok dengan filter ini.',
        'detail' => 'Detail',
        'timezone_note' => 'Seluruh waktu ditampilkan dalam :zone.',
    ],

    'columns' => [
        'uuid' => 'ID',
        'name' => 'Nama',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'status' => 'Status',
        'score' => 'Skor',
        'tier' => 'Kategori',
        'total' => 'Emisi',
        'total_ton' => 'Total (Ton CO2e)',
        'total_kg' => 'Total (kg CO2e)',
        'raw_kg' => 'Hasil faktor (kg CO2e)',
        'created_at' => 'Mulai mengisi',
        'completed_at' => 'Selesai',
        'result_email' => 'Email hasil',
        'dob' => 'Tanggal lahir',
        'gender' => 'Jenis kelamin',
        'intent' => 'Niat mengurangi',
        'locale' => 'Bahasa',
        'consented_at' => 'Waktu persetujuan',
        'consent_version' => 'Versi persetujuan',
    ],

    'detail' => [
        'back' => 'Kembali ke daftar',
        'identity' => 'Data diri',
        'identity_empty' => 'Peserta berhenti sebelum mengisi data diri.',
        'result' => 'Hasil perhitungan',
        'result_empty' => 'Belum ada hasil — pengisian belum diselesaikan.',
        'email_not_sent' => 'Belum terkirim',
        'email_resend' => 'Kirim ulang',
        'email_resending' => 'Mengirim...',
        'email_resent' => 'Email terkirim.',
        'email_resend_failed' => 'Gagal mengirim — cek storage/logs.',
        'per_category' => 'Rincian per sektor',
        'answers' => 'Jawaban lengkap',
        'answers_empty' => 'Belum ada jawaban tersimpan.',
        'question' => 'Pertanyaan',
        'answer' => 'Jawaban',
        'points' => 'Poin',
        'percentage' => 'Porsi',
        'raw_hint' => 'Angka hasil faktor emisi, bukan yang ditampilkan ke peserta.',
        'technical' => 'Data teknis',
        'ip' => 'Alamat IP',
        'user_agent' => 'Perangkat',
        'document' => 'Nomor dokumen',
        'view_result' => 'Buka halaman hasil',
        'view_report' => 'Buka laporan cetak',
        'delete' => 'Hapus data peserta',
        'delete_confirm_title' => 'Hapus data :name?',
        'delete_confirm_body' => 'Seluruh jawaban, hasil perhitungan, dan data diri peserta ini akan dihapus.',
        'delete_warning' => 'Tindakan ini permanen dan tidak bisa dibatalkan.',
        'delete_yes' => 'Ya, hapus permanen',
        'delete_cancel' => 'Batal',
        'deleted' => 'Data peserta sudah dihapus.',
    ],

    'status' => [
        'draft' => 'Belum selesai',
        'completed' => 'Selesai',
    ],

    'gender' => [
        'male' => 'Laki-laki',
        'female' => 'Perempuan',
    ],

    'intent' => [
        'belum_tahu' => 'Belum tahu',
        'mungkin' => 'Mungkin',
        'tentu' => 'Tentu, pasti',
    ],

    'empty_value' => '—',
];
