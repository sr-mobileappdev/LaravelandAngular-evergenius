<?php

namespace App\Http\Controllers;

use App\Classes\AppointmentsHelper;
use App\Classes\CallsHelper;
use App\Classes\CompanyHelper;
use App\User;
use Auth;
use Input;

class DashboardController extends Controller
{

    /* ********* Function for Dashboard calls Widget ********* */

    public function getCallWidgets()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        if (isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];
            $days = getCountDaysBeetweenDates($start_date, $end_date);
            if ($days <= 15) {
                $calls_statics = CallsHelper::getCallsStaticByDate($company_id, $start_date, $end_date);
            } elseif ($days > 15 && $days <= 120) {
                $calls_statics = CallsHelper::getCallsStaticByWeek($company_id, $start_date, $end_date);
            } else {
                $calls_statics = CallsHelper::getCallsStaticByMonth($company_id, $start_date, $end_date);
            }

            $calls_summary = CallsHelper::getCallsSummaryByDate($company_id, $start_date, $end_date);
            return response()->success(compact('calls_statics', 'calls_summary'));
        }
        return response()->error('Somthing Went Wrong');
    }

    public function getAppointmentsWidgets()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        if (isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];

            /*$calls_statics = CallsHelper::getCallsStaticByDate($company_id,$start_date,$end_date);
            $calls_summary = CallsHelper::getCallsSummaryByDate($company_id,$start_date,$end_date);*/
            $appointments_count = AppointmentsHelper::getCountAppointmentBytime($start_date, $end_date, $company_id);
            $appointments_sources = AppointmentsHelper::getCountAptmntBySoucetime($start_date, $end_date, $company_id);
            return response()->success(compact('appointments_count', 'appointments_sources'));
        }

        return response()->error('Somthing Went Wrong');
    }

    public function getFacebookPageInsight()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        $start_date = strtotime('-1 day', strtotime($input_data['start_date']));
        $end_date = strtotime('+1 day', strtotime($input_data['end_date']));
        $date1 = date_create($input_data['start_date']);
        $date2 = date_create($input_data['end_date']);
        $diff = date_diff($date1, $date2);
        $days_left = $diff->d;
        $start_date_old = strtotime('-' . $days_left . ' day', strtotime($input_data['start_date']));
        $end_date_old = strtotime('-1 day', strtotime($input_data['start_date']));
        $facebookPageInsightdata = CompanyHelper::getFacebookPageInsightData($start_date, $end_date, $company_id);
        $facebookPageInsightdata_old = CompanyHelper::getFacebookPageInsightData($start_date_old, $end_date_old, $company_id);

        $fan_percent = 0;
        $impression_percent = 0;
        $engagement_percent = 0;
        $view_percent = 0;
        $new_fans = $facebookPageInsightdata['page_fans'];
        $new_impressions = $facebookPageInsightdata['page_impressions'];
        $new_engagements = $facebookPageInsightdata['page_post_engagements'];
        $new_views = $facebookPageInsightdata['page_views_total'];
        $old_fans = $facebookPageInsightdata_old['page_fans'];
        $old_impressions = $facebookPageInsightdata_old['page_impressions'];
        $old_engagements = $facebookPageInsightdata_old['page_post_engagements'];
        $old_views = $facebookPageInsightdata_old['page_views_total'];

        if ($new_fans != 0 && $old_fans != 0) {
            $fan_percent = (($new_fans - $old_fans) / $old_fans) * 100;
            $fan_percent = number_format($fan_percent, 2);
        } else {
            $fan_percent = $new_fans;
        }
        if ($new_impressions != 0 && $old_impressions != 0) {
            $impression_percent = (($new_impressions - $old_impressions) / $old_impressions) * 100;
            //$impression_percent = number_format($impression_percent,2);
        } else {
            $impression_percent = $new_impressions;
        }
        if ($new_engagements != 0 && $old_engagements != 0) {
            $engagement_percent = (($new_engagements - $old_engagements) / $old_engagements) * 100;
            $engagement_percent = number_format($engagement_percent, 2);
        } else {
            $engagement_percent = $new_engagements;
        }
        if ($new_views != 0 && $old_views != 0) {
            $view_percent = (($new_views - $old_views) / $old_views) * 100;
            $view_percent = number_format($view_percent, 2);
        } else {
            $view_percent = $new_views;
        }
        //echo $fan_percent."---".$impression_percent."---".$engagement_percent."----".$view_percent;

        $percentageData = array('fan_percentage' => $fan_percent, 'impression_percent' => $impression_percent, 'engagement_percent' => $engagement_percent, 'view_percent' => $view_percent);
        return response()->success(compact('facebookPageInsightdata', 'percentageData'));
        //dd($facebookPageInsightdata);
    }
    public function getTwitterTimeline()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();

        $start_date = strtotime('-1 day', strtotime($input_data['start_date']));
        $end_date = strtotime('+1 day', strtotime($input_data['end_date']));
        $TwitterData = CompanyHelper::getTwitterTimelineData($start_date, $end_date, $company_id);
        return response()->success(compact('TwitterData'));
    }
    public function getInstagramViews()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        $InstaData = CompanyHelper::getInstaData($company_id);
        return response()->success(compact('InstaData'));
    }
    public function getLinkedInViews()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        $start_date = strtotime('-1 day', strtotime($input_data['start_date']));
        $end_date = strtotime('+1 day', strtotime($input_data['end_date']));
        $linkedin = CompanyHelper::getlinkedInData($start_date, $end_date, $company_id);
        return response()->success(compact('linkedin'));
    }

    public function getNewVsReturningWidgets()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        if (isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];
            $new_user = AppointmentsHelper::getNewAndReturningUsers($start_date, $end_date, $company_id, 'new');
            $returning_user = AppointmentsHelper::getNewAndReturningUsers($start_date, $end_date, $company_id, 'returning');
            return response()->success(compact('new_user', 'returning_user'));
        }
        return response()->error('Somthing Went Wrong');
    }
}
