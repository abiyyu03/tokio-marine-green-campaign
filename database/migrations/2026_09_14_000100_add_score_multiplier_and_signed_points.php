<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom untuk dokumen "Skoring Kalkulator Karbon" (September 2026).
 *
 * Dokumen itu memperkenalkan dua hal yang belum bisa ditampung skema lama:
 *
 * 1. Poin negatif sebagai insentif perilaku ramah lingkungan
 *    (tas belanja reusable -5, pilah sampah -8, galon isi ulang -5).
 *    Kolom poin sebelumnya unsigned, jadi diubah menjadi signed. Skor per
 *    kategori ikut signed karena Konsumsi & Sampah bisa berjumlah negatif.
 * 2. Jarak harian sebagai PENGALI skor kendaraan, bukan penambah
 *    (Skor Transport = skor kendaraan x 0,5 | 1 | 1,5 | 2)
 *    -> score_multiplier. Ikut disalin ke submission_values seperti `points`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_field_options', function (Blueprint $table) {
            $table->smallInteger('points')->default(0)->change();
            $table->decimal('score_multiplier', 5, 2)->nullable()->after('points');
        });

        Schema::table('submission_values', function (Blueprint $table) {
            $table->smallInteger('points')->default(0)->change();
            $table->decimal('score_multiplier', 5, 2)->nullable()->after('points');
        });

        Schema::table('submission_category_results', function (Blueprint $table) {
            $table->smallInteger('score')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('submission_category_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('score')->default(0)->change();
        });

        Schema::table('submission_values', function (Blueprint $table) {
            $table->dropColumn('score_multiplier');
            $table->unsignedSmallInteger('points')->default(0)->change();
        });

        Schema::table('emission_field_options', function (Blueprint $table) {
            $table->dropColumn('score_multiplier');
            $table->unsignedSmallInteger('points')->default(0)->change();
        });
    }
};
