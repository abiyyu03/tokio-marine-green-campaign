<?php

namespace Database\Seeders;

use App\Models\DropOffPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Isi direktori "Temukan Rumah Pilah Terdekat!".
 *
 * Daftar lokasi dari tim kampanye (Jakarta Timur). Jam operasional, telepon,
 * dan nomor WhatsApp belum diberikan, jadi dibiarkan null — kartu di halaman
 * hasil menyembunyikan baris yang kosong, termasuk tombol "Hubungi WhatsApp",
 * sehingga tidak ada nomor karangan yang tayang. Isi kolomnya di sini begitu
 * datanya turun.
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
        ];

        $slugs = [];

        foreach ($points as $index => $data) {
            $slug = Str::slug($data['name']);
            $slugs[] = $slug;

            DropOffPoint::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'image_file' => null,
                    'address' => $data['address'],
                    'city' => 'Jakarta Timur',
                    'province' => 'DKI Jakarta',
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
