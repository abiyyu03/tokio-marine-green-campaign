<?php

namespace Database\Seeders;

use App\Models\EmissionCategory;
use App\Models\EmissionFactor;
use App\Models\EmissionField;
use App\Models\EmissionFieldOption;
use Illuminate\Database\Seeder;

/**
 * Menyusun tiga langkah wizard persis seperti desain UI.
 * Seluruh struktur dideklarasikan sebagai array; loader di bawahnya generik,
 * sehingga menambah pertanyaan baru cukup menambah entri array.
 *
 * ANGGARAN POIN (gauge "Skor Kamu", total 100):
 *   Transportasi      35  = moda 20 + jarak 15
 *   Listrik Rumah     30  = AC 14 + kulkas 6 + daya 10
 *   Konsumsi & Sampah 35  = plastik 6 + tas 6 + pilah 7 + galon 6 + daging 6 + belanja 4
 *
 * GAYA TAMPILAN FIELD (`display_style`, dipakai wizard):
 *   card         kartu bergambar 4 kolom (moda transportasi)
 *   pill         tombol teks 2 kolom (jarak, listrik rumah)
 *   pill_compact tombol teks 3 kolom (konsumsi & sampah)
 *
 * PORSI EMISI PER SEKTOR (`emission_share`, dipakai halaman hasil):
 *   Transportasi 40% | Listrik Rumah 35% | Konsumsi & Sampah 25%
 * Angka ini ditetapkan dokumen logic Result Page dan sengaja berbeda dari
 * anggaran poin di atas — poin mengukur perilaku, share membagi ton emisi.
 *
 * PERHATIAN: seluruh angka `points`, `kg_co2e_year`, `numeric_value`, dan
 * `factors` masih PLACEHOLDER yang perlu dikonfirmasi ke sumber resmi sebelum
 * rilis. Kolom `source` dan `reference_year` pada emission_factors disediakan
 * untuk itu.
 */
class CalculatorSchemaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definition() as $categoryData) {
            $this->seedCategory($categoryData);
        }
    }

    private function seedCategory(array $data): void
    {
        $category = EmissionCategory::updateOrCreate(
            ['code' => $data['code']],
            [
                'slug' => $data['slug'],
                'calculator_key' => $data['calculator_key'],
                'icon' => $data['icon'] ?? null,
                'image_file' => $data['image_file'] ?? null,
                'accent_color' => $data['accent_color'] ?? null,
                'max_points' => $data['max_points'],
                'emission_share' => $data['emission_share'] ?? null,
                'sort_order' => $data['sort_order'],
                'is_active' => true,
            ]
        );

        foreach ($data['translations'] as $locale => $attributes) {
            $category->translations()->updateOrCreate(['locale' => $locale], $attributes);
        }

        // Pass 1: buat semua field tanpa dependensi.
        $fields = [];
        foreach ($data['fields'] as $index => $fieldData) {
            $field = EmissionField::updateOrCreate(
                ['emission_category_id' => $category->id, 'code' => $fieldData['code']],
                [
                    'input_type' => $fieldData['input_type'] ?? 'single_choice',
                    'display_style' => $fieldData['display_style'] ?? 'pill',
                    'unit' => $fieldData['unit'] ?? null,
                    'is_required' => $fieldData['is_required'] ?? true,
                    'is_factor_key' => $fieldData['is_factor_key'] ?? false,
                    'is_basis' => $fieldData['is_basis'] ?? false,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );

            foreach ($fieldData['translations'] as $locale => $attributes) {
                $field->translations()->updateOrCreate(['locale' => $locale], $attributes);
            }

            foreach ($fieldData['options'] as $optionIndex => $optionData) {
                $option = EmissionFieldOption::updateOrCreate(
                    ['emission_field_id' => $field->id, 'code' => $optionData['code']],
                    [
                        'image_file' => $optionData['image_file'] ?? null,
                        'icon' => $optionData['icon'] ?? null,
                        'points' => $optionData['points'] ?? 0,
                        'numeric_value' => $optionData['numeric_value'] ?? null,
                        'numeric_unit' => $optionData['numeric_unit'] ?? ($fieldData['unit'] ?? null),
                        'kg_co2e_year' => $optionData['kg_co2e_year'] ?? null,
                        'factor_key' => $optionData['factor_key'] ?? null,
                        'meta' => $optionData['meta'] ?? null,
                        'sort_order' => $optionIndex + 1,
                        'is_active' => true,
                    ]
                );

                foreach ($optionData['translations'] as $locale => $attributes) {
                    $option->translations()->updateOrCreate(['locale' => $locale], $attributes);
                }
            }

            // Opsi yang dikeluarkan dari desain dinonaktifkan, bukan dihapus:
            // jawaban lama di submission_values tetap bisa merujuknya.
            $field->options()
                ->whereNotIn('code', array_column($fieldData['options'], 'code'))
                ->update(['is_active' => false]);

            $fields[$fieldData['code']] = $field;
        }

        // Pass 2: sambungkan dependensi tampil-bersyarat setelah semua field ada.
        foreach ($data['fields'] as $fieldData) {
            if (empty($fieldData['depends_on'])) {
                continue;
            }

            $fields[$fieldData['code']]->update([
                'depends_on_field_id' => $fields[$fieldData['depends_on']]->id,
                'depends_on_option_code' => $fieldData['depends_on_option'] ?? null,
            ]);
        }

        foreach ($data['factors'] ?? [] as $factorData) {
            EmissionFactor::updateOrCreate(
                [
                    'emission_category_id' => $category->id,
                    'factor_key' => $factorData['key'],
                ],
                [
                    'value' => $factorData['value'],
                    'unit' => $factorData['unit'],
                    'basis_unit' => $factorData['basis_unit'] ?? null,
                    'source' => $factorData['source'] ?? 'PLACEHOLDER - perlu verifikasi',
                    'reference_year' => $factorData['reference_year'] ?? null,
                    'is_active' => true,
                    'notes' => $factorData['notes'] ?? null,
                ]
            );
        }
    }

    private function definition(): array
    {
        return [
            $this->transportasi(),
            $this->listrikRumah(),
            $this->konsumsiSampah(),
        ];
    }

    /**
     * Langkah 1 - Transportasi.
     * Hitung: faktor(moda) x jarak(km/hari) x 365.
     */
    private function transportasi(): array
    {
        return [
            'code' => 'transportasi',
            'slug' => 'transportasi',
            'calculator_key' => 'factor_basis',
            'icon' => 'car',
            'image_file' => 'images/calculator/transportasi.jpg',
            'accent_color' => '#F59E0B',
            'max_points' => 35,
            'emission_share' => 0.40,
            'sort_order' => 1,
            'translations' => [
                'id' => [
                    'name' => 'Transportasi',
                    'panel_title' => 'Transportasi',
                    'panel_description' => 'Pilih kendaraan dan jarak harian yang paling sering kamu tempuh untuk menghitung kontribusi emisi dari sektor transportasi.',
                ],
                'en' => [
                    'name' => 'Transport',
                    'panel_title' => 'Transport',
                    'panel_description' => 'Pick the vehicle and daily distance you travel most often so we can measure your emissions from transport.',
                ],
            ],
            'fields' => [
                [
                    'code' => 'moda_transportasi',
                    'display_style' => 'card',
                    'is_factor_key' => true,
                    'translations' => [
                        'id' => [
                            'label' => 'Apa moda transportasi utama yang kamu gunakan sehari-hari?',
                            'summary_label' => 'Moda',
                        ],
                        'en' => [
                            'label' => 'What is your main mode of transport day to day?',
                            'summary_label' => 'Mode',
                        ],
                    ],
                    'options' => [
                        [
                            'code' => 'mobil_bbm', 'points' => 20, 'factor_key' => 'mobil_bbm',
                            'icon' => 'car', 'image_file' => 'images/calculator/moda/mobil-bensin.png',
                            'translations' => [
                                'id' => ['label' => 'Mobil Bensin (BBM)', 'summary_label' => 'Mobil Bensin'],
                                'en' => ['label' => 'Petrol Car', 'summary_label' => 'Petrol Car'],
                            ],
                        ],
                        [
                            'code' => 'motor_bbm', 'points' => 10, 'factor_key' => 'motor_bbm',
                            'icon' => 'motorcycle', 'image_file' => 'images/calculator/moda/motor-bensin.png',
                            'translations' => [
                                'id' => ['label' => 'Motor Bensin (BBM)', 'summary_label' => 'Motor Bensin'],
                                'en' => ['label' => 'Petrol Motorcycle', 'summary_label' => 'Petrol Motorcycle'],
                            ],
                        ],
                        [
                            'code' => 'mobil_ev', 'points' => 12, 'factor_key' => 'mobil_ev',
                            'icon' => 'car-electric', 'image_file' => 'images/calculator/moda/mobil-listrik.png',
                            'translations' => [
                                'id' => ['label' => 'Mobil Listrik (EV)', 'summary_label' => 'Mobil Listrik'],
                                'en' => ['label' => 'Electric Car (EV)', 'summary_label' => 'Electric Car'],
                            ],
                        ],
                        [
                            'code' => 'motor_ev', 'points' => 5, 'factor_key' => 'motor_ev',
                            'icon' => 'motorcycle-electric', 'image_file' => 'images/calculator/moda/motor-listrik.png',
                            'translations' => [
                                'id' => ['label' => 'Motor Listrik (EV)', 'summary_label' => 'Motor Listrik'],
                                'en' => ['label' => 'Electric Motorcycle (EV)', 'summary_label' => 'Electric Motorcycle'],
                            ],
                        ],
                        [
                            'code' => 'transportasi_umum', 'points' => 6, 'factor_key' => 'transportasi_umum',
                            'icon' => 'bus', 'image_file' => 'images/calculator/moda/transportasi-umum.png',
                            'translations' => [
                                'id' => ['label' => 'Transportasi Umum', 'summary_label' => 'Transportasi Umum'],
                                'en' => ['label' => 'Public Transport', 'summary_label' => 'Public Transport'],
                            ],
                        ],
                        [
                            'code' => 'kombinasi', 'points' => 13, 'factor_key' => 'kombinasi',
                            'icon' => 'shuffle', 'image_file' => 'images/calculator/moda/kombinasi.png',
                            'translations' => [
                                'id' => ['label' => 'Kombinasi Transportasi', 'summary_label' => 'Kombinasi Transportasi'],
                                'en' => ['label' => 'A Mix of Modes', 'summary_label' => 'Mixed'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'jarak_harian',
                    'unit' => 'km/day',
                    'is_basis' => true,
                    'translations' => [
                        'id' => [
                            'label' => 'Berapa estimasi total jarak yang kamu tempuh dalam sehari?',
                            'summary_label' => 'Jarak',
                        ],
                        'en' => [
                            'label' => 'Roughly how far do you travel in a day?',
                            'summary_label' => 'Distance',
                        ],
                    ],
                    'options' => [
                        [
                            'code' => 'lt_10', 'points' => 2, 'numeric_value' => 6,
                            'translations' => [
                                'id' => ['label' => 'Kurang dari 10 km / hari', 'summary_label' => '< 10 km / hari'],
                                'en' => ['label' => 'Less than 10 km / day', 'summary_label' => '< 10 km / day'],
                            ],
                        ],
                        [
                            'code' => '10_25', 'points' => 5, 'numeric_value' => 17.5,
                            'translations' => [
                                'id' => ['label' => '10 – 25 km / hari', 'summary_label' => '10 – 25 km / hari'],
                                'en' => ['label' => '10 – 25 km / day', 'summary_label' => '10 – 25 km / day'],
                            ],
                        ],
                        [
                            'code' => '26_50', 'points' => 10, 'numeric_value' => 38,
                            'translations' => [
                                'id' => ['label' => '26 – 50 km / hari', 'summary_label' => '26 – 50 km / hari'],
                                'en' => ['label' => '26 – 50 km / day', 'summary_label' => '26 – 50 km / day'],
                            ],
                        ],
                        [
                            'code' => 'gt_50', 'points' => 15, 'numeric_value' => 65,
                            'translations' => [
                                'id' => ['label' => 'Lebih dari 50 km / hari', 'summary_label' => '> 50 km / hari'],
                                'en' => ['label' => 'More than 50 km / day', 'summary_label' => '> 50 km / day'],
                            ],
                        ],
                    ],
                ],
            ],
            'factors' => [
                ['key' => 'mobil_bbm', 'value' => 0.19200000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km/day'],
                ['key' => 'motor_bbm', 'value' => 0.07500000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km/day'],
                ['key' => 'mobil_ev', 'value' => 0.13000000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km/day',
                    'notes' => 'Turunan dari 0,15 kWh/km x faktor grid PLN.'],
                ['key' => 'motor_ev', 'value' => 0.03000000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km/day',
                    'notes' => 'Turunan dari 0,035 kWh/km x faktor grid PLN.'],
                ['key' => 'transportasi_umum', 'value' => 0.05500000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km/day'],
                ['key' => 'kombinasi', 'value' => 0.11000000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km/day',
                    'notes' => 'Rata-rata tertimbang mobil BBM, motor BBM, dan transportasi umum.'],
            ],
        ];
    }

    /**
     * Langkah 2 - Listrik Rumah.
     * Hitung: total kWh/tahun dari tiap opsi x faktor grid PLN.
     */
    private function listrikRumah(): array
    {
        return [
            'code' => 'listrik_rumah',
            'slug' => 'listrik-rumah',
            'calculator_key' => 'factor_basis',
            'icon' => 'bolt',
            'image_file' => 'images/calculator/listrik-rumah.jpg',
            'accent_color' => '#10B981',
            'max_points' => 30,
            'emission_share' => 0.35,
            'sort_order' => 2,
            'translations' => [
                'id' => [
                    'name' => 'Listrik Rumah',
                    'panel_title' => 'Konsumsi Listrik & Perangkat Rumah Tangga',
                    'panel_description' => 'Penggunaan daya listrik rumah tangga merupakan salah satu penyumbang emisi terbesar. Pilih peralatan dan kapasitas listrik rumahmu untuk mengukur dampaknya.',
                ],
                'en' => [
                    'name' => 'Home Electricity',
                    'panel_title' => 'Electricity Use & Household Appliances',
                    'panel_description' => 'Household electricity is one of the largest sources of emissions. Pick the appliances and connected capacity at home to measure their impact.',
                ],
            ],
            'fields' => [
                [
                    'code' => 'penggunaan_ac',
                    'unit' => 'kwh/year',
                    'is_basis' => true,
                    'translations' => [
                        'id' => [
                            'label' => 'Bagaimana penggunaan Air Conditioner (AC) di rumahmu?',
                            'summary_label' => 'AC',
                        ],
                        'en' => [
                            'label' => 'How is air conditioning used at your home?',
                            'summary_label' => 'AC',
                        ],
                    ],
                    'options' => [
                        [
                            'code' => 'tidak_ada', 'points' => 0, 'numeric_value' => 0,
                            'translations' => [
                                'id' => ['label' => 'Tidak Menggunakan AC', 'summary_label' => 'Tanpa AC'],
                                'en' => ['label' => 'No air conditioning', 'summary_label' => 'No AC'],
                            ],
                        ],
                        [
                            'code' => 'satu_unit_5jam_standar', 'points' => 10, 'numeric_value' => 1230,
                            'translations' => [
                                'id' => ['label' => '1 Unit (< 5 Jam / hari) - Standar', 'summary_label' => '1 Unit (< 5 Jam / hari) - Standar'],
                                'en' => ['label' => '1 unit (< 5 hrs / day) - Standard', 'summary_label' => '1 unit (< 5 hrs) - Standard'],
                            ],
                        ],
                        [
                            'code' => 'satu_unit_8jam_standar', 'points' => 13, 'numeric_value' => 2760,
                            'translations' => [
                                'id' => ['label' => '1 Unit (> 8 Jam / hari) - Standar', 'summary_label' => '1 Unit (> 8 Jam / hari) - Standar'],
                                'en' => ['label' => '1 unit (> 8 hrs / day) - Standard', 'summary_label' => '1 unit (> 8 hrs) - Standard'],
                            ],
                        ],
                        [
                            'code' => 'satu_unit_5jam_inverter', 'points' => 6, 'numeric_value' => 850,
                            'translations' => [
                                'id' => ['label' => '1 Unit (< 5 Jam / hari) - Inverter', 'summary_label' => '1 Unit (< 5 Jam / hari) - Inverter'],
                                'en' => ['label' => '1 unit (< 5 hrs / day) - Inverter', 'summary_label' => '1 unit (< 5 hrs) - Inverter'],
                            ],
                        ],
                        [
                            'code' => 'satu_unit_8jam_inverter', 'points' => 11, 'numeric_value' => 1910,
                            'translations' => [
                                'id' => ['label' => '1 Unit (> 8 Jam / hari) - Inverter', 'summary_label' => '1 Unit (> 8 Jam / hari) - Inverter'],
                                'en' => ['label' => '1 unit (> 8 hrs / day) - Inverter', 'summary_label' => '1 unit (> 8 hrs) - Inverter'],
                            ],
                        ],
                        // Menggantikan opsi lama 'lebih_dari_satu_unit' (dinonaktifkan loader).
                        [
                            'code' => 'dua_unit', 'points' => 13, 'numeric_value' => 3300,
                            'translations' => [
                                'id' => ['label' => '2 Unit AC (Pemakaian Standar/Malam Hari)', 'summary_label' => '2 Unit AC (Standar/Malam Hari)'],
                                'en' => ['label' => '2 AC units (standard/night-time use)', 'summary_label' => '2 AC units (standard/night)'],
                            ],
                        ],
                        [
                            'code' => 'tiga_unit_atau_intensif', 'points' => 14, 'numeric_value' => 5500,
                            'translations' => [
                                'id' => ['label' => '≥ 3 Unit AC atau Pemakaian Intensif', 'summary_label' => '≥ 3 Unit AC / Intensif'],
                                'en' => ['label' => '≥ 3 AC units or heavy use', 'summary_label' => '≥ 3 AC units / heavy use'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'tipe_kulkas',
                    'unit' => 'kwh/year',
                    'is_basis' => true,
                    'translations' => [
                        'id' => [
                            'label' => 'Tipe kulkas apa yang digunakan di rumahmu?',
                            'summary_label' => 'Kulkas',
                        ],
                        'en' => [
                            'label' => 'What type of refrigerator do you use at home?',
                            'summary_label' => 'Refrigerator',
                        ],
                    ],
                    'options' => [
                        [
                            'code' => 'tidak_ada', 'points' => 0, 'numeric_value' => 0,
                            'translations' => [
                                'id' => ['label' => 'Tidak Ada Kulkas', 'summary_label' => 'Tanpa Kulkas'],
                                'en' => ['label' => 'No refrigerator', 'summary_label' => 'None'],
                            ],
                        ],
                        [
                            'code' => 'standar', 'points' => 6, 'numeric_value' => 480,
                            'translations' => [
                                'id' => ['label' => 'Kulkas Standar (Non-Inverter)', 'summary_label' => 'Kulkas Standar (Non-Inverter)'],
                                'en' => ['label' => 'Standard refrigerator (non-inverter)', 'summary_label' => 'Standard (non-inverter)'],
                            ],
                        ],
                        [
                            'code' => 'inverter', 'points' => 3, 'numeric_value' => 250,
                            'translations' => [
                                'id' => ['label' => 'Kulkas Hemat Energi (Inverter)', 'summary_label' => 'Kulkas Inverter'],
                                'en' => ['label' => 'Energy-saving refrigerator (inverter)', 'summary_label' => 'Inverter'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'daya_terpasang',
                    'unit' => 'kwh/year',
                    'is_basis' => true,
                    'translations' => [
                        'id' => [
                            'label' => 'Berapa batas daya listrik (VA) terpasang di rumahmu?',
                            'summary_label' => 'Daya Listrik',
                        ],
                        'en' => [
                            'label' => 'What is the connected electrical capacity (VA) at your home?',
                            'summary_label' => 'Capacity',
                        ],
                    ],
                    'options' => [
                        [
                            'code' => 'lte_900', 'points' => 5, 'numeric_value' => 720,
                            'meta' => ['va' => 900],
                            'translations' => [
                                'id' => ['label' => '≤ 900 VA', 'summary_label' => '≤ 900 VA'],
                                'en' => ['label' => '≤ 900 VA', 'summary_label' => '≤ 900 VA'],
                            ],
                        ],
                        // ≤ 900 VA sengaja tetap 5 poin supaya skenario mockup
                        // (25 -> 46 -> 71) tidak bergeser; opsi di atasnya
                        // dipadatkan agar jatah daya tetap maksimal 10.
                        [
                            'code' => '1300', 'points' => 6, 'numeric_value' => 1200,
                            'meta' => ['va' => 1300],
                            'translations' => [
                                'id' => ['label' => '1300 VA', 'summary_label' => '1300 VA'],
                                'en' => ['label' => '1300 VA', 'summary_label' => '1300 VA'],
                            ],
                        ],
                        [
                            'code' => '2200', 'points' => 8, 'numeric_value' => 2000,
                            'meta' => ['va' => 2200],
                            'translations' => [
                                'id' => ['label' => '2200 VA', 'summary_label' => '2200 VA'],
                                'en' => ['label' => '2200 VA', 'summary_label' => '2200 VA'],
                            ],
                        ],
                        [
                            'code' => '3500_5500', 'points' => 9, 'numeric_value' => 3600,
                            'meta' => ['va_min' => 3500, 'va_max' => 5500],
                            'translations' => [
                                'id' => ['label' => '3.500 VA – 5.500 VA', 'summary_label' => '3.500 – 5.500 VA'],
                                'en' => ['label' => '3,500 VA – 5,500 VA', 'summary_label' => '3,500 – 5,500 VA'],
                            ],
                        ],
                        [
                            'code' => 'gte_6600', 'points' => 10, 'numeric_value' => 6000,
                            'meta' => ['va' => 6600],
                            'translations' => [
                                'id' => ['label' => '≥ 6.600 VA', 'summary_label' => '≥ 6.600 VA'],
                                'en' => ['label' => '≥ 6,600 VA', 'summary_label' => '≥ 6,600 VA'],
                            ],
                        ],
                    ],
                ],
            ],
            'factors' => [
                [
                    'key' => '', 'value' => 0.87000000, 'unit' => 'kgCO2e/kWh', 'basis_unit' => 'kwh/year',
                    'notes' => 'Faktor emisi grid PLN sistem Jawa-Bali. Perlu diperbarui ke angka resmi terakhir.',
                ],
            ],
        ];
    }

    /**
     * Langkah 3 - Konsumsi & Sampah.
     * Hitung: penjumlahan kg CO2e/tahun tiap opsi terpilih.
     */
    private function konsumsiSampah(): array
    {
        return [
            'code' => 'konsumsi_sampah',
            'slug' => 'konsumsi-sampah',
            'calculator_key' => 'direct_sum',
            'icon' => 'recycle',
            'image_file' => 'images/calculator/konsumsi-sampah.jpg',
            'accent_color' => '#EF4444',
            'max_points' => 35,
            'emission_share' => 0.25,
            'sort_order' => 3,
            'translations' => [
                'id' => [
                    'name' => 'Konsumsi & Sampah',
                    'panel_title' => 'Gaya Hidup & Pola Konsumsi Harian',
                    'panel_description' => 'Kebiasaan belanja, pengelolaan sampah, dan pilihan makananmu memberikan dampak langsung terhadap jumlah jejak karbon harian.',
                ],
                'en' => [
                    'name' => 'Consumption & Waste',
                    'panel_title' => 'Lifestyle & Daily Consumption',
                    'panel_description' => 'Your shopping habits, how you handle waste, and what you eat all feed directly into your daily carbon footprint.',
                ],
            ],
            'fields' => [
                [
                    'code' => 'plastik_sekali_pakai',
                    'display_style' => 'pill_compact',
                    'translations' => [
                        'id' => ['label' => 'Seberapa sering kamu menggunakan plastik sekali pakai?', 'summary_label' => 'Penggunaan Plastik'],
                        'en' => ['label' => 'How often do you use single-use plastic?', 'summary_label' => 'Single-use Plastic'],
                    ],
                    'options' => [
                        [
                            'code' => 'jarang', 'points' => 1, 'kg_co2e_year' => 15,
                            'translations' => [
                                'id' => ['label' => 'Jarang (0–2x / minggu)', 'summary_label' => 'Jarang (0–2x / minggu)'],
                                'en' => ['label' => 'Rarely (0–2x / week)', 'summary_label' => 'Rarely (0–2x / week)'],
                            ],
                        ],
                        [
                            'code' => 'sedang', 'points' => 5, 'kg_co2e_year' => 40,
                            'translations' => [
                                'id' => ['label' => 'Sedang (3–5x / minggu)', 'summary_label' => 'Sedang (3–5x / minggu)'],
                                'en' => ['label' => 'Moderate (3–5x / week)', 'summary_label' => 'Moderate (3–5x / week)'],
                            ],
                        ],
                        [
                            'code' => 'sering', 'points' => 6, 'kg_co2e_year' => 75,
                            'translations' => [
                                'id' => ['label' => 'Sering (>5x / minggu)', 'summary_label' => 'Sering (>5x / minggu)'],
                                'en' => ['label' => 'Often (>5x / week)', 'summary_label' => 'Often (>5x / week)'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'tas_belanja',
                    'display_style' => 'pill_compact',
                    'translations' => [
                        'id' => ['label' => 'Apakah kamu selalu membawa tas belanja sendiri saat bepergian?', 'summary_label' => 'Penggunaan Tas Belanja'],
                        'en' => ['label' => 'Do you always bring your own shopping bag?', 'summary_label' => 'Reusable Bag'],
                    ],
                    'options' => [
                        [
                            'code' => 'selalu', 'points' => 0, 'kg_co2e_year' => 0,
                            'translations' => [
                                'id' => ['label' => 'Ya, Selalu', 'summary_label' => 'Selalu'],
                                'en' => ['label' => 'Yes, always', 'summary_label' => 'Always'],
                            ],
                        ],
                        [
                            'code' => 'kadang', 'points' => 3, 'kg_co2e_year' => 12,
                            'translations' => [
                                'id' => ['label' => 'Kadang-kadang', 'summary_label' => 'Kadang-kadang'],
                                'en' => ['label' => 'Sometimes', 'summary_label' => 'Sometimes'],
                            ],
                        ],
                        [
                            'code' => 'tidak_pernah', 'points' => 6, 'kg_co2e_year' => 25,
                            'translations' => [
                                'id' => ['label' => 'Tidak Pernah', 'summary_label' => 'Tidak Pernah'],
                                'en' => ['label' => 'Never', 'summary_label' => 'Never'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'pilah_sampah',
                    'display_style' => 'pill_compact',
                    'translations' => [
                        'id' => ['label' => 'Apakah kamu memilah sampah organik dan anorganik di rumah?', 'summary_label' => 'Milah Sampah'],
                        'en' => ['label' => 'Do you separate organic and inorganic waste at home?', 'summary_label' => 'Waste Sorting'],
                    ],
                    'options' => [
                        [
                            'code' => 'ya', 'points' => 0, 'kg_co2e_year' => 0,
                            'translations' => [
                                'id' => ['label' => 'Ya', 'summary_label' => 'Ya'],
                                'en' => ['label' => 'Yes', 'summary_label' => 'Yes'],
                            ],
                        ],
                        [
                            'code' => 'tidak', 'points' => 7, 'kg_co2e_year' => 120,
                            'translations' => [
                                'id' => ['label' => 'Tidak', 'summary_label' => 'Tidak'],
                                'en' => ['label' => 'No', 'summary_label' => 'No'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'galon_isi_ulang',
                    'display_style' => 'pill_compact',
                    'translations' => [
                        'id' => ['label' => 'Apakah kamu menggunakan air galon isi ulang untuk kebutuhan minum?', 'summary_label' => 'Penggunaan Galon Isi Ulang'],
                        'en' => ['label' => 'Do you use refillable water gallons for drinking?', 'summary_label' => 'Refill Gallon'],
                    ],
                    'options' => [
                        [
                            'code' => 'ya', 'points' => 0, 'kg_co2e_year' => 0,
                            'translations' => [
                                'id' => ['label' => 'Ya', 'summary_label' => 'Ya'],
                                'en' => ['label' => 'Yes', 'summary_label' => 'Yes'],
                            ],
                        ],
                        [
                            'code' => 'tidak', 'points' => 6, 'kg_co2e_year' => 60,
                            'translations' => [
                                'id' => ['label' => 'Tidak', 'summary_label' => 'Tidak'],
                                'en' => ['label' => 'No', 'summary_label' => 'No'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'daging_merah',
                    'display_style' => 'pill_compact',
                    'translations' => [
                        'id' => ['label' => 'Seberapa sering kamu mengonsumsi daging merah (sapi/kambing)?', 'summary_label' => 'Konsumsi Daging Merah'],
                        'en' => ['label' => 'How often do you eat red meat (beef/lamb)?', 'summary_label' => 'Red Meat'],
                    ],
                    'options' => [
                        [
                            'code' => 'jarang', 'points' => 2, 'kg_co2e_year' => 120,
                            'translations' => [
                                'id' => ['label' => 'Jarang (0–1x / minggu)', 'summary_label' => 'Jarang (0–1x / minggu)'],
                                'en' => ['label' => 'Rarely (0–1x / week)', 'summary_label' => 'Rarely (0–1x / week)'],
                            ],
                        ],
                        [
                            'code' => 'sedang', 'points' => 4, 'kg_co2e_year' => 320,
                            'translations' => [
                                'id' => ['label' => 'Sedang (2–4x / minggu)', 'summary_label' => 'Sedang (2–4x / minggu)'],
                                'en' => ['label' => 'Moderate (2–4x / week)', 'summary_label' => 'Moderate (2–4x / week)'],
                            ],
                        ],
                        [
                            'code' => 'sering', 'points' => 6, 'kg_co2e_year' => 620,
                            'translations' => [
                                'id' => ['label' => 'Sering (>5x / minggu)', 'summary_label' => 'Sering (>5x / minggu)'],
                                'en' => ['label' => 'Often (>5x / week)', 'summary_label' => 'Often (>5x / week)'],
                            ],
                        ],
                    ],
                ],
                [
                    'code' => 'belanja_online',
                    'display_style' => 'pill_compact',
                    'translations' => [
                        'id' => ['label' => 'Berapa frekuensi kamu melakukan transaksi belanja online dalam sebulan?', 'summary_label' => 'Belanja Online'],
                        'en' => ['label' => 'How many online purchases do you make in a month?', 'summary_label' => 'Online Shopping'],
                    ],
                    'options' => [
                        [
                            'code' => 'lte_5', 'points' => 2, 'kg_co2e_year' => 45,
                            'translations' => [
                                'id' => ['label' => '≤ 5 kali / bulan', 'summary_label' => '≤ 5 kali / bulan'],
                                'en' => ['label' => '≤ 5 times / month', 'summary_label' => '≤ 5 / month'],
                            ],
                        ],
                        [
                            'code' => 'gt_5', 'points' => 4, 'kg_co2e_year' => 110,
                            'translations' => [
                                'id' => ['label' => '> 5 kali / bulan', 'summary_label' => '> 5 kali / bulan'],
                                'en' => ['label' => 'More than 5 times / month', 'summary_label' => 'More than 5 / month'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
