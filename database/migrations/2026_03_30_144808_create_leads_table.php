<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data yang dikumpulkan di langkah "Isi Data Diri" (progress 99%).
 *
 * `intent` menyimpan jawaban "Kalau ada cara sederhana untuk mengurangi dampak
 * emisimu, apakah kamu mau mencobanya?" — pertanyaan kualifikasi lead, bukan
 * pertanyaan emisi, sehingga tidak ikut masuk ke emission_fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('whatsapp_number');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('intent', ['belum_tahu', 'mungkin', 'tentu'])->nullable();
            $table->string('locale', 10)->default('id');

            // Jejak persetujuan Syarat & Ketentuan + Kebijakan Privasi.
            $table->timestamp('consented_at')->nullable();
            $table->string('consent_version', 20)->nullable();

            $table->timestamps();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
