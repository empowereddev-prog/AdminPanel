<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class PermissionUser extends Model implements AuditableContract
{
    use Auditable;
    protected $table = 'permission_users';
    protected $fillable = [
        'user_id',
        'menu_id',
        'is_view',
        'is_modify'
    ];
    public function user()
    {
        return $this->belongsTo('App\Model\User');
    }
    public function adminMenu()
    {
        return $this->belongsTo('App\Model\AdminMenu');
    }
    public static function insertpermission($data)
    {
        // dd($data);
        return DB::table('permission_users')->insert([
            'user_id' => $data['user_id'],
            'menu_id' => $data['menu_id'],
            'is_view' => $data['is_view'],
            'is_modify' => $data['is_modify'],
        ]);
        // dd('hhhhhh');
    }
    public static function deletepermission($user_id)
    {
        // dd(DB::table('permission_users')->where('user_id',$user_id)->dd());
        return DB::table('permission_users')->where('user_id', $user_id)->delete();
    }
    public static function getpermission($user_id)
    {
        return DB::table('permission_users')->where('user_id', $user_id)->get();
    }
    public static function checkpermission($user_id, $menu_id)
    {
        return DB::table('permission_users')->where('user_id', $user_id)->where('menu_id', $menu_id)->first();
    }
}
