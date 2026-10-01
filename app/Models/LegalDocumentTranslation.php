<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LegalDocumentTranslation extends Model
{
    protected $fillable = ['legal_document_id', 'locale', 'title', 'body'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }

    /**
     * Isi Markdown sebagai HTML. HTML mentah di dalam teks dibuang dan tautan
     * javascript: ditolak — teksnya diedit lewat phpMyAdmin, jadi jangan
     * dipercaya begitu saja sebelum ditampilkan dengan {!! !!}.
     */
    public function html(): string
    {
        return Str::markdown((string) $this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
