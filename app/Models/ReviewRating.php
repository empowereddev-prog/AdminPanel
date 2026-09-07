<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class ReviewRating extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    public $table = "client_review";
    protected $guarded = [];

    public static function addClientReview($validator)
    {
        return self::create($validator);
    }

    public static function clientReviewDetails()
    {
        return self::with('product')->orderBy('id', 'DESC')->get();
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public static function reviewRating($id)
    {
        return self::with('product')->where('id',  $id)->first();
    }

    public static function updateRating($id, $data)
    {
        return self::where('id', $id)->update($data);
    }
}
