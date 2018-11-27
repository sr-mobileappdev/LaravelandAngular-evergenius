<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmNewsletterContacts extends Model
{
    
   public function contact(){
    	return $this->belongsTo('App\Contact','contact_id','id')->where(function ($query) {
                $query
                ->whereNotNull('mobile_number')
                ->where('mobile_number','!=','')
                ->whereNull('dnd')
                      ->orWhere('dnd','0');
            });
    }

    public function sent(){
    	return $this->belongsTo('App\EmCampaignLog','contact_id','contact_id');
    }

    public function stat(){
    	return $this->belongsTo('App\EmCampaignLog','contact_id','contact_id')->where('company_id',$this->company_id);
    }
    public function contacts(){
    	return $this->belongsTo('App\Contact','contact_id','id')->where(function ($query) {
                $query->whereNull('dnd')
                      ->orWhere('dnd','0');
            });
    }
	

}
