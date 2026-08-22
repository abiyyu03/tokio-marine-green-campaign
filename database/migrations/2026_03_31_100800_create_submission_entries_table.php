<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `submission_entries` = satu kartu isian dalam sebuah kategori.
 * `entry_index` inilah yang membuat "Tambah Kendaraan Lain" bisa direkonstruksi:
 * kendaraan ke-1 dan ke-2 jadi dua entry terpisah dengan set value masing-masing.
 * Kategori non-repeatable cukup memakai entry_index = 0.
 *
 * `submission_values` menyimpan jawaban apa pun bentuknya: pilihan (option_id),
 * angka (value_numeric), atau teks bebas (value_text).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('entry_index')->default(0);

            // Jejak audit hasil hitung per kartu ("7,7 ton CO2/Hari" di UI).
            $table->foreignId('emission_factor_id')->nullable()
                ->constrained('emission_factors')->nullOnDelete();
            $table->decimal('kg_co2e_year', 18, 4)->nullable();
            $table->json('calc_meta')->nullable();       // basis, faktor, langkah turunan

            $table->timestamps();

            $table->unique(
                ['submission_id', 'emission_category_id', 'entry_index'],
                'sentry_unique'
            );
        });

        Schema::create('submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_field_option_id')->nullable()
                ->constrained('emission_field_options')->nullOnDelete();
            $table->decimal('value_numeric', 18, 4)->nullable();
            $table->text('value_text')->nullable();
            $table->timestamps();

            // Bukan unique: field multiple_choice boleh punya beberapa baris.
            $table->index(['submission_entry_id', 'emission_field_id'], 'svalue_entry_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_values');
        Schema::dropIfExists('submission_entries');
    }
};
