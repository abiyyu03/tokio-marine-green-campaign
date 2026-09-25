<?php

namespace Database\Seeders;

use App\Models\DropOffPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Isi direktori "Temukan Rumah Pilah Terdekat!" — sekarang berisi dua jenis
 * lokasi: 10 "Rumah Pilah" dari daftar awal, ditambah "Bank Sampah" dari
 * tabel "DATA BANK SAMPAH" yang dikirim tim kampanye (17 September 2026).
 * Keduanya sama-sama titik setor sampah terpilah, jadi dipakai model dan
 * tabel yang sama, dibedakan lewat namanya masing-masing.
 *
 * Foto kartunya diambil dari public/asset/images/rumah-pilah/ (nama
 * berkasnya sesuai nama lokasi), lalu dipotong 640x300 ke
 * asset/images/opt/{slug}-640.jpg + .webp supaya ringan; lokasi yang
 * fotonya belum ada tetap tampil dengan gambar pengganti.
 *
 * Jam operasional, telepon, dan nomor WhatsApp belum diberikan, jadi dibiarkan
 * null — kartu di halaman hasil menyembunyikan baris yang kosong, termasuk
 * tombol "Hubungi WhatsApp", sehingga tidak ada nomor karangan yang tayang.
 * Isi kolomnya di sini begitu datanya turun.
 *
 * Seeder ini juga menonaktifkan lokasi yang sudah tidak ada di daftar (mis.
 * baris contoh Bogor dari versi sebelumnya) — barisnya sengaja tidak dihapus
 * supaya laporan lama tetap bisa menunjuk ke lokasi yang sama.
 */
class DropOffPointSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            ['name' => 'Rumah Pilah Temugiring', 'address' => 'Jl. Temugiring RW 08 Kayu Putih'],
            ['name' => 'Rumah Pilah RKI Kayu Putih', 'address' => 'Jl. Kayu Putih Raya - Kayu Putih'],
            ['name' => 'Rumah Pilah Berkah', 'address' => 'Jl. Buna Karya I Pondok Kelapa'],
            ['name' => 'Rumah Pilah Cemara', 'address' => 'Jl. Komplek DKI RT 009/02'],
            ['name' => 'Rumah Pilah Ceria Sehat', 'address' => 'Jl. Mawar Meray III Duren Sawit'],
            ['name' => 'Rumah Pilah Hijau', 'address' => 'Rusun Penggilingan Tower E Cakung'],
            ['name' => 'Rumah Pilah PKSMS', 'address' => 'Rusun Klender RW 001'],
            ['name' => 'Rumah Pilah RW 05 Kayu Putih', 'address' => 'Jl. Logam RW 05 Kayu Putih'],
            ['name' => 'Rumah Pilah Tunas Beringin', 'address' => 'Taman Segitiga Beringin Jl. Bunga Rampai Raya 17'],
            ['name' => 'Rumah Pilah Pedaengan', 'address' => 'Kp. Pedaengan Penggilingan Cakung'],

            // "DATA BANK SAMPAH", tim kampanye (17 September 2026).
            [
                'name' => 'Bank Sampah Anugerah Alam Semesta (ASA)',
                'address' => 'Gereja Santa Anna, Jl. Laut Arafuru Blok A7/7',
                'city' => 'Duren Sawit, Jakarta Timur',
            ],
            [
                'name' => 'Bank Sampah Gunung Emas',
                'address' => 'Jl. Kamboja 3 No. 9A, RT.9/RW.11, Rawamangun, Kec. Pulo Gadung',
                'city' => 'Jakarta Timur',
            ],
            [
                'name' => 'Bank Sampah Harapan',
                // Slipi ada di Jakarta Barat, beda kota dari lokasi lain di
                // daftar ini — makanya city/province ditulis per baris,
                // bukan disamaratakan seperti sebelumnya.
                'address' => 'Jl. K.S. Tubun Raya',
                'city' => 'Slipi, Jakarta Barat',
                // Alamat teksnya ambigu — "Jl. K.S. Tubun" juga ada di
                // Kampung Melayu, jadi pencarian teks polos di mapsUrl()
                // sempat nyasar ke sana (temuan UAT-RES-04). Koordinat ini
                // dikonfirmasi lewat percakapan dengan user, 25 Sep 2026.
                'latitude' => -6.211484233536067,
                'longitude' => 106.82080613686034,
            ],
        ];

        $slugs = [];

        foreach ($points as $index => $data) {
            $slug = Str::slug($data['name']);
            $slugs[] = $slug;

            DropOffPoint::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'image_file' => "asset/images/opt/{$slug}-640.jpg",
                    'address' => $data['address'],
                    'city' => $data['city'] ?? 'Jakarta Timur',
                    'province' => 'DKI Jakarta',
                    // Kalau lat/long tersedia, DropOffPoint::mapsUrl() memakainya
                    // duluan sebelum jatuh ke pencarian teks nama+alamat yang
                    // rawan nyasar (lihat catatan di baris Bank Sampah Harapan).
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'opening_hours' => null,
                    'phone' => null,
                    'whatsapp_number' => null,
                    'maps_url' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }

        DropOffPoint::query()->whereNotIn('slug', $slugs)->update(['is_active' => false]);
    }
}
