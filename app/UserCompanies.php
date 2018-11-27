<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserCompanies extends Model
{
    use SoftDeletes;
    protected $table = 'user_companies';
}
