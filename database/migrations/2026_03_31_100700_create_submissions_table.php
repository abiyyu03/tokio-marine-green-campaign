<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu sesi pengisian kalkulator.
 *
 * `lead_id` sengaja nullable: form nama/email/WhatsApp baru muncul di langkah
 * terakhir, sehingga jawaban langkah 1-3 harus bisa disimpan lebih dulu.
 * `uuid` dipakai untuk melanjutkan draft dan sebagai kunci URL halaman hasil
 * tanpa perlu login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('locale', 10)->default('id');
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
