<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Auth;
class EmCampaign extends Model
{
   use SoftDeletes;
   public function status(){
    	return $this->hasMany('App\EmCampaignStatus','id','status');
    }

   public function elist(){
    	return $this->hasMany('App\EmCampaignNewsletterList','campaign_id')->whereNull('deleted_at');
    }

    public function logs(){
        return  $this->hasMany('App\EmCampaignLog','campaign_id')->where('status', 1)->take(getenv('EMAILMARKETING_JOB_BATCH_SIZE'));
    }

    public function apikeyexists(){
        return $this->belongsTo('App\CompanySetting','company_id','company_id')->where('name','sendgrid_api_key')->whereNotNull('value')->where('value','<>','');
    }
     public function getScheduleDatetimeAttribute($value)
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

    public function clicks(){
        return $this->hasMany('App\EmCampaignLog','campaign_id','id')->where('click_status','1');
    }
    public function opened(){
        return $this->hasMany('App\EmCampaignLog','campaign_id','id')->where('open_status','1');
    }
    public function bounced(){
        return $this->hasMany('App\EmCampaignLog','campaign_id','id')->where('bounce_status','1');
    }
    public function spammed(){
        return $this->hasMany('App\EmCampaignLog','campaign_id','id')->where('spam_status','1');
    }
    public function sent(){
        return $this->hasMany('App\EmCampaignLog','campaign_id','id')->where('status','2');
    }
    public function subscribed(){
        return $this->hasMany('App\EmCampaignNewsletterList','campaign_id','id')->whereNull('deleted_at');
    }

}
