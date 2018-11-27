<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Auth;

class Review extends Model
{
  use SoftDeletes;
  protected $table = 'reviews';
}
