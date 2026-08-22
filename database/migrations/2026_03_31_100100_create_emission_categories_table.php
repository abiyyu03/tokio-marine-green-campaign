<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu langkah wizard (Transportasi Darat, Daya Rumah Tangga,
 * Peralatan Rumah Tangga). Semua perilaku langkah dikendalikan dari sini,
 * termasuk apakah entri-nya boleh ditambah berulang ("Tambah Kendaraan Lain").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // transportasi_darat, daya_rumah_tangga, ...
            $table->string('slug')->unique();
            $table->string('calculator_key');           // dipetakan ke class kalkulator di PHP
            $table->string('icon')->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedSmallInteger('max_entries')->nullable();
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
            $table->string('name');                     // "Transportasi Darat"
            $table->string('title')->nullable();        // "Transportasi Darat"
            $table->string('subtitle')->nullable();     // "Bagaimana Cara Anda Beraktivitas Hari Ini?"
            $table->string('summary_label')->nullable();// "Total Emisi perjalanan Anda"
            $table->string('add_entry_label')->nullable(); // "Tambah Kendaraan Lain"
            $table->text('description')->nullable();
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