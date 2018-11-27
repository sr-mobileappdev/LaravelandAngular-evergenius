<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactTblView extends Model
{
   public function contact_info(){
    	return $this->belongsTo('App\Contact','id','id');
    }
    public function lead(){
    	return $this->hasOne('App\Lead','contact_id','id');
    }
}
