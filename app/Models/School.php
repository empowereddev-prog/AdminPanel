<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class School extends Model implements AuditableContract
{
    use HasFactory, Auditable;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(User::class, 'school_id');
    }

    public function parentInvites()
    {
        return $this->hasMany(SchoolParentInvite::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(SchoolSubscription::class);
    }

    /**
     * Parents only. students() is deliberately left alone: SchoolController::show
     * eager-loads it and view.blade.php iterates it expecting parents *and*
     * teachers, so narrowing it there would empty the staff rows out of the
     * school user list.
     */
    public function parents()
    {
        return $this->hasMany(User::class, 'school_id')->where('user_role_id', 3);
    }
}
