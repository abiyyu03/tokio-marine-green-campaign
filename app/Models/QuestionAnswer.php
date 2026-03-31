<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionAnswer extends Model
{
    protected $fillable = ['leads_id', 'question_id', 'answer_id'];

    public function leads()
    {
        return $this->belongsTo(Leads::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
