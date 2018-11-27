<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactsDt extends Model
{
    protected $table = 'contacts_dt';

    public function contact_info(){
    	return $this->belongsTo('App\Contact','id','id');
    }
}
