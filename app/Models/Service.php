<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Service extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $guarded = [];

    public static function getAllServices()
    {
        return self::orderBy('id', 'DESC')->get();
    }

    public static function getAllServiceEdit($id)
    {
        return self::where('id', $id)->first();
    }

    public function serviceCategories()
    {
        return $this->hasMany(ServiceCategories::class, 'service_id');
    }
}
