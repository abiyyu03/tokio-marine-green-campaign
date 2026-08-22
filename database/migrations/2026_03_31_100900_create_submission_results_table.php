<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hasil akhir yang dibekukan saat submission selesai, supaya halaman hasil
 * tetap konsisten walaupun faktor emisi diperbarui di kemudian hari.
 *
 * Semua emisi disimpan dalam kg CO2e per TAHUN sebagai satu-satunya sumber
 * kebenaran; nilai per hari / per bulan / ton diturunkan saat ditampilkan.
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
            $table->unsignedSmallInteger('household_size')->nullable();
            $table->decimal('total_kg_co2e_year', 18, 4);
            $table->decimal('per_capita_ton_co2e_year', 12, 4);
            $table->timestamp('computed_at');
            $table->timestamps();
        });

        // Sumber angka pie chart + baris "Total Emisi" tiap tabel di halaman hasil.
        Schema::create('submission_category_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
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
