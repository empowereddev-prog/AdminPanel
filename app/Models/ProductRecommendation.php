<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ProductRecommendation extends Model implements AuditableContract
{
    use Auditable;
    protected $fillable = ['title', 'priority', 'description', 'image', 'status', 'color', 'title_color', 'url'];
}
