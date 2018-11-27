<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmCampaignLog extends Model
{
	 protected $table = 'em_campaign_log';

	 public function campaign(){
	 	 return $this->hasMany('App\EmCampaign','id','campaign_id');
	 }

}
