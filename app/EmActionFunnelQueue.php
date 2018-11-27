<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmActionFunnelQueue extends Model
{
    protected $table = 'em_action_funnel_queue';

    public function contact()
    {
        return $this->belongsTo('App\Contact', 'contact_id', 'id')->select('id', 'email', 'first_name', 'last_name', 'mobile_number')->where(function ($query) {
            $query->whereNull('dnd')
                      ->orWhere('dnd', '0');
        });
    }
    public function action()
    {
        return $this->belongsTo('App\EmFunnelAction', 'action_id', 'id')->select('id', 'name');
    }

    public function funnel()
    {
        return $this->belongsTo('App\EmFunnel', 'funnel_id');
    }
    
    public function apikeyexists()
    {
        return $this->belongsTo('App\CompanySetting', 'company_id', 'company_id')->where('name', 'sendgrid_api_key')->whereNotNull('value')->where('value', '<>', '');
    }
    
	public function contactList()
    {
        return $this->belongsTo('App\Contact', 'contact_id', 'id')->select('id', 'email', 'first_name', 'last_name', 'mobile_number','deleted_at')->withTrashed();
    }
}
