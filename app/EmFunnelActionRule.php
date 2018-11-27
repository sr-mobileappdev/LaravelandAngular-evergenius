<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmFunnelActionRule extends Model
{
   protected $table = 'em_funnel_action_rules';

   public function company(){
   		return $this->belongsTo('App\EmFunnel','funnel_id');
   }
   public function aptstatus(){
   	return $this->belongsTo('App\AppointmentStatus','appointment_status','id');
   }
   public function sourcename(){
   		if($this->opportunity_source_id!=null){
   			return $this->belongsTo('App\EgTerm','opportunity_source_id','id')->select(array('id','term_value'));
   		}
   		return $this->belongsTo('App\EgTerm','opportunity_source_id','id');
   }

}