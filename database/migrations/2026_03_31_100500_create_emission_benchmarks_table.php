<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Angka pembanding di sidebar halaman hasil:
 * Rata-rata Global 6.26 / ASEAN 7.8 / Indonesia 3.62 ton CO2-eq/Tahun/Kapita.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // global, asean, indonesia
            $table->decimal('value', 12, 4);
            $table->string('unit')->default('tCO2e/capita/year');
            $table->string('source')->nullable();
            $table->unsignedSmallInteger('reference_year')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('emission_benchmark_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_benchmark_id')
                ->constrained(indexName: 'ebench_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label');                      // "Rata-rata Global"
            $table->timestamps();

            $table->unique(['emission_benchmark_id', 'locale'], 'ebench_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_benchmark_translations');
        Schema::dropIfExists('emission_benchmarks');
    }
};
