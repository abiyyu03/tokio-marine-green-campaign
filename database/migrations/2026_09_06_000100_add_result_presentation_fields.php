<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan untuk "Dokumentasi Logic & UI Copy Result Page".
 *
 * Dokumen itu menetapkan tiga hal yang belum punya tempat di skema:
 *
 * 1. Porsi emisi tiap sektor terhadap total (Transportasi 40%, Listrik 35%,
 *    Konsumsi & Sampah 25%) -> emission_categories.emission_share.
 * 2. Kalimat pembanding yang berbeda per tier ("...sudah sangat baik" vs
 *    "...mendekati rata-rata" vs "...di atas rata-rata")
 *    -> result_tier_translations.benchmark_note.
 * 3. Rentang "jika 100 orang sepertimu" yang juga berbeda per tier
 *    (7,5-8 | 8-16 | 16-23 Ton) -> result_tiers.community_avoided_*.
 *
 * `raw_kg_co2e_year` menyimpan hasil hitung faktor emisi apa adanya, terpisah
 * dari angka yang ditampilkan. Keduanya dibutuhkan: yang tampil mengikuti
 * rentang tier sesuai dokumen, yang mentah tetap dipakai untuk analisa dan
 * untuk kalibrasi ulang faktor di kemudian hari.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_categories', function (Blueprint $table) {
            $table->decimal('emission_share', 5, 4)->nullable()->after('max_points');
        });

        Schema::table('result_tiers', function (Blueprint $table) {
            $table->decimal('community_avoided_min_ton_co2e', 12, 4)->nullable()
                ->after('approx_max_ton_co2e');
            $table->decimal('community_avoided_max_ton_co2e', 12, 4)->nullable()
                ->after('community_avoided_min_ton_co2e');
        });

        Schema::table('result_tier_translations', function (Blueprint $table) {
            $table->text('benchmark_note')->nullable()->after('headline');
        });

        Schema::table('submission_results', function (Blueprint $table) {
            $table->decimal('raw_kg_co2e_year', 18, 4)->nullable()->after('total_kg_co2e_year');
        });

        Schema::table('submission_category_results', function (Blueprint $table) {
            $table->decimal('raw_kg_co2e_year', 18, 4)->nullable()->after('kg_co2e_year');
        });
    }

    public function down(): void
    {
        Schema::table('submission_category_results', function (Blueprint $table) {
            $table->dropColumn('raw_kg_co2e_year');
        });

        Schema::table('submission_results', function (Blueprint $table) {
            $table->dropColumn('raw_kg_co2e_year');
        });

        Schema::table('result_tier_translations', function (Blueprint $table) {
            $table->dropColumn('benchmark_note');
        });

        Schema::table('result_tiers', function (Blueprint $table) {
            $table->dropColumn(['community_avoided_min_ton_co2e', 'community_avoided_max_ton_co2e']);
        });

        Schema::table('emission_categories', function (Blueprint $table) {
            $table->dropColumn('emission_share');
        });
    }
};
