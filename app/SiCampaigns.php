<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class SiCampaigns extends Model
{
    use BelongsToTenants;
	use SoftDeletes;
	public $tenantColumns = ['company_id'];
	protected $table = 'si_campaigns';

	public function posts(){
    	return $this->hasMany('App\SiPosts','campaign_id');
    }
}
