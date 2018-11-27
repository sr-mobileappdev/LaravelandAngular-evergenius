<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class PerfectAudienceData extends Model
{
	use BelongsToTenants;
	use SoftDeletes;
	public $tenantColumns = ['company_id'];
	protected $table = 'perfect_audience_data';
}
