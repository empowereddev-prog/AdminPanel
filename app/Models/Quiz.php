<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $guarded = [""];

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id', 'id');
    }

    public function userAttemptQuizzes()
    {
        return $this->hasMany(UserAttemptQuiz::class, 'quiz_id', 'id');
    }

    public function quizCategory()
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id', 'id');
    }
}
