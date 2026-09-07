<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class AdminNotification extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['user_id', 'title', 'message', 'is_sent'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
