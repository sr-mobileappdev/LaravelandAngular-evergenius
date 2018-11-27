<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HipsterJazzbo\Landlord\BelongsToTenants;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Auth;

class ReviewSetting extends Model
{
  use SoftDeletes;
  protected $table = 'review_settings';
}
