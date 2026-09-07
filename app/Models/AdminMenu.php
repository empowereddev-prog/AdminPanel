<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
class AdminMenu extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $table = 'admin_menu';
    protected $fillable = [
        'menu_name', 'id_general_status','pid'
    ];
    public function Permission_menu(){
        return $this->hasMany('App\Model\PermissionUsers');
    } 

    public function PermissionUser(){
        return $this->hasMany(PermissionUsers::class,'menu_id','id');
    }

    public static function getMenuData()
    {
        $res = DB::table('admin_menu')->where('pid',0)->get();
        return json_decode(json_encode($res),true);
        
    }
    public static function getSubMenuData($pid)
    {
        $res = DB::table('admin_menu')->where('pid',$pid)->get();
        return json_decode(json_encode($res),true);
    }
}
