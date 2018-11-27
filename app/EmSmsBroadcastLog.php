<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;

/**
 * model to save log of sms broadcast
 */

class EmSmsBroadcastLog extends Model
{
    protected $table = 'em_sms_broadcast_logs';
    public function contact(){
    	return $this->belongsTo('App\Contact','contact_id','id')->where(function ($query) {
                $query
                ->whereNotNull('mobile_number')
                ->where('mobile_number','!=','')
                ->whereNull('dnd')
                      ->orWhere('dnd','0');
            });
    }
    public function campign(){
        return $this->belongsTo('App\EmSmsBroadcast','sms_broadcast_id','id');
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
