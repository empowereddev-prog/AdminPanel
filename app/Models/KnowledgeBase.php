<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class KnowledgeBase extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    use SoftDeletes;

    protected $fillable = ['media', 'title', 'description', 'age_range', 'category', 'school', 'status'];

    protected $casts = [
        'age_range' => 'array',
        'category' => 'array',
        'school' => 'array',
    ];
    public function ageRange()
    {
        return $this->belongsTo(AgeGroup::class, 'age_range');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category');
    }
}
