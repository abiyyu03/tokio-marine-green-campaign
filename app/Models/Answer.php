<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['choice_label', 'score', 'answer', 'question_id', 'question_type'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
