<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmCampaignNewsletterList extends Model
{

    use SoftDeletes;

	/**
	* The attributes that should be mutated to dates.
	*
	* @var array
	*/
	protected $dates = ['deleted_at'];
    
        public function contactlist(){
        return $this->hasMany('App\EmNewsletterContacts','list_id','list_id');
    }
    public function detail(){
        return $this->belongsTo('App\EmNewsletterList','list_id');
    }

    
}
