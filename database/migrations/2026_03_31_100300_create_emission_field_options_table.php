<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan untuk field bertipe choice/select. Menggantikan tabel `answers` lama.
 *
 * `numeric_value` menampung angka bawaan opsi sehingga tabel referensi khusus
 * (daftar tarif PLN, daftar watt alat elektronik) tidak perlu dibuat terpisah:
 *   - opsi "900 VA"      -> numeric_value 900,  numeric_unit "VA",
 *                           meta {"tariff_per_kwh": 1352}
 *   - opsi "AC"          -> numeric_value 840,  numeric_unit "watt"
 *   - opsi "Hybrid"      -> meta {"renewable_share": 0.5}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_field_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_field_id')->constrained()->cascadeOnDelete();
            $table->string('code');                      // mobil, motor, bensin, solar, pln, ...
            $table->string('image_file')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('numeric_value', 18, 6)->nullable();
            $table->string('numeric_unit')->nullable();
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
            $table->string('label');                     // "Mobil", "Bensin", "100% PLN"
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
