<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Isi kotak "Kabar Baik Untukmu, :name!" -> "Rekomendasi Aksi Khusus :name".
 *
 * Penargetan bertingkat, keduanya boleh null (= berlaku untuk semua):
 *   result_tier_id       -> hanya untuk skor Dampak Tinggi, misalnya
 *   emission_category_id -> hanya bila kategori itu penyumbang terbesar
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('result_tier_id')->nullable()
                ->constrained('result_tiers')->cascadeOnDelete();
            $table->foreignId('emission_category_id')->nullable()
                ->constrained('emission_categories')->cascadeOnDelete();
            $table->string('icon')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['result_tier_id', 'emission_category_id'], 'reco_target_idx');
        });

        Schema::create('recommendation_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')
                ->constrained(indexName: 'reco_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->text('body');                         // boleh memuat :name
            $table->timestamps();

            $table->unique(['recommendation_id', 'locale'], 'reco_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_translations');
        Schema::dropIfExists('recommendations');
    }
};
