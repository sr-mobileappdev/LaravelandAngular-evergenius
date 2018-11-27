<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
class SmsRecord extends Model
{
    use SoftDeletes;
   /* public function contact(){
    	return $this->belongsTo('App\Contact','contact_id');
    }*/

    public function conversationContact(){
        return $this->belongsTo('App\Contact','contact_id')->select(array('id', 'first_name','last_name','email','mobile_number'));
    }
    public function lastMessage(){
        return $this->belongsTo('App\SmsRecord','contact_id','contact_id');
    }
   /* public function NotSeen(){
        return $this->hasMany('App\SmsRecord','contact_id','contact_id')->whereNotNull('not_seen');
    }*/

     public function getSentTimeAttribute($value)
	{
		$user = Auth::user();
		$company_id = $user->company_id;
		$tz=CompanySettingsHelper::getSetting($company_id, 'timezone');
		/* If Timezone Set */
		if( $tz!='' && $tz!=false ) {
			return Carbon::createFromTimestamp(strtotime($value))
            ->timezone($tz)
            ->toDateTimeString();
        } 

        else {
        	return $value;
        } 
	     
	}
}
