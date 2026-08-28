<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan jawaban. Setiap opsi membawa DUA angka yang saling melengkapi:
 *
 *   `points`         -> menaikkan gauge "Skor Kamu" (0-100) secara langsung.
 *   angka emisi      -> bahan hitung ton CO2e di halaman hasil, bentuknya
 *                       tergantung calculator_key kategorinya:
 *                         numeric_value  = basis (km/hari, kWh/tahun)
 *                         kg_co2e_year   = kontribusi emisi tahunan langsung
 *
 * Contoh:
 *   "10 - 25 km / hari"  -> points 7,  numeric_value 17.5 (km/day)
 *   "Kulkas Standar"     -> points 4,  numeric_value 480  (kwh/year)
 *   "Sedang (3-5x/mg)"   -> points 5,  kg_co2e_year 62.4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_field_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('image_file')->nullable();     // gambar kartu moda transportasi
            $table->string('icon')->nullable();

            $table->unsignedSmallInteger('points')->default(0);

            $table->decimal('numeric_value', 18, 6)->nullable();
            $table->string('numeric_unit')->nullable();
            $table->decimal('kg_co2e_year', 18, 4)->nullable();

            // Kode faktor emisi yang dipakai bila field ini is_factor_key.
            $table->string('factor_key')->nullable();

            $table->json('meta')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['emission_field_id', 'code'], 'eopt_field_code_unique');
            $table->index(['emission_field_id', 'is_active', 'sort_order'], 'eopt_field_active_idx');
        });

        Schema::create('emission_field_option_translations', function (Blueprint $table) {
            $table->id();
            // Nama FK dinamai manual: nama otomatis mencapai 67 karakter,
            // melewati batas 64 karakter identifier MySQL.
            $table->foreignId('emission_field_option_id')
                ->constrained(indexName: 'eopt_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label');                     // "Mobil Bensin (BBM)"
            $table->string('summary_label')->nullable(); // "Mobil Bensin" — versi pendek untuk sidebar
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['emission_field_option_id', 'locale'], 'eopt_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_field_option_translations');
        Schema::dropIfExists('emission_field_options');
    }
};
