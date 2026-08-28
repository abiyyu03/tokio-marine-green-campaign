<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blok "setiap tahunnya emisi harianmu setara dengan:" di halaman hasil:
 *   1.600 Liter Bensin yang dikonsumsi kendaraan
 *   18 Kali Penerbangan domestik antarkota
 *   192 Pohon Dewasa selama 1 tahun untuk menyerap seluruh emisimu
 *
 * Angka tampil = total kg CO2e / `kg_co2e_per_unit`, dibulatkan sesuai
 * `decimals`. Teksnya di tabel terjemahan memakai placeholder :value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_equivalences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // bensin, penerbangan, pohon
            $table->decimal('kg_co2e_per_unit', 18, 6);
            $table->unsignedTinyInteger('decimals')->default(0);
            $table->string('icon')->nullable();
            $table->string('source')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('emission_equivalence_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_equivalence_id')
                ->constrained(indexName: 'eequiv_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);
            // "**:value Liter Bensin** yang dikonsumsi kendaraan"
            $table->string('template');
            $table->timestamps();

            $table->unique(['emission_equivalence_id', 'locale'], 'eequiv_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_equivalence_translations');
        Schema::dropIfExists('emission_equivalences');
    }
};
