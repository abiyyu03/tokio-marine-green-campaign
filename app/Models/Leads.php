<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leads extends Model
{
    protected $table = 'leads';

    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'dob',
        'domicile',
        'locale',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'lead_id');
    }
}
