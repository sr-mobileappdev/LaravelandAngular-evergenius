<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactComment extends Model
{
	//use BelongsToTenants;
	//use SoftDeletes;
	//public $tenantColumns = ['company_id'];
   public function user(){
    	return $this->belongsTo('App\User','created_by');
   }


}
