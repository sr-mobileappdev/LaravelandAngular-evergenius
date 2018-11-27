<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Classes\CompanySettingsHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
class Lead extends Model
{
    use SoftDeletes;
    public function assignee(){
        return $this->belongsTo('App\User','user_id')->select(array('id','name','avatar'));
    }
    public function contact(){
        return $this->belongsTo('App\Contact','contact_id')->select(array('id','first_name','last_name','email','mobile_number as phone'));
    }
    public function source(){
        return $this->belongsTo('App\LeadSource','source_id')->select(array('id','name'));
    }
    public function stage(){
        return $this->belongsTo('App\Stage','stage_id')->select(array('id','title','slug'));
    }
    public function service(){
        return $this->belongsTo('App\Service','service_id')->select(array('id','name'));
    }
    public function tasks(){
        return $this->hasMany('App\Task','lead_id');
    }
    public function appointment(){
        return $this->hasOne('App\Appointment','contact_id','contact_id');
    }

    public function getActionClassAttribute(){
        if(Auth::user()){
            $user = Auth::user();
            $company_id = $user->company_id;
            $medium_time = CompanySettingsHelper::getSetting($company_id, 'lead_medium_time');
            $high_time = CompanySettingsHelper::getSetting($company_id, 'lead_high_time');
            $created_time = $this->created_at;
            $lead_action_time = $this->lead_action_time;
            $action_taken = $this->action_taken;
            if((int) $action_taken==0 && $medium_time!==false && $high_time!==false ){
                $medium_time = (int) $medium_time;
                $high_time = (int) $high_time;
                $date1 = strtotime($created_time);
                $date2 = time();
                
                $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
                /* If Timezone Set */
                if( $tz!='' && $tz!=false ) {
                    $c_time = Carbon::createFromTimestamp($date2)
                    ->timezone($tz)
                    ->toDateTimeString();
                    $date2 = strtotime($c_time); 
                }


                $mins = ($date2 - $date1) / 60;
                if($mins<= $medium_time){
                    return 'normal';
                }
                else if($mins>$medium_time && $mins<=$high_time){
                    return 'medium';
                }
                else{
                    return 'critical';
                }
            }
        }
        return null;
    }

    public function getCreatedAtAttribute($value)
    {   
        if(Auth::user()){
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
        return $value;
    }
    public function getUpdatedAtAttribute($value)
    {   
        if(Auth::user()){
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
        return $value;
    }
    
    public function contacts(){
        return $this->belongsTo('App\Contact','contact_id','id');
    }
    public function company(){
        return $this->belongsTo('App\Company','company_id');
    }
}
