<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class QuizCategory extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'quiz_categories';
    protected $guarded = [];


    public function questions()
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_category_id', 'id');
    }
    public function quiz()
    {
        return $this->hasMany(Quiz::class, 'quiz_category_id', 'id');
    }
}
