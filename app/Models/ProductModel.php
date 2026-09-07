<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ProductModel extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    public $table = "products";
    protected $guarded = [];

    public static function saveProductDetails($validatedData)
    {
        // dd($validatedData);
        return self::create($validatedData);
    }

    public static function getproductDetails()
    {
        return self::orderBy('id', 'DESC')->get();
    }

    public static function getCategoryById($id)
    {
        return self::where('id', $id)->first();
    }

    public static function updateProductData($validatedData, $id)
    {
        self::where('id', $id)->update($validatedData);
    }

    public static function getproductdata($id)
    {
        return self::where('id', $id)->first();
    }

    public function reviews()
    {
        return $this->hasMany(ReviewRating::class);
    }

    public static function deleteProductData($id)
    {
        return self::where('id', $id)->delete();
    }
}
