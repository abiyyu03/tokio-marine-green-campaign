<?php

namespace Database\Seeders;

use App\Models\EmissionCategory;
use App\Models\EmissionFactor;
use App\Models\EmissionField;
use App\Models\EmissionFieldOption;
use Illuminate\Database\Seeder;

/**
 * Menyusun ulang tiga langkah wizard persis seperti desain UI.
 * Seluruh struktur dideklarasikan sebagai array; loader di bawahnya generik,
 * sehingga menambah pertanyaan baru cukup menambah entri array.
 *
 * PERHATIAN: angka pada `factors` dan `meta.tariff_per_kwh` masih placeholder
 * yang perlu dikonfirmasi ke sumber resmi sebelum rilis. Kolom `source` dan
 * `reference_year` disediakan untuk itu.
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
                'is_repeatable' => $data['is_repeatable'] ?? false,
                'max_entries' => $data['max_entries'] ?? null,
                'sort_order' => $data['sort_order'],
                'is_active' => true,
            ]
        );

        foreach ($data['translations'] as $locale => $attributes) {
            $category->translations()->updateOrCreate(
                ['locale' => $locale],
                $attributes
            );
        }

        // Pass 1: buat semua field tanpa dependensi.
        $fields = [];
        foreach ($data['fields'] as $index => $fieldData) {
            $field = EmissionField::updateOrCreate(
                ['emission_category_id' => $category->id, 'code' => $fieldData['code']],
                [
                    'input_type' => $fieldData['input_type'],
                    'display_style' => $fieldData['display_style'] ?? null,
                    'unit' => $fieldData['unit'] ?? null,
                    'unit_position' => $fieldData['unit_position'] ?? 'suffix',
                    'decimals' => $fieldData['decimals'] ?? 0,
                    'min_value' => $fieldData['min_value'] ?? null,
                    'max_value' => $fieldData['max_value'] ?? null,
                    'step' => $fieldData['step'] ?? null,
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

            foreach ($fieldData['options'] ?? [] as $optionIndex => $optionData) {
                $option = EmissionFieldOption::updateOrCreate(
                    ['emission_field_id' => $field->id, 'code' => $optionData['code']],
                    [
                        'image_file' => $optionData['image_file'] ?? null,
                        'icon' => $optionData['icon'] ?? null,
                        'numeric_value' => $optionData['numeric_value'] ?? null,
                        'numeric_unit' => $optionData['numeric_unit'] ?? null,
                        'meta' => $optionData['meta'] ?? null,
                        'sort_order' => $optionIndex + 1,
                        'is_active' => true,
                    ]
                );

                foreach ($optionData['translations'] as $locale => $attributes) {
                    $option->translations()->updateOrCreate(['locale' => $locale], $attributes);
                }
            }

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

        foreach ($data['factors'] as $factorData) {
            EmissionFactor::updateOrCreate(
                [
                    'emission_category_id' => $category->id,
                    'factor_key' => $factorData['key'],
                ],
                [
                    'value' => $factorData['value'],
                    'unit' => $factorData['unit'],
                    'basis_unit' => $factorData['basis_unit'],
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
            $this->transportasiDarat(),
            $this->dayaRumahTangga(),
            $this->peralatanRumahTangga(),
        ];
    }

    private function transportasiDarat(): array
    {
        return [
            'code' => 'transportasi_darat',
            'slug' => 'transportasi-darat',
            'calculator_key' => 'distance_factor',
            'icon' => 'car-side',
            'is_repeatable' => true,
            'max_entries' => 10,
            'sort_order' => 1,
            'translations' => [
                'id' => [
                    'name' => 'Transportasi Darat',
                    'title' => 'Transportasi Darat',
                    'subtitle' => 'Bagaimana Cara Anda Beraktivitas Hari Ini?',
                    'summary_label' => 'Total Emisi perjalanan Anda',
                    'add_entry_label' => 'Tambah Kendaraan Lain',
                ],
                'en' => [
                    'name' => 'Land Transport',
                    'title' => 'Land Transport',
                    'subtitle' => 'How Do You Get Around Today?',
                    'summary_label' => 'Your Total Travel Emissions',
                    'add_entry_label' => 'Add Another Vehicle',
                ],
            ],
            'fields' => [
                [
                    'code' => 'moda_transportasi',
                    'input_type' => 'single_choice',
                    'display_style' => 'card',
                    'is_factor_key' => true,
                    'translations' => [
                        'id' => ['label' => 'Pilih moda transportasi utama Anda'],
                        'en' => ['label' => 'Choose your main mode of transport'],
                    ],
                    'options' => [
                        ['code' => 'mobil', 'icon' => 'car',
                            'translations' => ['id' => ['label' => 'Mobil'], 'en' => ['label' => 'Car']]],
                        ['code' => 'motor', 'icon' => 'motorcycle',
                            'translations' => ['id' => ['label' => 'Motor'], 'en' => ['label' => 'Motorcycle']]],
                    ],
                ],
                [
                    'code' => 'bahan_bakar',
                    'input_type' => 'single_choice',
                    'display_style' => 'card',
                    'is_factor_key' => true,
                    // Muncul setelah moda transportasi dipilih (apa pun pilihannya).
                    'depends_on' => 'moda_transportasi',
                    'translations' => [
                        'id' => ['label' => 'Bahan bakar apa yang digunakan kendaraan Anda?'],
                        'en' => ['label' => 'What fuel does your vehicle use?'],
                    ],
                    'options' => [
                        ['code' => 'listrik', 'icon' => 'ev-plug',
                            'translations' => ['id' => ['label' => 'Listrik'], 'en' => ['label' => 'Electric']]],
                        ['code' => 'bensin', 'icon' => 'fuel-pump',
                            'translations' => ['id' => ['label' => 'Bensin'], 'en' => ['label' => 'Gasoline']]],
                        ['code' => 'solar', 'icon' => 'fuel-can',
                            'translations' => ['id' => ['label' => 'Solar'], 'en' => ['label' => 'Diesel']]],
                    ],
                ],
                [
                    'code' => 'jarak_harian',
                    'input_type' => 'number',
                    'unit' => 'KM',
                    'unit_position' => 'suffix',
                    'is_basis' => true,
                    'min_value' => 0,
                    'max_value' => 1000,
                    'step' => 1,
                    'translations' => [
                        'id' => [
                            'label' => 'Seberapa jauh Anda menempuh perjalanan dalam sehari?',
                            'placeholder' => 'Contoh: 30',
                            'unit_label' => 'KM',
                        ],
                        'en' => [
                            'label' => 'How far do you travel in a day?',
                            'placeholder' => 'e.g. 30',
                            'unit_label' => 'KM',
                        ],
                    ],
                ],
            ],
            'factors' => [
                ['key' => 'mobil|bensin',  'value' => 0.19200, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km'],
                ['key' => 'mobil|solar',   'value' => 0.17100, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km'],
                ['key' => 'mobil|listrik', 'value' => 0.15000, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km'],
                ['key' => 'motor|bensin',  'value' => 0.10300, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km'],
                ['key' => 'motor|listrik', 'value' => 0.02600, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km'],
                ['key' => 'motor|solar',   'value' => 0.10300, 'unit' => 'kgCO2e/km', 'basis_unit' => 'km',
                    'notes' => 'Kombinasi tidak lazim di Indonesia. Konfirmasi apakah opsi Solar perlu disembunyikan saat moda = Motor.'],
            ],
        ];
    }

    private function dayaRumahTangga(): array
    {
        return [
            'code' => 'daya_rumah_tangga',
            'slug' => 'daya-rumah-tangga',
            'calculator_key' => 'electricity_bill',
            'icon' => 'home',
            'is_repeatable' => false,
            'sort_order' => 2,
            'translations' => [
                'id' => [
                    'name' => 'Daya Rumah Tangga',
                    'title' => 'Daya Rumah Tangga',
                    'subtitle' => 'Berikan Dampak Positif dari Rumah.',
                    'summary_label' => 'Total Emisi rumah tangga Anda',
                ],
                'en' => [
                    'name' => 'Household Energy',
                    'title' => 'Household Energy',
                    'subtitle' => 'Make a Positive Impact from Home.',
                    'summary_label' => 'Your Total Household Emissions',
                ],
            ],
            'fields' => [
                [
                    'code' => 'anggota_keluarga',
                    'input_type' => 'number',
                    'unit' => 'Orang',
                    'min_value' => 1,
                    'max_value' => 30,
                    'step' => 1,
                    'translations' => [
                        'id' => [
                            'label' => 'Berapa banyak anggota keluarga yang tinggal bersama Anda?',
                            'placeholder' => 'Contoh: 30',
                            'unit_label' => 'Orang',
                        ],
                        'en' => [
                            'label' => 'How many family members live with you?',
                            'placeholder' => 'e.g. 30',
                            'unit_label' => 'People',
                        ],
                    ],
                ],
                [
                    'code' => 'sumber_listrik',
                    'input_type' => 'single_choice',
                    'display_style' => 'card',
                    'is_factor_key' => true,
                    'translations' => [
                        'id' => ['label' => 'Dari mana listrik rumah Anda berasal?'],
                        'en' => ['label' => 'Where does your home electricity come from?'],
                    ],
                    'options' => [
                        ['code' => 'pln', 'icon' => 'transmission-tower',
                            'translations' => ['id' => ['label' => '100% PLN'], 'en' => ['label' => '100% Grid']]],
                        ['code' => 'energi_bersih', 'icon' => 'solar-panel',
                            'translations' => ['id' => ['label' => '100% Energi Bersih'], 'en' => ['label' => '100% Clean Energy']]],
                        ['code' => 'hybrid', 'icon' => 'home-bolt', 'meta' => ['renewable_share' => 0.5],
                            'translations' => ['id' => ['label' => 'Hybrid'], 'en' => ['label' => 'Hybrid']]],
                    ],
                ],
                [
                    'code' => 'daya_terpasang',
                    'input_type' => 'select',
                    'display_style' => 'dropdown',
                    'unit' => 'VA',
                    'translations' => [
                        'id' => [
                            'label' => 'Berapa daya listrik terpasang di rumah Anda?',
                            'placeholder' => 'Pilih Daya Listrik Rumah Anda',
                            'unit_label' => 'VA',
                        ],
                        'en' => [
                            'label' => 'What is your installed electrical capacity?',
                            'placeholder' => 'Select your home capacity',
                            'unit_label' => 'VA',
                        ],
                    ],
                    // numeric_value = daya (VA), meta.tariff_per_kwh = tarif Rp/kWh
                    // yang dipakai untuk menurunkan kWh dari nominal tagihan.
                    'options' => [
                        ['code' => '450', 'numeric_value' => 450, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 415.00],
                            'translations' => ['id' => ['label' => '450 VA'], 'en' => ['label' => '450 VA']]],
                        ['code' => '900_subsidi', 'numeric_value' => 900, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 605.00],
                            'translations' => ['id' => ['label' => '900 VA (Subsidi)'], 'en' => ['label' => '900 VA (Subsidised)']]],
                        ['code' => '900_rtm', 'numeric_value' => 900, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 1352.00],
                            'translations' => ['id' => ['label' => '900 VA (Non-Subsidi)'], 'en' => ['label' => '900 VA (Non-Subsidised)']]],
                        ['code' => '1300', 'numeric_value' => 1300, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 1444.70],
                            'translations' => ['id' => ['label' => '1.300 VA'], 'en' => ['label' => '1,300 VA']]],
                        ['code' => '2200', 'numeric_value' => 2200, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 1444.70],
                            'translations' => ['id' => ['label' => '2.200 VA'], 'en' => ['label' => '2,200 VA']]],
                        ['code' => '3500_5500', 'numeric_value' => 3500, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 1699.53],
                            'translations' => ['id' => ['label' => '3.500 - 5.500 VA'], 'en' => ['label' => '3,500 - 5,500 VA']]],
                        ['code' => '6600_up', 'numeric_value' => 6600, 'numeric_unit' => 'VA',
                            'meta' => ['tariff_per_kwh' => 1699.53],
                            'translations' => ['id' => ['label' => '6.600 VA ke atas'], 'en' => ['label' => '6,600 VA and above']]],
                    ],
                ],
                [
                    'code' => 'tagihan_bulanan',
                    'input_type' => 'currency',
                    'unit' => 'Rp',
                    'unit_position' => 'prefix',
                    'is_basis' => true,
                    'min_value' => 0,
                    'translations' => [
                        'id' => ['label' => 'Rata-rata tagihan listrik Anda per bulan?', 'placeholder' => '0', 'unit_label' => 'Rp'],
                        'en' => ['label' => 'Your average monthly electricity bill?', 'placeholder' => '0', 'unit_label' => 'Rp'],
                    ],
                ],
            ],
            'factors' => [
                ['key' => 'pln', 'value' => 0.87000, 'unit' => 'kgCO2e/kWh', 'basis_unit' => 'kWh',
                    'notes' => 'Faktor emisi grid. Konfirmasi angka resmi per wilayah/tahun.'],
                ['key' => 'energi_bersih', 'value' => 0.00000, 'unit' => 'kgCO2e/kWh', 'basis_unit' => 'kWh'],
                ['key' => 'hybrid', 'value' => 0.43500, 'unit' => 'kgCO2e/kWh', 'basis_unit' => 'kWh',
                    'notes' => 'Diasumsikan 50% grid. Sesuaikan dengan meta.renewable_share bila porsinya dibuat dinamis.'],
            ],
        ];
    }

    /**
     * CATATAN: layar untuk langkah ini tidak ikut dilampirkan, jadi daftar
     * alat di bawah disusun dari tabel "C. Peralatan Rumah Tangga" pada
     * halaman hasil (Lampu Pijar, AC) lalu dilengkapi alat umum lainnya.
     * Perlu dicocokkan ulang dengan desain aslinya.
     */
    private function peralatanRumahTangga(): array
    {
        return [
            'code' => 'peralatan_rumah_tangga',
            'slug' => 'peralatan-rumah-tangga',
            'calculator_key' => 'appliance_usage',
            'icon' => 'lamp',
            'is_repeatable' => true,
            'max_entries' => 20,
            'sort_order' => 3,
            'translations' => [
                'id' => [
                    'name' => 'Peralatan Rumah Tangga',
                    'title' => 'Peralatan Rumah Tangga',
                    'subtitle' => 'Alat elektronik apa yang Anda gunakan sehari-hari?',
                    'summary_label' => 'Total Emisi peralatan Anda',
                    'add_entry_label' => 'Tambah Peralatan Lain',
                ],
                'en' => [
                    'name' => 'Home Appliances',
                    'title' => 'Home Appliances',
                    'subtitle' => 'Which appliances do you use daily?',
                    'summary_label' => 'Your Total Appliance Emissions',
                    'add_entry_label' => 'Add Another Appliance',
                ],
            ],
            'fields' => [
                [
                    'code' => 'jenis_alat',
                    'input_type' => 'single_choice',
                    'display_style' => 'card',
                    // numeric_value = daya rata-rata (watt), dipakai menghitung kWh.
                    'translations' => [
                        'id' => ['label' => 'Alat elektronik apa yang Anda gunakan?'],
                        'en' => ['label' => 'Which appliance do you use?'],
                    ],
                    'options' => [
                        ['code' => 'lampu_pijar', 'numeric_value' => 60, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Lampu Pijar'], 'en' => ['label' => 'Incandescent Lamp']]],
                        ['code' => 'lampu_led', 'numeric_value' => 10, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Lampu LED'], 'en' => ['label' => 'LED Lamp']]],
                        ['code' => 'ac', 'numeric_value' => 840, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'AC'], 'en' => ['label' => 'Air Conditioner']]],
                        ['code' => 'kulkas', 'numeric_value' => 150, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Kulkas'], 'en' => ['label' => 'Refrigerator']]],
                        ['code' => 'televisi', 'numeric_value' => 120, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Televisi'], 'en' => ['label' => 'Television']]],
                        ['code' => 'mesin_cuci', 'numeric_value' => 500, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Mesin Cuci'], 'en' => ['label' => 'Washing Machine']]],
                        ['code' => 'kipas_angin', 'numeric_value' => 60, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Kipas Angin'], 'en' => ['label' => 'Electric Fan']]],
                        ['code' => 'rice_cooker', 'numeric_value' => 400, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Rice Cooker'], 'en' => ['label' => 'Rice Cooker']]],
                        ['code' => 'water_heater', 'numeric_value' => 1500, 'numeric_unit' => 'watt',
                            'translations' => ['id' => ['label' => 'Water Heater'], 'en' => ['label' => 'Water Heater']]],
                    ],
                ],
                [
                    'code' => 'jumlah_unit',
                    'input_type' => 'number',
                    'unit' => 'Unit',
                    'min_value' => 1,
                    'max_value' => 100,
                    'step' => 1,
                    'translations' => [
                        'id' => ['label' => 'Berapa unit yang Anda miliki?', 'placeholder' => 'Contoh: 2', 'unit_label' => 'Unit'],
                        'en' => ['label' => 'How many units do you have?', 'placeholder' => 'e.g. 2', 'unit_label' => 'Units'],
                    ],
                ],
                [
                    'code' => 'durasi_harian',
                    'input_type' => 'number',
                    'unit' => 'Jam/Hari',
                    'is_basis' => true,
                    'min_value' => 0,
                    'max_value' => 24,
                    'step' => 0.5,
                    'decimals' => 1,
                    'translations' => [
                        'id' => ['label' => 'Berapa lama digunakan dalam sehari?', 'placeholder' => 'Contoh: 8', 'unit_label' => 'Jam/Hari'],
                        'en' => ['label' => 'How long is it used per day?', 'placeholder' => 'e.g. 8', 'unit_label' => 'Hours/Day'],
                    ],
                ],
            ],
            'factors' => [
                // Tanpa field kunci: satu faktor grid untuk seluruh peralatan.
                ['key' => '', 'value' => 0.87000, 'unit' => 'kgCO2e/kWh', 'basis_unit' => 'kWh',
                    'notes' => 'Samakan dengan faktor grid pada kategori Daya Rumah Tangga.'],
            ],
        ];
    }
}
