<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Action;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Mood extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    protected $guarded = [];

    public function activity()
    {
        return $this->hasMany(Activity::class);
    }
}
