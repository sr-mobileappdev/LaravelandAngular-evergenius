<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
class CompanyTemplate extends Model
{
	//use BelongsToTenants;
	use SoftDeletes;
	//public $tenantColumns = ['company_id'];
	public function getPreviewImageAttribute($value)
    {
    	$company_id = $this->company_id;
    	if(empty($company_id)){
    		$company_id = 0;
    	}
        return getenv('API_URL')."/template-preview/".$company_id."/".$value;
    }
}
