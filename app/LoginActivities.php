<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LoginActivities extends Model
{
    use SoftDeletes;
    protected $table = 'login_activities';
}
