<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsResult extends Model
{
    protected $fillable = ['leads_id'];

    public function leads()
    {
        return $this->belongsTo(Leads::class);
    }
}
