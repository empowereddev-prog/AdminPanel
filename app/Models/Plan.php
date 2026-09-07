<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Plan extends Model implements AuditableContract
{
    use Auditable;
    use HasFactory;
    protected $fillable = [
        'plan',
        'plan_id',
        'plan_type',
        'monthly_price_id',
        'monthly_amount',
        'annual_price_id',
        'annually_amount',
        'description',
        'zh_CN_description',
        'zh_TW_description',
        'tax_rate_id',
        'discount',
        'status',
        'language'
    ];

    //  /**
    //  * Get the user that should be logged as the author of the audit.
    //  *
    //  * @return \Illuminate\Contracts\Auth\Authenticatable|null
    //  */
    // public function getUserToAudit()
    // {
    //     // Assuming you use the default auth guard
    //     dd(auth()->user());
    //     return auth()->user();
    // }
}
