<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blok "Dampak Kolektif Komunitas" di halaman hasil:
 *   Mengurangi 25 Ton sampah langsung ke TPA
 *   Mengolah 12 Ton plastik keras
 *   Mengubah 8 Ton multilayer menjadi bahan bangunan bermanfaat
 *
 * `cohort_size` (100 orang) dan `avoided_*` menyusun kalimat pembuka
 * "jika 100 orang dengan profil sepertimu ..., 16 - 23 Ton CO2 dapat
 * dihindari setiap tahunnya" — disimpan di tabel setelan kampanye.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_impacts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // sampah_tpa, plastik_keras, multilayer
            $table->decimal('value', 12, 2);
            $table->string('unit')->nullable();           // "Ton"
            $table->unsignedSmallInteger('reference_year')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('community_impact_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_impact_id')
                ->constrained(indexName: 'cimpact_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);
            // "Mengurangi **:value Ton** sampah langsung ke TPA"
            $table->string('template');
            $table->timestamps();

            $table->unique(['community_impact_id', 'locale'], 'cimpact_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_impact_translations');
        Schema::dropIfExists('community_impacts');
    }
};
