<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori hasil berdasarkan SKOR POIN 0-100, bukan lagi ton CO2e:
 *   Dampak Ringan 0-30 | Dampak Sedang 31-60 | Dampak Tinggi 61-100
 *
 * Rentang bersifat tertutup di kedua ujung (min <= skor <= max) supaya cocok
 * dengan legenda gauge di desain yang menulis "(0-30)", "(31-60)", "(61-100)".
 *
 * `approx_*_ton_co2e` hanya untuk tabel "Total poin dikategorikan" di sidebar
 * halaman hasil ("~1,2 - 2 ton CO2/tahun"); angka emisi yang sebenarnya tetap
 * dihitung dari jawaban, bukan dari rentang ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // ringan, sedang, tinggi
            $table->unsignedTinyInteger('min_score')->default(0);
            $table->unsignedTinyInteger('max_score')->default(100);
            $table->decimal('approx_min_ton_co2e', 12, 4)->nullable();
            $table->decimal('approx_max_ton_co2e', 12, 4)->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('color')->nullable();          // warna segmen gauge & badge
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('result_tier_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_tier_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label');                      // "Dampak Tinggi"
            $table->string('badge_label');                // "Climate Mover"
            $table->string('headline');                   // "Kabar Baik Untukmu, :name!"
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['result_tier_id', 'locale'], 'rtier_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_tier_translations');
        Schema::dropIfExists('result_tiers');
    }
};
