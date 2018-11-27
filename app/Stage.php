<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Stage extends Model
{
    use SoftDeletes;

    public function leads(){
        return $this->hasMany('App\Lead','user_id');
    }
}
