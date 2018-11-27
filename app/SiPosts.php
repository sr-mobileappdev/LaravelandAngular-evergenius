<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class SiPosts extends Model
{
    use BelongsToTenants;
	use SoftDeletes;
	public $tenantColumns = ['company_id'];
	protected $table = 'si_posts';

	 public function meta(){
    	return $this->hasMany('App\SiPostsMeta','post_id');
    }
}
