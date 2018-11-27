<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class SiNetwork extends Model
{
    use BelongsToTenants;
	use SoftDeletes;
	public $tenantColumns = ['company_id'];
}
