<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class EgTerm extends Model
{
    use BelongsToTenants;
	use SoftDeletes;
	protected $table = 'eg_terms';
}