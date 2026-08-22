<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faktor emisi. Menggantikan kolom `answers.score` yang bertipe integer dan
 * karenanya tidak bisa menampung angka desimal kecil, apalagi faktor kombinasi.
 *
 * `factor_key` dibentuk dari kode opsi pada field ber-flag is_factor_key,
 * digabung dengan "|" mengikuti urutan sort_order field:
 *   Mobil + Bensin  -> "mobil|bensin"
 *   Motor + Listrik -> "motor|listrik"
 *   100% PLN        -> "pln"
 * Kategori tanpa field kunci memakai factor_key = "" (satu faktor tunggal).
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
            $table->string('basis_unit')->nullable();     // km, kWh, liter
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
