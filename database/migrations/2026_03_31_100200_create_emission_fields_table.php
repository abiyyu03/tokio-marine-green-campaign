<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emission_category_id')->constrained()->cascadeOnDelete();
            $table->string('code');                     // moda_transportasi, jarak_harian, ...
            $table->enum('input_type', [
                'single_choice',
                'multiple_choice',
                'select',
                'number',
                'currency',
                'text',
                'date',
            ]);
            $table->string('display_style')->nullable(); // card, dropdown, radio, stepper

            // Kode kanonik untuk perhitungan; teks tampilannya ada di unit_label
            // pada tabel terjemahan (mis. "Orang" -> "People").
            $table->string('unit')->nullable();          // KM, Orang, Rp, VA
            $table->enum('unit_position', ['prefix', 'suffix'])->default('suffix');
            $table->unsignedTinyInteger('decimals')->default(0);
            $table->decimal('min_value', 16, 4)->nullable();
            $table->decimal('max_value', 16, 4)->nullable();
            $table->decimal('step', 16, 4)->nullable();

            $table->boolean('is_required')->default(true);
            $table->boolean('is_factor_key')->default(false);
            $table->boolean('is_basis')->default(false);

            // Tampil bersyarat: "Bahan bakar" baru muncul setelah moda dipilih.
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
            $table->string('label');                     // "Seberapa jauh Anda menempuh perjalanan dalam sehari?"
            $table->string('placeholder')->nullable();   // "Contoh: 30"
            $table->string('helper_text')->nullable();
            $table->string('unit_label')->nullable();     // "Orang" / "People", "Jam/Hari" / "Hours/Day"
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
