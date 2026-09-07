<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableContract;

class Testmonial extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    protected $guarded = [];
}
