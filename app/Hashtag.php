<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;

class Hashtag extends Model
{
	use BelongsToTenants;
	use SoftDeletes;
    //protected $table = 'hashtags';
}
