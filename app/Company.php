<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class Company extends Model
{
	protected $table = 'companies';
	use SoftDeletes;
	
	 public function settings()
    {
        return $this->hasMany('App\CompanySetting', 'company_id');
    }
    public function assignedUser(){
    	return $this->belongsTo('App\AdminView','id','company_id');
    }
}
