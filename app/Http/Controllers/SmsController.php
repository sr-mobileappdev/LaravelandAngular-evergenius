<?php

namespace App\Http\Controllers;

use App\Classes\CompanyHelper;
use App\Classes\CronHelper;
use App\Classes\SmsHelper;
use App\SmsRecord;
use Auth;
use Carbon\Carbon;
use Datatables;
use Input;

class SmsController extends Controller
{
    public function postIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $where = array();
        $input_data = Input::get();
        if (isset($input_data['customFilter']['start_time']) && isset($input_data['customFilter']['end_time'])) {
            $date_s = date('Y-m-d 00:00:00', strtotime($input_data['customFilter']['start_time']));
            $date_e = date('Y-m-d 23:59:59', strtotime($input_data['customFilter']['end_time']));
            $tz = 'UTC';
            $date_s = Carbon::createFromTimestamp(strtotime($date_s))
                ->timezone($tz)
                ->toDateTimeString();
            $date_e = Carbon::createFromTimestamp(strtotime($date_e))
                ->timezone($tz)
                ->toDateTimeString();
            $d_s = array('sent_time', '>=', $date_s);
            $d_e = array('sent_time', '<=', $date_e);
            array_push($where, $d_s, $d_e);
        }

        $sms = SmsRecord::select('receiver_name', 'sms_to', 'sent_time', 'sms_body', 'direction', 'contact_id')->where('company_id', $company_id)->where($where)->get();

        return Datatables::of($sms)->make(true);
    }

    public static function getStoreScheduledSms()
    {
        // Create Cron Record
        $last_fetch_time = CronHelper::getRecentExecutedTime('sms_fetch');
        $cron_id = CronHelper::createCronRecord('calls_fetch');
        $compnies = CompanyHelper::getAllCompanies();
        SmsHelper::SaveIncomingSmsRecords($compnies, $last_fetch_time);
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('success');
    }

    public function getSmsWidgets()
    {
        $user = Auth::user();
        $company_id = $user->company_id;

        $input_data = input::all();
        if (isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];

            $days = getCountDaysBeetweenDates($start_date, $end_date);

            if ($days <= 15) {
                $sms_statics = SmsHelper::getSmsStaticByDate($company_id, $start_date, $end_date);
            } elseif ($days > 15 && $days <= 120) {
                $sms_statics = SmsHelper::getSmsStaticByWeek($company_id, $start_date, $end_date);
            } else {
                $sms_statics = SmsHelper::getSmsStaticByMonth($company_id, $start_date, $end_date);
            }

            $sms_summary = SmsHelper::getSmsSummaryByDate($company_id, $start_date, $end_date);
            return response()->success(compact('sms_statics', 'sms_summary'));
        }
        return response()->error('Somthing Went Wrong');
    }

    public function callBackSmsStore()
    {
        $input_data = Input::get();
        SmsHelper::storeIncmoingSms($input_data);
    }
}
