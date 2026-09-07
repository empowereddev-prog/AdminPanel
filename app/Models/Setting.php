<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Setting extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $guarded = [];
    public function updateSetting($where, $value)
    {
        $settings = Setting::where('title', $where['title'])
            ->update($value);
        return $settings;
    }
}
