<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Angka pembanding di halaman hasil, mis. kalimat
 * "berada di atas rata-rata masyarakat Indonesia (2 - 2,5 Ton CO2/tahun)".
 *
 * Desain menampilkan RENTANG, bukan satu angka, sehingga `max_value`
 * disediakan; bila null, `value` diperlakukan sebagai angka tunggal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // indonesia, global, asean
            $table->decimal('value', 12, 4);
            $table->decimal('max_value', 12, 4)->nullable();
            $table->string('unit')->default('tCO2e/capita/year');
            $table->boolean('is_primary')->default(false); // dipakai di kalimat pembanding utama
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
            $table->string('label');                      // "rata-rata masyarakat Indonesia"
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
