<?php

namespace Database\Seeders;

use App\Models\DropOffPoint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Contoh isi direktori "Temukan Rumah Pilah Terdekat!".
 * Data asli akan datang dari tim kampanye; baris di sini memakai satu alamat
 * contoh yang sama seperti pada mockup dan aman untuk dihapus.
 */
class DropOffPointSeeder extends Seeder
{
    public function run(): void
    {
        $points = [
            ['name' => 'Rumah Pilah Bersama - Bogor Selatan', 'city' => 'Kota Bogor'],
            ['name' => 'Rumah Pilah Bersama - Bogor Tengah', 'city' => 'Kota Bogor'],
            ['name' => 'Rumah Pilah Bersama - Bogor Utara', 'city' => 'Kota Bogor'],
            ['name' => 'Rumah Pilah Bersama - Bogor Barat', 'city' => 'Kota Bogor'],
            ['name' => 'Rumah Pilah Bersama - Cibinong', 'city' => 'Kabupaten Bogor'],
            ['name' => 'Rumah Pilah Bersama - Depok', 'city' => 'Kota Depok'],
        ];

        foreach ($points as $index => $data) {
            DropOffPoint::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'image_file' => 'images/drop-off/rumah-pilah.jpg',
                    'address' => 'Jl. Pajajaran No. 45, Baranangsiang, '.$data['city'],
                    'city' => $data['city'],
                    'province' => 'Jawa Barat',
                    'opening_hours' => 'Senin - Sabtu (08.00 - 16.00 WIB)',
                    'phone' => '012345678910',
                    'whatsapp_number' => '012345678910',
                    'maps_url' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
