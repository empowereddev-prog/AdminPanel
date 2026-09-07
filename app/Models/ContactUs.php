<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model 
{
    // use AuditableTrait;
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'subject',
        // 'phone_no',
        'message',
    ];
}
