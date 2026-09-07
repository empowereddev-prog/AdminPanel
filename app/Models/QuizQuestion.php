<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class QuizQuestion extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;
    protected $guarded = [];

    public function options()
    {
        return $this->hasMany(QuizQuestionOption::class, 'question_id', 'id');
    }

    public function correctOption()
    {
        return $this->hasOne(QuizQuestionOption::class)->where('is_correct', true);
    }
}
