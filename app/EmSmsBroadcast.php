<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmSmsBroadcast extends Model
{
	use SoftDeletes;
    protected $table = 'em_sms_broadcasts';
    public function getScheduleDatetimeAttribute($value)
    {
    	$user = Auth::user();
    	if($user){
			if($value!=null){
				$timezone = \App\Classes\CompanyHelper::getCompanyTimezone();
				$valuea = \App\Classes\CompanyHelper::AnyUtcToTimeZone($value,$timezone);
				return $valuea;
			}
    	}
    	return $value;
	}
	public function getSentAtAttribute($value)
    {
    	$user = Auth::user();
    	if($user){
			$company_id = $user->company_id;
			if($value!=null){
				$timezone = \App\Classes\CompanyHelper::getCompanyTimezone();
				$valuea = \App\Classes\CompanyHelper::AnyUtcToTimeZone($value,$timezone);
				return $valuea;
			}
    	}

    	return $value;
    }

}

