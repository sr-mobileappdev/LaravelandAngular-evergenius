<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class  EmSmsBroadcastNewsletterList extends Model
{
    public $table = 'em_sms_broadcast_newsletter_lists';
    public function newsletter()
    {
        return $this->belongsTo('App\EmNewsletterList','list_id','id');
    }
}
