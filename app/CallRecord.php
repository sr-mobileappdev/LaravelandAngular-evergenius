<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
class CallRecord extends Model
{
	use SoftDeletes;
    public function getCallStartAtAttribute($value)
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

	public function notes()
    {
        return $this->hasOne('App\CallRecordNote','call_id');
    }
}