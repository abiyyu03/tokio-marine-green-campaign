<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori hasil berdasarkan rentang emisi tahunan per kapita.
 * Menghasilkan badge "Champion of the Earth" dan judul
 * "Kamu adalah Pahlawan Hijau, {name}!" di halaman hasil.
 *
 * Batas rentang bersifat setengah terbuka: min <= nilai < max.
 * min null = tidak ada batas bawah, max null = tidak ada batas atas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // champion, low, average, high
            $table->decimal('min_ton_co2e', 12, 4)->nullable();
            $table->decimal('max_ton_co2e', 12, 4)->nullable();
            $table->string('badge_icon')->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('result_tier_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_tier_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('badge_label');                // "Champion of the Earth"
            $table->string('headline');                   // "Kamu adalah Pahlawan Hijau, :name!"
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
