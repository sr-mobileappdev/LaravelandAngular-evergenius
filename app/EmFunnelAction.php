<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmFunnelAction extends Model
{
   protected $table = 'em_funnel_actions';

   public function funnel(){
   	return $this->belongsTo('App\EmFunnel','funnel_id','id');
   }

   public function f_list(){
   		return $this->hasMany('App\EmFunnelList','funnel_id','funnel_id');
   }
   
   public function rules(){
         return $this->hasMany('App\EmFunnelActionRule','action_id');
   }
    public function appointment(){
         return $this->hasMany('App\EmFunnelActionRule','action_id')->where('rule_type','appointment');;
   }
    public function opportunity(){
         return $this->hasMany('App\EmFunnelActionRule','action_id')->where('rule_type','opportunity');
   }

   public function sent(){
         return $this->hasMany('App\EmActionFunnelQueue','action_id')->where('status','2');
   }

   public function opened(){
   		return $this->hasMany('App\EmActionFunnelQueue','action_id')->where('open_status','1');
   }
   public function clicked(){
         return $this->hasMany('App\EmActionFunnelQueue','action_id')->where('click_status','1');
   }

   public function tag(){
      return $this->belongsTo('App\EgTerm','tag_id','id')->where('term_type','tag');
   }
   public function emlist(){
      return $this->belongsTo('App\EmNewsletterList','list_id','id');
   }
}