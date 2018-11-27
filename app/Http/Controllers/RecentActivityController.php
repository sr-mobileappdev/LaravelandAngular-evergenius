<?php

namespace App\Http\Controllers;

use App\Classes\ActivityHelper;
use Auth;
use Carbon\Carbon;
use Input;

class RecentActivityController extends Controller
{
    public function getIndex()
    {
        $d_e = '';
        $d_s = '';
        $last_id = '';
        $con_id = '';
        $user = Auth::user();
        $user_id = $user->id;

        $tz = 'UCT';
        if (Input::get('last_id')) {
            $last_id = Input::get('last_id');
        }

        if (Input::get('contact_id')) {
            $con_id = Input::get('contact_id');
        }

        if (Input::get('start_date') && Input::get('end_date')) {
            /* Get TimeZone Time */
            if ($tz != '' && $tz != false) {
                $d_s = Carbon::createFromTimestamp(strtotime(Input::get('start_date')))
                    ->timezone($tz)
                    ->toDateTimeString();
                $d_e = Carbon::createFromTimestamp(strtotime(Input::get('end_date')))
                    ->timezone($tz)
                    ->toDateTimeString();
            }
        }
        $user_role = $user
            ->roles()
            ->select(['slug'])
            ->first()->toArray();

        //Fetch data for doctor
        if ($user_role['slug'] == 'doctor') {
            $activities = $activites = ActivityHelper::getRecentActivity($d_s, $d_e, $con_id, $last_id, $user_id);
        } elseif ($user_role['slug'] == 'admin.user') {
            $activities = $activites = ActivityHelper::getRecentActivity($d_s, $d_e, $con_id, $last_id);
        }

        return response()->success(compact('activities'));
    }
}
