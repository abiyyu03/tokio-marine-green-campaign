<?php

/**
 * Pesan validasi berbahasa Indonesia.
 *
 * Laravel tidak mengirim file ini secara bawaan; tanpa file ini pesan error
 * tampil apa adanya sebagai kunci mentah ("validation.required") di layar.
 * Isinya sengaja dibatasi pada aturan yang benar-benar dipakai aplikasi ini.
 *
 * Urutan yang dibaca Laravel: `custom.<field>.<rule>` dulu (kalimat khusus per
 * field), baru pesan umum di bawah dengan :attribute diisi dari `attributes`.
 */
return [
    'accepted' => 'Kolom :attribute harus dicentang.',
    'after' => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'before' => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'boolean' => 'Kolom :attribute hanya boleh bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Kolom :attribute bukan tanggal yang valid.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max digit.',
    'email' => 'Format :attribute belum benar.',
    'exists' => 'Pilihan :attribute tidak tersedia.',
    'in' => 'Pilihan :attribute tidak tersedia.',
    'integer' => 'Kolom :attribute harus berupa angka bulat.',
    'max' => [
        'array' => 'Kolom :attribute maksimal berisi :max item.',
        'file' => 'Ukuran :attribute maksimal :max kilobita.',
        'numeric' => 'Kolom :attribute maksimal bernilai :max.',
        'string' => 'Kolom :attribute maksimal :max karakter.',
    ],
    'min' => [
        'array' => 'Kolom :attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobita.',
        'numeric' => 'Kolom :attribute minimal bernilai :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'regex' => 'Format :attribute belum benar.',
    'required' => 'Kolom :attribute wajib diisi.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'unique' => 'Kolom :attribute sudah terdaftar.',
    'url' => 'Format :attribute bukan tautan yang valid.',

    /**
     * Kalimat khusus per field. Dipakai untuk hal-hal yang tidak cukup
     * dijelaskan pesan umum: contoh format, atau apa yang harus dilakukan
     * pengguna berikutnya.
     */
    'custom' => [
        'name' => [
            'required' => 'Nama lengkap wajib diisi.',
        ],
        'email' => [
            'required' => 'Email wajib diisi — laporan hasilmu dikirim ke alamat ini.',
            'email' => 'Format email belum benar. Contoh: nama@email.com',
        ],
        'whatsapp' => [
            'required' => 'Nomor WhatsApp wajib diisi.',
            'regex' => 'Nomor WhatsApp hanya boleh berisi angka, tanpa awalan +62. Contoh: 81234567890',
            'min' => 'Nomor WhatsApp minimal :min digit. Contoh: 81234567890',
            'max' => 'Nomor WhatsApp maksimal :max digit.',
        ],
        'dob' => [
            'date' => 'Tanggal lahir belum lengkap. Pilih tanggal lewat ikon kalender.',
            'before' => 'Tanggal lahir harus sebelum hari ini.',
        ],
        'gender' => [
            'in' => 'Pilih salah satu: Laki-laki atau Perempuan.',
        ],
        'intent' => [
            'in' => 'Pilih salah satu jawaban yang tersedia.',
        ],
        'consent' => [
            'accepted' => 'Centang persetujuan Syarat & Ketentuan dan Kebijakan Privasi untuk melanjutkan.',
        ],
        'password' => [
            'required' => 'Kata sandi wajib diisi.',
        ],
    ],

    /** Nama field yang dibaca manusia, mengisi :attribute di pesan umum. */
    'attributes' => [
        'name' => 'Nama Lengkap',
        'email' => 'Email',
        'whatsapp' => 'Nomor WhatsApp',
        'whatsapp_number' => 'Nomor WhatsApp',
        'dob' => 'Tanggal Lahir',
        'gender' => 'Gender',
        'intent' => 'Pilihan jawaban',
        'consent' => 'Persetujuan',
        'password' => 'Kata Sandi',
        'remember' => 'Ingat saya',
    ],
];
