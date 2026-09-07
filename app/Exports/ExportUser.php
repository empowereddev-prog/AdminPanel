<?php

namespace App\Exports;

#use App\AppExports;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Role;
use Session;
// use App\Models\Tier;

class ExportUser implements FromCollection, WithHeadings {
	/**
	* @return \Illuminate\Support\Collection
	*/
	public function collection() {

		$startdate = Session::get('startdate');

		$enddate = Session::get('enddate');

		$query = User::query();

		$query->select('*', \DB::raw("date_format(created_at,'%d/%m/%Y') as created"))->whereIn('user_role_id', [2,3]);
		Session::forget('startdate');
		Session::forget('enddate');

		$rmList = $query->orderBy('id', 'DESC')->get();
		$i = 1;
		$ArrUser = array();
		foreach ($rmList as $key => $value) {
			$tier_id = Role::select('name')->where('id', $value['user_role_id'])->first();
			/*if($value['tier'] == 1){
                $tier =  'PB';
            }
            elseif($value['tier'] == 2){
                $tier = 'TPC';
            }
          	else{
            	$tier = '';
          	}*/
          	$tier = $tier_id->name??'';

          	if($value['status'] == 1){
                $status =  'Active';
            }
            elseif($value['status'] == 0){
                $status = 'Inactive';
            }
           
			$Arr = [
			'name' 		=> $value['name'],
			'email' 	=> $value['email'],
			'user_type' => $tier,
			'phone_no' => $value['phone_no'],
			'created'	=> $value['created'],
			'status'	=> $status,
			];
			$ArrUser[] = $Arr;
			$i++;
		}
		$norecord = [
			'name' 		=> '',
			'email' 	=> '',
			'user_type' => '',
			'phone_no' => '',
			'created'	=> '',
			'status'	=> '',
			];
		$record[] = $norecord;

		if($ArrUser){
			return collect($ArrUser);
		}
		else{
			return collect($record);
		}
	}
	public function headings(): array
	{
		return [
			'Name',
			'Email Address',
			'Segment',
			'Mobile Number',
			'Added On',
			'Status',
		];
	}
}