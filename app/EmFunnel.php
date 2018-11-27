<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmFunnel extends Model
{
    use SoftDeletes;

    protected $table = 'em_funnels';

    public function f_list()
    {
        return $this->hasMany('App\EmFunnelList', 'funnel_id');
    }
    public function stepcount()
    {
        return $this->hasMany('App\EmFunnelAction', 'funnel_id');
    }
    public function action()
    {
        return $this->hasMany('App\EmFunnelAction', 'funnel_id')->where('status', '1');
    }
    public function apikeyexists()
    {
        return $this->belongsTo('App\CompanySetting', 'company_id', 'company_id')->where('name', 'sendgrid_api_key')->whereNotNull('value')->where('value', '<>', '');
    }
    public function company()
    {
        return $this->belongsTo('App\Company');
    }
}
