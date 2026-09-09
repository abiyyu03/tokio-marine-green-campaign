<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "email hasil sudah dikirim".
 *
 * Dipakai untuk tiga hal: mencegah kirim ganda bila submit diulang, membuat
 * halaman hasil jujur (kalimat "cek email kamu" hanya tampil kalau emailnya
 * memang berangkat), dan memberi tim kampanye jawaban saat peserta lapor
 * emailnya tidak masuk.
 *
 * Kolomnya nullable dan tanpa nilai bawaan: submission lama otomatis terbaca
 * sebagai "belum pernah dikirimi email", yang memang benar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->timestamp('result_email_sent_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('result_email_sent_at');
        });
    }
};
