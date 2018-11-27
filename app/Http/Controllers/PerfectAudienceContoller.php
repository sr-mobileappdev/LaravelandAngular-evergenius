<?php

namespace App\Http\Controllers;

use App\Classes\AppOptionsHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\CronHelper;
use App\CompanySetting;
use App\PerfectAudienceData;
use App\User;
use Auth;
use Curl;
use DateTime;
use DB;
use Input;

class PerfectAudienceContoller extends Controller
{
    public function accessToken()
    {
        $email = AppOptionsHelper::getOptionValue('pa_email');
        $password = AppOptionsHelper::getOptionValue('pa_password');
        $api_url = "https://api.perfectaudience.com/auth?email=" . $email . "&password=" . $password . "";
        $response = Curl::to($api_url)->asJson()->post();
        if ($response && $response->status == '200') {
            return $response->token;
        } else {
            return false;
        }
    }

    public function getCampaignsBySite()
    {
        $accessToken = self::accessToken();
        if ($accessToken) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $pa_site_data = CompanySetting::where('company_id', $company_id)->where('name', 'pa_site_id')->first();
            if ($pa_site_data) {
                $pa_site_id = $pa_site_data->value;
                $api_url = "https://api.perfectaudience.com/campaigns?site_id=" . $pa_site_id . "";
                //$api_url="https://api.perfectaudience.com/reports/campaign_report?site_id=".getenv('PA_SITE_ID')."&interval=lifetime";
                $response = Curl::to($api_url)
                    ->withHeader('Authorization:' . $accessToken . '')
                    ->asJson()
                    ->get();
                if ($response->status == 200) {
                    $i = 0;
                    $campaigns = array();
                    foreach ($response->campaigns as $item) {
                        $campaigns[$i]['_id'] = $item->_id;
                        $campaigns[$i]['name'] = $item->name;
                        $i++;
                    }
                    return response()->success($campaigns);
                }
            }
        }
    }
    public function getAdsBySiteId()
    {
        $accessToken = self::accessToken();

        if ($accessToken) {
            $data = Input::get();
            $user = Auth::user();
            $company_id = $user->company_id;
            $pa_site_data = CompanySetting::where('company_id', $company_id)->where('name', 'pa_site_id')->first();

            if ($pa_site_data) {
                $pa_site_id = $pa_site_data->value;
                $api_url = "https://api.perfectaudience.com/reports/ad_report?site_id=" . $pa_site_id . "&interval=lifetime&start_date=" . $data['start_date'] . "&end_date=" . $data['end_date'] . "";
                $response = Curl::to($api_url)
                    ->withHeader('Authorization:' . $accessToken . '')
                    ->asJson()
                    ->get();
                if ($response->status == 200) {
                    return response()->success($response->report);
                }
            }
            return response()->error("Site ID Not Found in compmany settings");
        }
    }
    public function getCampaignGraph()
    {
        $accessToken = self::accessToken();
        if ($accessToken) {
            $data = Input::get();
        }
    }

    public static function getUpdatePerfectAudienceData()
    {
        $cron_id = CronHelper::createCronRecord('update_perfectaudience_data');
        $all_compnies = CompanyHelper::getAllCompanies();
        $email = AppOptionsHelper::getOptionValue('pa_email');
        $password = AppOptionsHelper::getOptionValue('pa_password');
        $api_url = "https://api.perfectaudience.com/auth?email=" . $email . "&password=" . $password . "";
        $response = Curl::to($api_url)->asJson()->post();
        if ($response && $response->status == '200') {
            $accessToken = $response->token;
        }
        //$accessToken = $this->accessToken();
        foreach ($all_compnies as $key => $company) {
            $company_id = $company['id'];
            $site_id = CompanySettingsHelper::getSetting($company_id, "pa_site_id");

            /* If Company Having Site ID */
            if ($site_id != false) {
                $campigns_data = self::getCampignsDataPA($accessToken, $site_id, 'today');
                if (isset($campigns_data->report)) {
                    $all_campaigns = $campigns_data->report;
                    foreach ($all_campaigns as $key => $value) {
                        self::insertPerfectAudienceData($value, $company_id, $site_id);
                    }
                }
            }
        }
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('Perfect Audience Data Update Success');
    }

    public static function getCampignsDataPA($access_token, $site_id, $interval = 'today')
    {
        $api_url = "https://api.perfectaudience.com/reports/campaign_report?site_id=$site_id&interval=$interval";
        $response = Curl::to($api_url)
            ->withHeader('Authorization:' . $access_token . '')
            ->asJson()
            ->get();
        return $response;
    }

    public static function insertPerfectAudienceData($data, $company_id, $site_id)
    {
        $ins_data = [];
        $created_at = new dateTime();
        foreach ($data as $key => $value) {
            $ins_data[$key] = $value;
        }
        $ins_data['company_id'] = $company_id;
        $ins_data['site_id'] = $site_id;
        $ins_data['created_at'] = $created_at;
        PerfectAudienceData::insert($ins_data);
    }

    public function getDataWidgets()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = input::all();
        $type = 'ctr';
        $campaign_id = null;
        if (isset($input_data['type'])) {
            $type = $input_data['type'];
        }
        if (isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];

            if (isset($input_data['pa_campaign_id'])) {
                $campaign_id = $input_data['pa_campaign_id'];
            }

            $statics = self::getDataStaticByDate($company_id, $start_date, $end_date, $type, $campaign_id);
            return response()->success(compact('statics'));
        }
        return response()->error('Somthing Went Wrong');
    }

    public static function getDataStaticByDate($company_id, $start_date, $end_date, $type = 'ctr', $campign_id = null)
    {
        $where = array();

        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();
        if ($campign_id != null) {
            $camp_w_array = array('campaign_id', '=', $campign_id);
            array_push($where, $camp_w_array);
        }

        /* *************  Get Data From Database ************* */
        $impressions = PerfectAudienceData::groupBy(DB::raw('date(created_at)'))
            ->select(DB::raw('sum(impressions) as impressions'), DB::raw('date(created_at) as date'))
            ->whereBetween('created_at', array($start_time, $end_time))
            ->where('company_id', $company_id)
            ->where($where)
            ->get()
            ->toArray();

        $reponses = PerfectAudienceData::groupBy(DB::raw('date(created_at)'))
            ->select(DB::raw('sum(' . $type . ') as reponses'), DB::raw('date(created_at) as date'))
            ->whereBetween('created_at', array($start_time, $end_time))
            ->where('company_id', $company_id)
            ->where($where)
            ->get()
            ->toArray();

        $dates = getDatesFromRange($start_date, $end_date);
        foreach ($dates as $key => $date) {
            $impressions_count = 0;
            $respons_count = 0;
            foreach ($impressions as $key => $impression) {
                if (isset($impression['date']) && $impression['date'] == $date) {
                    $impressions_count = $impression['impressions'];
                }
            }
            foreach ($reponses as $key => $reponse) {
                if (isset($reponse['date']) && $reponse['date'] == $date) {
                    $respons_count = round($reponse['reponses'], 2);
                }
            }
            $out[] = array('date' => date('M d', strtotime($date)), 'impressions' => $impressions_count, 'responses' => $respons_count);
        }
        return $out;
    }
}
