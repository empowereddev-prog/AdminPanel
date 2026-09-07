<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class QuizQuestionOption extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;
    protected $guarded = [];


    public function question()
    {
        return $this->belongsTo(QuizQuestion::class);
    }
}
