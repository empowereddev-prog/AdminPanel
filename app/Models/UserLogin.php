<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id','logged_in_at'];
    protected $casts = ['logged_in_at'=>'datetime'];
}
