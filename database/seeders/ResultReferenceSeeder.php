<?php

namespace Database\Seeders;

use App\Models\EmissionBenchmark;
use App\Models\ResultTier;
use Illuminate\Database\Seeder;

/**
 * Data referensi halaman hasil: angka pembanding di sidebar dan
 * kategori badge berdasarkan emisi tahunan per kapita.
 *
 * Nilai benchmark diambil apa adanya dari desain. Catatan: ASEAN (7,8) tampil
 * lebih tinggi daripada rata-rata Global (6,26) — perlu dikonfirmasi ke tim data.
 */
class ResultReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $benchmarks = [
            ['code' => 'global',    'value' => 6.26, 'sort_order' => 1,
                'id' => 'Rata-rata Global',    'en' => 'Global Average'],
            ['code' => 'asean',     'value' => 7.80, 'sort_order' => 2,
                'id' => 'Rata-rata Asean',    'en' => 'ASEAN Average'],
            ['code' => 'indonesia', 'value' => 3.62, 'sort_order' => 3,
                'id' => 'Rata-rata Indonesia', 'en' => 'Indonesia Average'],
        ];

        foreach ($benchmarks as $data) {
            $benchmark = EmissionBenchmark::updateOrCreate(
                ['code' => $data['code']],
                [
                    'value' => $data['value'],
                    'unit' => 'tCO2e/capita/year',
                    'source' => 'PLACEHOLDER - perlu verifikasi',
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach (['id', 'en'] as $locale) {
                $benchmark->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['label' => $data[$locale]]
                );
            }
        }

        // Rentang setengah terbuka: min <= nilai < max.
        // Batas 3,62 dan 6,26 sengaja disamakan dengan benchmark di atas.
        $tiers = [
            [
                'code' => 'champion', 'min' => null, 'max' => 2.00,
                'badge_icon' => 'leaf', 'color' => '#00A0AF', 'sort_order' => 1,
                'translations' => [
                    'id' => [
                        'badge_label' => 'Champion of the Earth',
                        'headline' => 'Kamu adalah Pahlawan Hijau, :name!',
                        'description' => 'Jejak karbonmu jauh di bawah rata-rata nasional. Terima kasih sudah menjaga bumi tetap sejuk dengan pilihan hidupmu yang luar biasa.',
                    ],
                    'en' => [
                        'badge_label' => 'Champion of the Earth',
                        'headline' => 'You Are a Green Hero, :name!',
                        'description' => 'Your carbon footprint is far below the national average. Thank you for keeping the planet cool through your remarkable lifestyle choices.',
                    ],
                ],
            ],
            [
                'code' => 'guardian', 'min' => 2.00, 'max' => 3.62,
                'badge_icon' => 'seedling', 'color' => '#3DBEA3', 'sort_order' => 2,
                'translations' => [
                    'id' => [
                        'badge_label' => 'Sahabat Bumi',
                        'headline' => 'Langkahmu Sudah Tepat, :name!',
                        'description' => 'Jejak karbonmu masih di bawah rata-rata Indonesia. Sedikit penyesuaian lagi dan kamu bisa menembus level tertinggi.',
                    ],
                    'en' => [
                        'badge_label' => 'Friend of the Earth',
                        'headline' => 'You Are on the Right Track, :name!',
                        'description' => 'Your footprint is still below the Indonesian average. A few more adjustments and you can reach the highest level.',
                    ],
                ],
            ],
            [
                'code' => 'balanced', 'min' => 3.62, 'max' => 6.26,
                'badge_icon' => 'scale', 'color' => '#F5C518', 'sort_order' => 3,
                'translations' => [
                    'id' => [
                        'badge_label' => 'Penjaga Seimbang',
                        'headline' => 'Masih Ada Ruang untuk Bertumbuh, :name!',
                        'description' => 'Jejak karbonmu berada di atas rata-rata Indonesia namun di bawah rata-rata global. Beberapa kebiasaan kecil bisa menurunkannya.',
                    ],
                    'en' => [
                        'badge_label' => 'Balanced Guardian',
                        'headline' => 'There Is Room to Grow, :name!',
                        'description' => 'Your footprint sits above the Indonesian average but below the global one. A few small habits could bring it down.',
                    ],
                ],
            ],
            [
                'code' => 'action_needed', 'min' => 6.26, 'max' => null,
                'badge_icon' => 'alert', 'color' => '#E4572E', 'sort_order' => 4,
                'translations' => [
                    'id' => [
                        'badge_label' => 'Saatnya Beraksi',
                        'headline' => 'Ayo Mulai Perubahan, :name!',
                        'description' => 'Jejak karbonmu berada di atas rata-rata global. Kabar baiknya, perubahan kecil yang konsisten bisa memberi dampak besar.',
                    ],
                    'en' => [
                        'badge_label' => 'Time to Act',
                        'headline' => 'Let Us Start the Change, :name!',
                        'description' => 'Your footprint is above the global average. The good news: small, consistent changes can make a big difference.',
                    ],
                ],
            ],
        ];

        foreach ($tiers as $data) {
            $tier = ResultTier::updateOrCreate(
                ['code' => $data['code']],
                [
                    'min_ton_co2e' => $data['min'],
                    'max_ton_co2e' => $data['max'],
                    'badge_icon' => $data['badge_icon'],
                    'color' => $data['color'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($data['translations'] as $locale => $attributes) {
                $tier->translations()->updateOrCreate(['locale' => $locale], $attributes);
            }
        }
    }
}
