<?php

// use Illuminate\Foundation\Auth\User as Authenticatable;

namespace App;

use Bican\Roles\Contracts\HasRoleAndPermission as HasRoleAndPermissionContract;
use Bican\Roles\Traits\HasRoleAndPermission;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use HipsterJazzbo\Landlord\BelongsToTenants;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract, HasRoleAndPermissionContract
{

    use SoftDeletes;
    use Authenticatable, CanResetPassword, HasRoleAndPermission;
    use BelongsToTenants;
    public $tenantColumns = ['company_id'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'oauth_provider', 'oauth_provider_id','notification_last_seen','status'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'oauth_provider_id', 'oauth_provider',
    ];

    public function rolesUser(){
        return $this->hasMany('App\RoleUser','user_id');
    }

    public function userrole(){
        return $this->belongsTo('App\RoleUser','user_id');
    }

    public function company(){
        return $this->belongsTo('App\Company','company_id','id')->select('id','name','address','country','state','zip_code');
    }
 

  //  function role


}
