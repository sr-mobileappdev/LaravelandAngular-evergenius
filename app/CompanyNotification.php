<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CompanyNotification extends Model
{

    public function users(){
    	return $this->belongsTo('App\User','user_id');
    }
}