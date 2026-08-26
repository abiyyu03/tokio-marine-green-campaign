<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu pertanyaan di dalam langkah wizard.
 *
 * Desain baru hanya memakai pilihan bertombol (single_choice); tidak ada lagi
 * input angka bebas. Karena itu kolom pengatur input numerik (min/max/step/
 * decimals) dihapus — angka basis perhitungan kini menempel pada opsi
 * (`emission_field_options.numeric_value`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
            $table->string('code');                     // moda_transportasi, jarak_harian, ...
            $table->enum('input_type', ['single_choice', 'multiple_choice'])
                ->default('single_choice');

            // card  = kartu bergambar 2 kolom (moda transportasi)
            // pill  = tombol teks yang mengalir (sisanya)
            $table->string('display_style')->default('pill');

            // Satuan kanonik dari numeric_value opsi: km/day, kwh/year, kg/year.
            $table->string('unit')->nullable();

            $table->boolean('is_required')->default(true);
            $table->boolean('is_factor_key')->default(false); // opsi terpilih menentukan faktor emisi
            $table->boolean('is_basis')->default(false);      // opsi terpilih menyumbang angka basis

            // Tampil bersyarat; belum dipakai desain saat ini, tetap disediakan.
            $table->foreignId('depends_on_field_id')->nullable()
                ->constrained('emission_fields')->nullOnDelete();
            $table->string('depends_on_option_code')->nullable(); // null = cukup terisi apa pun

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['emission_category_id', 'code'], 'efield_cat_code_unique');
            $table->index(['emission_category_id', 'is_active', 'sort_order'], 'efield_cat_active_idx');
        });

        Schema::create('emission_field_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_field_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label');                    // "Apa moda transportasi utama yang kamu gunakan sehari-hari?"
            $table->string('summary_label')->nullable();// "Moda", "Jarak" — untuk ringkasan sidebar
            $table->string('helper_text')->nullable();
            $table->timestamps();

            $table->unique(['emission_field_id', 'locale'], 'efield_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_field_translations');
        Schema::dropIfExists('emission_fields');
    }
};
