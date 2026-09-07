<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class AppNotification extends Model implements AuditableContract
{
    use HasFactory,Auditable;

    protected $guarded = [];
    protected $casts = [
        'json_body' => 'array', // <-- ye add karo
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
