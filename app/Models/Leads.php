<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leads extends Model
{
    protected $table = "leads";

    protected $fillable = [
        'name',
        'dob',
        'telp_number',
        'email',
        'domicille'
    ];
}
