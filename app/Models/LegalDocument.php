<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Dokumen legal yang disetujui peserta (Syarat & Ketentuan). Teksnya diedit
 * langsung di database — lihat migrasi 2026_10_02_000100.
 */
class LegalDocument extends Model
{
    use HasTranslations;

    public const TERMS = 'syarat_ketentuan';

    protected string $translationClass = LegalDocumentTranslation::class;

    protected $fillable = ['code', 'version', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Dokumen aktif untuk sebuah kode, atau null bila belum diisi di server. */
    public static function active(string $code): ?self
    {
        return static::query()
            ->withTranslation()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }

    public function html(?string $locale = null): string
    {
        return $this->translation($locale)?->html() ?? '';
    }
}
