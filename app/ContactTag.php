<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactTag extends Model
{

    public function tag(){
       return $this->belongsTo('App\EgTerm','tag_id','id');
    }
}
