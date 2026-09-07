<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class Category extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $table = 'category';
    protected $fillable = ['color','title_color','category_name','category_name_chinese', 'status'];
}
