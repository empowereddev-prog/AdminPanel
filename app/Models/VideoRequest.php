<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class VideoRequest extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'username',
        'email',
        'user_message',
        'status',
        'admin_notes',
    ];
}
