<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class User extends Authenticatable implements AuditableContract
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
        'mobile_otp',
        'password_reset_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // decimal(10,2) otherwise serialises as the string "25.50" while the
        // sibling battery_points emits a number.
        'loyalty_points' => 'float',
        'popup_1_updated_at' => 'datetime',
        'popup_2_updated_at' => 'datetime',
    ];

    public function children()
    {
        return $this->hasMany(Child::class, 'parent_id', 'id');
    }

    public function child()
    {
        return $this->hasMany(User::class, 'parent_id', 'id')
            ->where('user_type', 'child');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    public function userAvatar()
    {
        return $this->hasOne(UserAvtarImage::class, 'child_id', 'id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class, 'user_id');
    }

    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class, 'user_id', 'id');
    }
}
