<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatteryEvent extends Model
{
    protected $fillable = ['user_id','direction','reason','points','effective_date','meta'];
    protected $casts = ['effective_date'=>'date','meta'=>'array'];
}
