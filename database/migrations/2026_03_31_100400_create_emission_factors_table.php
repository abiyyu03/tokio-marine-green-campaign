<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faktor emisi per kategori, dipisah dari opsi supaya angkanya bisa
 * diperbarui di satu tempat tanpa menyentuh teks pertanyaan.
 *
 * `factor_key` dicocokkan dengan `emission_field_options.factor_key` milik
 * opsi terpilih pada field ber-flag is_factor_key:
 *   Mobil Bensin (BBM) -> "mobil_bbm"   -> 0.192 kgCO2e/km
 *   Motor Listrik (EV) -> "motor_ev"    -> 0.021 kgCO2e/km
 * Kategori tanpa field kunci memakai factor_key = "" (satu faktor tunggal),
 * mis. faktor grid listrik PLN untuk kategori Listrik Rumah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
            $table->string('factor_key')->default('');
            $table->decimal('value', 18, 8);
            $table->string('unit');                       // kgCO2e/km, kgCO2e/kWh
            $table->string('basis_unit')->nullable();     // km/day, kwh/year
            $table->string('source')->nullable();         // wajib diisi saat data final
            $table->unsignedSmallInteger('reference_year')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['emission_category_id', 'factor_key'], 'efactor_cat_key_unique');
            $table->index(['emission_category_id', 'is_active'], 'efactor_cat_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_factors');
    }
};
