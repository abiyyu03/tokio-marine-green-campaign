<?php

namespace Database\Seeders;

use App\Models\CommunityImpact;
use App\Models\EmissionBenchmark;
use App\Models\EmissionCategory;
use App\Models\EmissionEquivalence;
use App\Models\Recommendation;
use App\Models\ResultTier;
use Illuminate\Database\Seeder;

/**
 * Data referensi halaman hasil: tier skor, angka pembanding, blok "setara
 * dengan", rekomendasi aksi, dan statistik dampak kolektif komunitas.
 *
 * Template terjemahan memakai penanda **tebal** dan placeholder :value / :name
 * yang dirender oleh App\Support\ResultText.
 */
class ResultReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = $this->seedTiers();
        $this->seedBenchmarks();
        $this->seedEquivalences();
        $this->seedRecommendations($tiers);
        $this->seedCommunityImpacts();
    }

    /** @return array<string, ResultTier> */
    private function seedTiers(): array
    {
        $definitions = [
            [
                'code' => 'ringan', 'min_score' => 0, 'max_score' => 30,
                'approx_min' => 1.2, 'approx_max' => 2.0,
                'badge_icon' => 'leaf', 'color' => '#22C55E', 'sort_order' => 1,
                'translations' => [
                    'id' => [
                        'label' => 'Dampak Ringan',
                        'badge_label' => 'Climate Hero',
                        'headline' => 'Kerja Bagus, :name!',
                        'description' => 'Jejak emisimu sudah tergolong ringan. Pertahankan kebiasaan baik ini, dan ajak satu orang terdekat untuk ikut memulainya:',
                    ],
                    'en' => [
                        'label' => 'Low Impact',
                        'badge_label' => 'Climate Hero',
                        'headline' => 'Great Work, :name!',
                        'description' => 'Your footprint is already low. Keep these habits going, and bring one person close to you along:',
                    ],
                ],
            ],
            [
                'code' => 'sedang', 'min_score' => 31, 'max_score' => 60,
                'approx_min' => 2.0, 'approx_max' => 3.0,
                'badge_icon' => 'seedling', 'color' => '#F59E0B', 'sort_order' => 2,
                'translations' => [
                    'id' => [
                        'label' => 'Dampak Sedang',
                        'badge_label' => 'Climate Shifter',
                        'headline' => 'Sedikit Lagi, :name!',
                        'description' => 'Jejak emisimu berada di tengah. Satu atau dua kebiasaan baru sudah cukup untuk menurunkannya ke level ringan:',
                    ],
                    'en' => [
                        'label' => 'Moderate Impact',
                        'badge_label' => 'Climate Shifter',
                        'headline' => 'Almost There, :name!',
                        'description' => 'Your footprint sits in the middle. One or two new habits are enough to bring it down to the low tier:',
                    ],
                ],
            ],
            [
                'code' => 'tinggi', 'min_score' => 61, 'max_score' => 100,
                'approx_min' => 3.0, 'approx_max' => 5.0,
                'badge_icon' => 'bolt', 'color' => '#EF4444', 'sort_order' => 3,
                'translations' => [
                    'id' => [
                        'label' => 'Dampak Tinggi',
                        'badge_label' => 'Climate Mover',
                        'headline' => 'Kabar Baik Untukmu, :name!',
                        'description' => 'Jejak emisi yang tinggi memberikan peluang besar bagi kamu untuk membuat perubahan berdampak signifikan. Kamu tidak perlu mengubah seluruh gaya hidupmu sekaligus, cukup mulai dari 1 kebiasaan kecil dari rumah:',
                    ],
                    'en' => [
                        'label' => 'High Impact',
                        'badge_label' => 'Climate Mover',
                        'headline' => 'Good News for You, :name!',
                        'description' => 'A high footprint means a big opportunity to make a real difference. You do not need to change your whole lifestyle at once, just start with one small habit at home:',
                    ],
                ],
            ],
        ];

        $tiers = [];

        foreach ($definitions as $data) {
            $tier = ResultTier::updateOrCreate(
                ['code' => $data['code']],
                [
                    'min_score' => $data['min_score'],
                    'max_score' => $data['max_score'],
                    'approx_min_ton_co2e' => $data['approx_min'],
                    'approx_max_ton_co2e' => $data['approx_max'],
                    'badge_icon' => $data['badge_icon'],
                    'color' => $data['color'],
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach ($data['translations'] as $locale => $attributes) {
                $tier->translations()->updateOrCreate(['locale' => $locale], $attributes);
            }

            $tiers[$data['code']] = $tier;
        }

        return $tiers;
    }

    private function seedBenchmarks(): void
    {
        $benchmarks = [
            [
                'code' => 'indonesia', 'value' => 2.00, 'max_value' => 2.50,
                'is_primary' => true, 'sort_order' => 1,
                'id' => 'rata-rata masyarakat Indonesia',
                'en' => 'the Indonesian average',
            ],
            [
                'code' => 'global', 'value' => 6.26, 'max_value' => null,
                'is_primary' => false, 'sort_order' => 2,
                'id' => 'rata-rata global',
                'en' => 'the global average',
            ],
            [
                'code' => 'asean', 'value' => 7.80, 'max_value' => null,
                'is_primary' => false, 'sort_order' => 3,
                'id' => 'rata-rata ASEAN',
                'en' => 'the ASEAN average',
            ],
        ];

        foreach ($benchmarks as $data) {
            $benchmark = EmissionBenchmark::updateOrCreate(
                ['code' => $data['code']],
                [
                    'value' => $data['value'],
                    'max_value' => $data['max_value'],
                    'unit' => 'tCO2e/capita/year',
                    'is_primary' => $data['is_primary'],
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
    }

    /**
     * Blok "setiap tahunnya emisi harianmu setara dengan".
     * Angkanya sengaja dipilih agar konsisten dengan contoh di desain:
     * 3,8 ton -> 1.645 liter bensin, 18 penerbangan, 192 pohon.
     */
    private function seedEquivalences(): void
    {
        $items = [
            [
                'code' => 'bensin', 'kg_per_unit' => 2.310000, 'icon' => 'fuel', 'sort_order' => 1,
                'id' => '**:value Liter Bensin** yang dikonsumsi kendaraan',
                'en' => '**:value litres of petrol** burned by a vehicle',
            ],
            [
                'code' => 'penerbangan', 'kg_per_unit' => 211.000000, 'icon' => 'plane', 'sort_order' => 2,
                'id' => '**:value Kali Penerbangan** domestik antarkota',
                'en' => '**:value domestic flights** between cities',
            ],
            [
                'code' => 'pohon', 'kg_per_unit' => 19.800000, 'icon' => 'tree', 'sort_order' => 3,
                'id' => 'Butuh **:value Pohon Dewasa** selama 1 tahun penuh untuk menyerap seluruh emisimu',
                'en' => 'It takes **:value mature trees** a full year to absorb all of your emissions',
            ],
        ];

        foreach ($items as $data) {
            $equivalence = EmissionEquivalence::updateOrCreate(
                ['code' => $data['code']],
                [
                    'kg_co2e_per_unit' => $data['kg_per_unit'],
                    'decimals' => 0,
                    'icon' => $data['icon'],
                    'source' => 'PLACEHOLDER - perlu verifikasi',
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach (['id', 'en'] as $locale) {
                $equivalence->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['template' => $data[$locale]]
                );
            }
        }
    }

    /** @param array<string, ResultTier> $tiers */
    private function seedRecommendations(array $tiers): void
    {
        $categories = EmissionCategory::pluck('id', 'code');

        $items = [
            [
                'code' => 'pilah_setor_sampah', 'tier' => null, 'category' => null, 'sort_order' => 1,
                'id' => 'Mulai memilah dan menyetorkan **2 - 3 kg sampah/minggu** (±104 - 156 kg/tahun).',
                'en' => 'Start sorting and dropping off **2 - 3 kg of waste per week** (±104 - 156 kg/year).',
            ],
            [
                'code' => 'potensi_pengurangan', 'tier' => 'tinggi', 'category' => null, 'sort_order' => 2,
                'id' => 'Kamu berpotensi mengurangi hingga **160 - 230 kg CO₂ per tahun** (memotong **4 - 7%** dari total emisi tahunanmu).',
                'en' => 'You could cut up to **160 - 230 kg CO₂ per year** (**4 - 7%** off your annual total).',
            ],
            [
                'code' => 'transport_shift', 'tier' => null, 'category' => 'transportasi', 'sort_order' => 3,
                'id' => 'Ganti **2 hari perjalanan per minggu** ke transportasi umum atau berbagi kendaraan.',
                'en' => 'Swap **2 travel days a week** for public transport or carpooling.',
            ],
            [
                'code' => 'listrik_hemat', 'tier' => null, 'category' => 'listrik_rumah', 'sort_order' => 4,
                'id' => 'Naikkan suhu AC ke **25°C** dan cabut perangkat yang menyala siaga.',
                'en' => 'Set the AC to **25°C** and unplug devices left on standby.',
            ],
            [
                'code' => 'konsumsi_bijak', 'tier' => null, 'category' => 'konsumsi_sampah', 'sort_order' => 5,
                'id' => 'Bawa tas belanja sendiri dan gabungkan pesanan online jadi **satu kali pengiriman**.',
                'en' => 'Bring your own shopping bag and bundle online orders into **a single delivery**.',
            ],
        ];

        foreach ($items as $data) {
            $recommendation = Recommendation::updateOrCreate(
                ['code' => $data['code']],
                [
                    'result_tier_id' => $data['tier'] ? $tiers[$data['tier']]->id : null,
                    'emission_category_id' => $data['category'] ? $categories[$data['category']] ?? null : null,
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach (['id', 'en'] as $locale) {
                $recommendation->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['body' => $data[$locale]]
                );
            }
        }
    }

    private function seedCommunityImpacts(): void
    {
        $items = [
            [
                'code' => 'sampah_tpa', 'value' => 25, 'sort_order' => 1,
                'id' => 'Mengurangi **:value Ton** sampah langsung ke TPA',
                'en' => 'Diverted **:value tonnes** of waste from landfill',
            ],
            [
                'code' => 'plastik_keras', 'value' => 12, 'sort_order' => 2,
                'id' => 'Mengolah **:value Ton** plastik keras',
                'en' => 'Processed **:value tonnes** of rigid plastic',
            ],
            [
                'code' => 'multilayer', 'value' => 8, 'sort_order' => 3,
                'id' => 'Mengubah **:value Ton** multilayer menjadi bahan bangunan bermanfaat',
                'en' => 'Turned **:value tonnes** of multilayer packaging into useful building material',
            ],
        ];

        foreach ($items as $data) {
            $impact = CommunityImpact::updateOrCreate(
                ['code' => $data['code']],
                [
                    'value' => $data['value'],
                    'unit' => 'Ton',
                    'reference_year' => (int) date('Y'),
                    'sort_order' => $data['sort_order'],
                    'is_active' => true,
                ]
            );

            foreach (['id', 'en'] as $locale) {
                $impact->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['template' => $data[$locale]]
                );
            }
        }
    }
}
