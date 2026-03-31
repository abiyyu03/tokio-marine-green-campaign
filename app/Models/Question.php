<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['label', 'order', 'lang', 'group_id'];

    public function group()
    {
        return $this->belongsTo(QuestionGroup::class, 'group_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
