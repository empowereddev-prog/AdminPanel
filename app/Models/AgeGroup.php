<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class AgeGroup extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $fillable = ['name', 'start_age', 'end_age', 'status'];
}
