<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Label satuan pendek untuk blok "setara dengan".
 *
 * `template` berisi kalimat utuh ("**:value Liter Bensin** yang dikonsumsi
 * kendaraan") yang cocok untuk halaman hasil. Laporan cetak memisah angka
 * dari labelnya — angkanya besar, labelnya kecil di bawahnya — sehingga
 * butuh potongan teks tanpa angka di dalamnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_equivalence_translations', function (Blueprint $table) {
            $table->string('unit_label')->nullable()->after('template');
        });
    }

    public function down(): void
    {
        Schema::table('emission_equivalence_translations', function (Blueprint $table) {
            $table->dropColumn('unit_label');
        });
    }
};
