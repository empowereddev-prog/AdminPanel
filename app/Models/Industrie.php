<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Industrie extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $fillable = [
        'industries_banners_id',
        'description',
        'author_name',
        'designation',
        'image',
        'video',
    ];
    public function banner()
    {
        return $this->belongsTo(IndustriesBanner::class, 'industries_banners_id');
    }
}
