<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class Activity extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes,Auditable;
    protected $guarded = [];

    public function mood()
    {
        return $this->belongsTo(Mood::class, 'mood');
    }
}
