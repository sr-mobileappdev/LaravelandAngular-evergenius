<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Auth;

class Appointment extends Model
{
	use SoftDeletes;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];

    public function contacts(){
        return $this->belongsTo('App\Contact','contact_id','id');
    }
    public function doctor(){
    	return $this->belongsTo('App\User','provider_user_id','id')->select('id','name','phone');
    }

    public function appointment_reason(){
    	return $this->belongsTo('App\AppointmentService','appointment_service_id');
    }

    public function appointment_provider(){
    	return $this->belongsTo('App\User','provider_user_id');
    }
     public function appointment_status(){
    	return $this->belongsTo('App\AppointmentStatus','appointment_status_id');
    }

     public function company(){
        return $this->belongsTo('App\Company','company_id');
    }
    public function week_scheduler(){
        return $this->belongsTo('App\UserSetting','provider_user_id'); 
    }
    
    public function getBookDatetimeAttribute($value)
    {
        $tz='';
        if (Auth::check()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $tz=CompanySettingsHelper::getSetting($company_id, 'timezone');
        }

        if( $tz!='' && $tz!=false ) {
            return Carbon::createFromTimestamp(strtotime($value))
            ->timezone($tz)
            ->toDateTimeString();
        } 
        else {
            return $value;
        } 
         
    }
    public function getAppointmentStatusIdAttribute($value)
    {
        return (int) $value;
    }

}
