<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class EmFunnelList extends Model
{
	use SoftDeletes;

	/**
	* The attributes that should be mutated to dates.
	*
	* @var array
	*/
	protected $dates = ['deleted_at'];
   	protected $table = 'em_funnel_lists';

   public function listdetail(){
    	return $this->belongsTo('App\EmNewsletterList','list_id','id');
    }
    public function listd(){
    	return $this->hasMany('App\EmNewsletterContacts','list_id','list_id');
    }
}