<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Task extends Model
{
    use SoftDeletes;
    public function type(){
        return $this->belongsTo('App\TaskType','type_id')->select(array('id','name'));
    }
    public function contact(){
    	return $this->belongsTo('App\Contact','contact_id','id')->select('id','first_name','last_name');
    }
}
