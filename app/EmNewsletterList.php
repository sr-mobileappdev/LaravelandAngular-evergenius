<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmNewsletterList extends Model
{
    use SoftDeletes;

    /**
    * The attributes that should be mutated to dates.
    *
    * @var array
    */
    protected $dates = ['deleted_at'];
    protected $fillable = array('company_id', 'name','description');
    public $table = 'em_newsletter_lists';

    public function detials()
    {
        return $this->belongsTo('App\EmNewsletterList', 'contact_id', 'id');
    }

    //'today','yesterday','sub','unsub','sent'

    public function today()
    {
        return $this->hasMany('App\EmNewsletterContacts', 'list_id', 'id')->whereDate('created_at', '=', date('Y-m-d'));
    }

    public function yesterday()
    {
        $yesterday = date("Y-m-d", strtotime('-1 days'));
        return $this->hasMany('App\EmNewsletterContacts', 'list_id', 'id')->whereDate('created_at', '=', $yesterday);
    }

    public function sub()
    {
        $status = \App\EmListStatus::where('name', 'Subscribe')->first()->toArray();
        return $this->hasMany('App\EmNewsletterContacts', 'list_id', 'id')->where('status_id', $status['id']);
    }

    public function unsub()
    {
        $status = \App\EmListStatus::where('name', 'Unsubscribe')->first()->toArray();
        return $this->hasMany('App\EmNewsletterContacts', 'list_id', 'id')->where('status_id', $status['id']);
    }
    public function sent()
    {
        return $this->hasMany('App\EmCampaignLog', 'list_id', 'id')->where('status', '2');
    }
    public function funnel()
    {
        return $this->hasMany('App\EmActionFunnelQueue', 'list_id', 'id')->where('status', '2')->where('action_type','=','1');
    }
}
