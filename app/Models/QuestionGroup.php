<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionGroup extends Model
{
    protected $table = 'question_groups';

    protected $fillable = ['code', 'name', 'lang'];

    public function questions()
    {
        return $this->hasMany(Question::class, 'group_id');
    }
}
