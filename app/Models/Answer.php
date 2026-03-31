<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $fillable = ['choice_label', 'score', 'answer', 'image_file', 'question_id', 'question_type'];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
