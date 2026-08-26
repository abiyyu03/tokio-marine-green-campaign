<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu langkah wizard emisi (Transportasi, Listrik Rumah,
 * Konsumsi & Sampah). Langkah "Isi Data Diri" TIDAK ada di sini karena
 * datanya masuk ke tabel `leads`, bukan ke perhitungan emisi.
 *
 * `calculator_key` memilih strategi hitung di App\Services\CarbonCalculator:
 *   factor_basis -> faktor emisi x jumlah numeric_value opsi terpilih,
 *                   dikali 365 bila basis_unit faktornya per hari
 *                   (Transportasi: kgCO2e/km x km/hari; Listrik: kgCO2e/kWh x kWh/tahun)
 *   direct_sum   -> penjumlahan kg_co2e_year tiap opsi terpilih (Konsumsi & Sampah)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();           // transportasi, listrik_rumah, konsumsi_sampah
            $table->string('slug')->unique();
            $table->string('calculator_key');
            $table->string('icon')->nullable();          // ikon kartu di halaman hasil
            $table->string('image_file')->nullable();    // foto panel kiri wizard
            $table->string('accent_color')->nullable();  // warna kartu kategori di halaman hasil
            $table->unsignedSmallInteger('max_points')->default(0); // jatah poin langkah ini
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('emission_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_category_id')
                ->constrained(indexName: 'ecat_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);

            // "Transportasi" — dipakai sebagai judul langkah, label kartu hasil,
            // dan penyusun label tombol ("Lanjut ke Listrik Rumah").
            $table->string('name');

            // Judul tebal di panel kiri, mis. "Konsumsi Listrik & Perangkat Rumah Tangga".
            $table->string('panel_title')->nullable();
            $table->text('panel_description')->nullable();

            $table->timestamps();

            $table->unique(['emission_category_id', 'locale'], 'ecat_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_category_translations');
        Schema::dropIfExists('emission_categories');
    }
};
