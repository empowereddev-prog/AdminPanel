<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
// use OwenIt\Auditing\Auditable;

class ChildPerformedActivity extends Model  
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    public function activity()
    {
        return $this->hasOne(Activity::class, 'id', 'activity_id');
    }
}
