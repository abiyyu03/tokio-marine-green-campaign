<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen legal yang harus disetujui peserta sebelum submit, mis. Syarat &
 * Ketentuan di step terakhir kalkulator.
 *
 * Isinya sengaja di database, bukan di lang/, supaya tim kampanye bisa
 * merevisi teks lewat phpMyAdmin tanpa deploy berkas. `body` ditulis dalam
 * Markdown. `version` disalin ke leads.consent_version saat peserta setuju,
 * jadi naikkan nilainya setiap kali isi dokumen berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // syarat_ketentuan
            $table->string('version', 20);                // muat di leads.consent_version (20)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('legal_document_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legal_document_id')
                ->constrained(indexName: 'legal_doc_trans_fk')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->longText('body');                     // Markdown
            $table->timestamps();

            $table->unique(['legal_document_id', 'locale'], 'legal_doc_trans_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_document_translations');
        Schema::dropIfExists('legal_documents');
    }
};
