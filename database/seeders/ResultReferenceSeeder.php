<?php

namespace Database\Seeders;

use App\Models\CommunityImpact;
use App\Models\EmissionBenchmark;
use App\Models\EmissionEquivalence;
use App\Models\Recommendation;
use App\Models\ResultTier;
use Illuminate\Database\Seeder;

/**
 * Data referensi halaman hasil, mengikuti "Dokumentasi Logic & UI Copy
 * Result Page Kalkulator Karbon".
 *
 * MATRIKS TIER (bagian 1 dokumen):
 *   0-30   Dampak Ringan  1,5-2 Ton  Green Starter    #166534
 *   31-60  Dampak Sedang  2-3   Ton  Earth Supporter  #854d0e
 *   61-100 Dampak Tinggi  3-5   Ton  Climate Mover    #991b1b
 *
 * KOEFISIEN PADANAN VISUAL (bagian 3 dokumen):
 *   1 Ton CO2e = 420 Liter bensin = 4,7 penerbangan domestik = 50 pohon dewasa
 * Tabel di database menyimpan kebalikannya (kg CO2e per satu satuan) supaya
 * satu rumus `emisi / kg_per_unit` berlaku untuk semua baris.
 *
 * Template terjemahan memakai penanda **tebal** dan placeholder :value / :name
 * / :range yang dirender oleh App\Support\ResultText.
 */
class ResultReferenceSeeder extends Seeder
{
    /** 1 Ton CO2e setara berapa satuan padanan. */
    private const UNITS_PER_TON = [
        'bensin' => 420,
        'penerbangan' => 4.7,
        'pohon' => 50,
    ];

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
                'approx_min' => 1.5, 'approx_max' => 2.0,
                'avoided_min' => 7.5, 'avoided_max' => 8.0,
                'badge_icon' => 'leaf', 'color' => '#166534', 'sort_order' => 1,
                'translations' => [
                    'id' => [
                        'label' => 'Dampak Ringan',
                        'badge_label' => 'Green Starter',
                        'headline' => 'Pertahankan Kebiasaan Baikmu!',
                        'benchmark_note' => 'Gaya hidupmu sudah sangat baik dan tergolong ramah lingkungan! Emisimu berada di bawah rata-rata per kapita masyarakat Indonesia (:range Ton CO₂/tahun).',
                        'description' => 'Untuk terus menjaga bumi dan menekan sisa emisi harianmu, kamu bisa mulai langkah kecil berikut dari rumah:',
                    ],
                    'en' => [
                        'label' => 'Low Impact',
                        'badge_label' => 'Green Starter',
                        'headline' => 'Keep Up the Good Habits!',
                        'benchmark_note' => 'Your lifestyle is already very kind to the planet. Your footprint sits below the Indonesian average per capita (:range tonnes CO₂/year).',
                        'description' => 'To keep it that way and trim what is left of your daily emissions, you can start with these small steps at home:',
                    ],
                ],
            ],
            [
                'code' => 'sedang', 'min_score' => 31, 'max_score' => 60,
                'approx_min' => 2.0, 'approx_max' => 3.0,
                'avoided_min' => 8.0, 'avoided_max' => 16.0,
                'badge_icon' => 'seedling', 'color' => '#854d0e', 'sort_order' => 2,
                'translations' => [
                    'id' => [
                        'label' => 'Dampak Sedang',
                        'badge_label' => 'Earth Supporter',
                        'headline' => 'Kabar Baik Untukmu, :name!',
                        'benchmark_note' => 'Jejak emisi harianmu saat ini mendekati rata-rata per kapita masyarakat Indonesia (:range Ton CO₂/tahun). Masih ada ruang untuk penghematan!',
                        'description' => 'Kamu bisa menekan emisi harianmu secara signifikan tanpa harus mengubah seluruh pola hidup sekaligus. Cukup mulai dari 1 kebiasaan kecil dari rumah:',
                    ],
                    'en' => [
                        'label' => 'Moderate Impact',
                        'badge_label' => 'Earth Supporter',
                        'headline' => 'Good News for You, :name!',
                        'benchmark_note' => 'Your daily footprint is close to the Indonesian average per capita (:range tonnes CO₂/year). There is still room to save!',
                        'description' => 'You can bring your daily emissions down significantly without changing your whole lifestyle at once. One small habit at home is enough to start:',
                    ],
                ],
            ],
            [
                'code' => 'tinggi', 'min_score' => 61, 'max_score' => 100,
                'approx_min' => 3.0, 'approx_max' => 5.0,
                'avoided_min' => 16.0, 'avoided_max' => 23.0,
                'badge_icon' => 'bolt', 'color' => '#991b1b', 'sort_order' => 3,
                'translations' => [
                    'id' => [
                        'label' => 'Dampak Tinggi',
                        'badge_label' => 'Climate Mover',
                        'headline' => 'Peluang Besar Membuat Perubahan!',
                        'benchmark_note' => 'Jejak emisi harianmu saat ini berada di atas rata-rata per kapita masyarakat Indonesia (:range Ton CO₂/tahun).',
                        'description' => 'Jejak emisi yang tinggi memberikan peluang besar bagi kamu untuk membuat perubahan berdampak signifikan dengan langkah sederhana:',
                    ],
                    'en' => [
                        'label' => 'High Impact',
                        'badge_label' => 'Climate Mover',
                        'headline' => 'A Big Chance to Make a Difference!',
                        'benchmark_note' => 'Your daily footprint is currently above the Indonesian average per capita (:range tonnes CO₂/year).',
                        'description' => 'A high footprint is a big opportunity to make a real difference with simple steps:',
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
                    'community_avoided_min_ton_co2e' => $data['avoided_min'],
                    'community_avoided_max_ton_co2e' => $data['avoided_max'],
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
                'id' => 'rata-rata per kapita masyarakat Indonesia',
                'en' => 'the Indonesian average per capita',
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
     *
     * Uji cepat memakai koefisien dokumen: 2 Ton -> 840 liter, 9 penerbangan,
     * 100 pohon; 5 Ton -> 2.100 liter, 24 penerbangan, 250 pohon.
     */
    private function seedEquivalences(): void
    {
        $items = [
            [
                'code' => 'bensin', 'icon' => 'fuel', 'sort_order' => 1,
                'id' => '**:value Liter Bensin** yang dikonsumsi kendaraan',
                'en' => '**:value litres of petrol** burned by a vehicle',
            ],
            [
                'code' => 'penerbangan', 'icon' => 'plane', 'sort_order' => 2,
                'id' => '**:value Kali Penerbangan** domestik antarkota',
                'en' => '**:value domestic flights** between cities',
            ],
            [
                'code' => 'pohon', 'icon' => 'tree', 'sort_order' => 3,
                'id' => 'Butuh **:value Pohon Dewasa** selama 1 tahun penuh untuk menyerap seluruh emisimu',
                'en' => 'It takes **:value mature trees** a full year to absorb all of your emissions',
            ],
        ];

        foreach ($items as $data) {
            $equivalence = EmissionEquivalence::updateOrCreate(
                ['code' => $data['code']],
                [
                    'kg_co2e_per_unit' => round(1000 / self::UNITS_PER_TON[$data['code']], 6),
                    'decimals' => 0,
                    'icon' => $data['icon'],
                    'source' => 'Dokumentasi Logic Result Page - 1 Ton CO2e = '
                        .self::UNITS_PER_TON[$data['code']].' satuan',
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

    /**
     * "Rekomendasi Aksi Khusus :name" — dua butir per tier, angka setoran
     * sampah dan potensi penguranganya naik seiring tier.
     *
     * @param  array<string, ResultTier>  $tiers
     */
    private function seedRecommendations(array $tiers): void
    {
        $items = [
            [
                'code' => 'pilah_setor_ringan', 'tier' => 'ringan', 'sort_order' => 1,
                'id' => 'Mulai memilah dan menyetorkan **1 kg sampah/minggu** (±52 kg/tahun).',
                'en' => 'Start sorting and dropping off **1 kg of waste per week** (±52 kg/year).',
            ],
            [
                'code' => 'potensi_ringan', 'tier' => 'ringan', 'sort_order' => 2,
                'id' => 'Kamu berpotensi mengurangi hingga **75 - 80 kg CO₂ per tahun** (memotong **4 - 8%** dari total emisi tahunanmu!).',
                'en' => 'You could cut up to **75 - 80 kg CO₂ per year** (**4 - 8%** off your annual total!).',
            ],
            [
                'code' => 'pilah_setor_sedang', 'tier' => 'sedang', 'sort_order' => 1,
                'id' => 'Mulai memilah dan menyetorkan **1 - 2 kg sampah/minggu** (±52 - 104 kg/tahun).',
                'en' => 'Start sorting and dropping off **1 - 2 kg of waste per week** (±52 - 104 kg/year).',
            ],
            [
                'code' => 'potensi_sedang', 'tier' => 'sedang', 'sort_order' => 2,
                'id' => 'Kamu berpotensi mengurangi hingga **80 - 160 kg CO₂ per tahun** (memotong **3 - 6%** dari total emisi tahunanmu!).',
                'en' => 'You could cut up to **80 - 160 kg CO₂ per year** (**3 - 6%** off your annual total!).',
            ],
            [
                'code' => 'pilah_setor_tinggi', 'tier' => 'tinggi', 'sort_order' => 1,
                'id' => 'Mulai memilah dan menyetorkan **2 - 3 kg sampah/minggu** (±104 - 156 kg/tahun).',
                'en' => 'Start sorting and dropping off **2 - 3 kg of waste per week** (±104 - 156 kg/year).',
            ],
            [
                'code' => 'potensi_tinggi', 'tier' => 'tinggi', 'sort_order' => 2,
                'id' => ':name berpotensi mengurangi hingga **160 - 230 kg CO₂ per tahun** (memotong **4 - 7%** dari total emisi tahunanmu!).',
                'en' => ':name could cut up to **160 - 230 kg CO₂ per year** (**4 - 7%** off your annual total!).',
            ],
        ];

        foreach ($items as $data) {
            $recommendation = Recommendation::updateOrCreate(
                ['code' => $data['code']],
                [
                    'result_tier_id' => $tiers[$data['tier']]->id,
                    'emission_category_id' => null,
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

        // Butir versi lama tidak lagi cocok dengan dokumen: yang tanpa tier akan
        // muncul di semua tier, yang per kategori menambah butir di luar dua
        // butir yang ditetapkan. Dinonaktifkan, bukan dihapus, supaya hasil
        // lama yang sudah pernah menampilkannya tetap bisa ditelusuri.
        Recommendation::query()
            ->whereIn('code', [
                'pilah_setor_sampah', 'potensi_pengurangan',
                'transport_shift', 'listrik_hemat', 'konsumsi_bijak',
            ])
            ->update(['is_active' => false]);
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
