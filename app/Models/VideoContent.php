<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class VideoContent extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $guarded = [];
    protected $casts = ['school_id' => 'array',];
    protected $table = 'video_contents';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
