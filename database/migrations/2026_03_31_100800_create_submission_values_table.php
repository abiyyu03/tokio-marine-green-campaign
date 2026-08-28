<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu jawaban pengguna.
 *
 * Struktur lama memakai `submission_entries` + `entry_index` untuk mendukung
 * kartu berulang ("Tambah Kendaraan Lain"). Desain baru tidak punya kategori
 * berulang, jadi lapisan itu dihapus dan jawaban menempel langsung ke
 * submission — jauh menyederhanakan pembacaan state di wizard.
 *
 * `points` dan `kg_co2e_year` disalin dari opsi saat jawaban disimpan supaya
 * hasil lama tidak ikut berubah ketika angka referensi diperbarui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_field_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emission_field_option_id')->nullable()
                ->constrained('emission_field_options')->nullOnDelete();

            $table->unsignedSmallInteger('points')->default(0);
            $table->decimal('value_numeric', 18, 6)->nullable();
            $table->decimal('kg_co2e_year', 18, 4)->nullable();
            $table->text('value_text')->nullable();

            $table->timestamps();

            // Satu jawaban per field: field single_choice menimpa jawaban lama.
            $table->unique(['submission_id', 'emission_field_id'], 'svalue_sub_field_unique');
            $table->index(['submission_id', 'emission_category_id'], 'svalue_sub_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_values');
    }
};
