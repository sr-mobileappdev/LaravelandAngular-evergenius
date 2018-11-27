<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Classes\CompanySettingsHelper;
use Auth;
class AppointmentsDt extends Model
{
    protected $table = 'appointments_dt';
    public function getBookDatetimeAttribute($value)
    {
        $tz='';
        if (Auth::check()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $tz=CompanySettingsHelper::getSetting($company_id, 'timezone');
        }

        if( $tz!='' && $tz!=false ) {
            return Carbon::createFromTimestamp(strtotime($value))
            ->timezone($tz)
            ->toDateTimeString();
        } 
        else {
            return $value;
        } 
         
    }
}
