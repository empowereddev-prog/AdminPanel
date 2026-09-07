<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class Child extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(User::class);
    }

    public function userAvatar()
{
    return $this->hasOne(UserAvtarImage::class, 'child_id', 'id');
}
}
