<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data dari langkah "Isi Data Diri".
 */
class Leads extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'dob',
        'gender',
        'intent',
        'locale',
        'consented_at',
        'consent_version',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'consented_at' => 'datetime',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'lead_id');
    }

    /** Nama panggilan untuk sapaan halaman hasil: "Halo, Rian!" */
    public function firstName(): string
    {
        return trim(explode(' ', trim($this->name))[0] ?? $this->name);
    }
}
