<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil akhir yang dibekukan saat submission selesai, supaya halaman hasil
 * tetap konsisten walaupun angka referensi diperbarui di kemudian hari.
 *
 * `score` adalah angka yang tampil di gauge "Skor Kamu" (0-100).
 * Emisi disimpan dalam kg CO2e per TAHUN sebagai satu-satunya sumber
 * kebenaran; tampilan "±3,8 Ton CO2 / Tahun" diturunkan saat render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('result_tier_id')->nullable()
                ->constrained('result_tiers')->nullOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->decimal('total_kg_co2e_year', 18, 4)->default(0);
            $table->timestamp('computed_at');
            $table->timestamps();
        });

        // Sumber angka kartu per kategori di halaman hasil
        // ("Transportasi ±1,52 Ton CO2 / Tahun").
        Schema::create('submission_category_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->decimal('kg_co2e_year', 18, 4)->default(0);
            $table->decimal('percentage', 7, 4)->default(0);
            $table->timestamps();

            $table->unique(['submission_id', 'emission_category_id'], 'scatres_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_category_results');
        Schema::dropIfExists('submission_results');
    }
};
