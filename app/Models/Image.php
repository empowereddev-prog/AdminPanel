<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class Image extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $guarded = [
        'section_id',
        'section',
        'original_image_name',
        'image_name',
        'type',
        'video_name',
    ];

    public static function getImagesOfProduct($id){
        $data = self::where('section_id', $id)->where('section' , 'product')->get('image_name');
        return $data;
    }
}
