<?php

namespace App\Http\Controllers;

use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\GoogleanalyticsHelper;
use App\CompanySetting;
use Auth;
use Datatables;
use Input;

class GoogleanalyticsContoller extends Controller
{
    public function getDashboardAnalytics()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $profile_id = GoogleanalyticsHelper::getAnalyticsProfileID($company_id);

        $input_data = input::all();
        if (!empty($profile_id) && isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];
            $report = $this->getUsersReport($start_date, $end_date, $profile_id);
            $total_visits = $report['total_visits'];

            $browser_visits = $this->getBrowerDevicesVisits($start_date, $end_date, $profile_id, $total_visits);
            $traffic_sources = $this->getTrafficSources($start_date, $end_date, $profile_id, $total_visits);
            $roi_data = $this->getRoiData($start_date, $end_date, $profile_id, $company_id);

            $visitor_report = $report['visits'];
            $metrics = $report['metrics'];
            $sessions = $report['sessions'];
            $data = compact('visitor_report', 'metrics', 'browser_visits', 'traffic_sources', 'roi_data', 'sessions');
            //dd($data);
            return response()->success($data);
        }

        //return response()->error('Somthing Went Wrong');
    }

    public function initializeAnalytics()
    {
        try {
            if (Auth::user()) {
                $user = Auth::user();
                $company_id = $user->company_id;
            } else {
                $company_id = $this->company_id;
            }
            $analytics_key = GoogleanalyticsHelper::getAnalyticsFilePath();
            // Create and configure a new client object.
            $client = new \Google_Client();
            $client->setApplicationName("Hello Analytics Reporting");
            $client->setAuthConfig($analytics_key);
            $client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
            $analytics = new \Google_Service_Analytics($client);
            return $analytics;
        } catch (\Exception $e) {
            return false;
        }
    }

    /*------------ Function for get Visitor from google analytics ------------ */
    public function getUsersReport($start_date, $end_end, $profile_id)
    {
        try {
            $date_s = date('Y-m-d', strtotime($start_date));
            $date_e = date('Y-m-d', strtotime($end_end));

            $days = getCountDaysBeetweenDates($date_s, $date_e);
            if ($days <= 15) {
                $dimensions_ac = "ga:day";
                $format = 'M d';
                $day_series = getDatesFromRange($date_s, $date_e, $format);
            } elseif ($days > 15 && $days <= 120) {
                $format = "d M";

                $dimensions_ac = "ga:week";
                $day_series = getWeeksDaysBetweenDays($date_s, $date_e, $format);
                //print_r($dimensions_ac); die();
            } else {
                $format = "M y";

                $dimensions_ac = "ga:month";
                $day_series = getMonthsDaysBetweenDays($date_s, $date_e, $format);
            }
            /*else{

            $format = "M y";
            $dimensions_ac = "ga:year";
            $day_series =  getYearsBetweenDays($date_s, $date_e, $format);
            }*/

            $analytics = $this->initialGoogleAnalytics();
            $dimensions_ac = "ga:day";
            $total_visits = 0;
            $webvisits = array();
            $optParams = array(
                'dimensions' => $dimensions_ac);
            $results = $analytics->data_ga->get(
                'ga:' . $profile_id,
                $date_s,
                $date_e,
                'ga:sessions',
                $optParams
            );

            $rows = $results->getRows();
            $sessions = $results['totalsForAllResults']['ga:sessions'];

            $i = 0;
            foreach ($day_series as $key => $date_ana) {
                $visits = $results->rows[$i][1];
                $webvisits[] = array('date' => $date_ana, 'visits' => $visits);
                $total_visits = $total_visits + $visits;
                $i++;
            }

            $result_metric = $analytics->data_ga->get(
                'ga:' . $profile_id,
                $date_s,
                $date_e,
                'ga:pageviews,ga:uniqueDimensionCombinations,ga:bounceRate,ga:uniquePageviews,ga:users'
            );

            $metric_rows = $result_metric->getRows();
            //print_r($metric_rows); die();
            $out['metrics'] = array(
                'page_views' => $metric_rows[0][0],
                'unique_views' => $metric_rows[0][1],
                'bounce_date' => round($metric_rows[0][2], 2),
                'page_view' => $metric_rows[0][3],
                'users' => $metric_rows[0][4],
            );
            $out['visits'] = $webvisits;
            $out['total_visits'] = $metric_rows[0][4];
            $out['sessions'] = $sessions;
            return $out;
        } catch (\Exception $e) {
            return false;
        }
    }

    /*------------ Function for get Browsers from google analytics ------------ */

    public function getBrowerDevicesVisits($start_date, $end_end, $profile_id, $total_visits)
    {
        try {
            $date_s = date('Y-m-d', strtotime($start_date));
            $date_e = date('Y-m-d', strtotime($end_end));
            $analytics = $this->initialGoogleAnalytics();
            $mobile_visit = 0;
            $web_visit = 0;
            $mobile_browser_visits = array();
            $web_browser_visits = array();

            $optParams = array(
                'dimensions' => 'ga:deviceCategory,ga:browser');

            $results = $analytics->data_ga->get(
                'ga:' . $profile_id,
                $date_s,
                $date_e,
                'ga:users',
                $optParams
            );

            $data = $results->getRows();
            if (!empty($data)) {
                foreach ($data as $row) {
                    $source = $row[0];
                    $browser = $row[1];
                    $visits = $row[2];

                    /* For web */
                    if ($source == 'desktop') {
                        $web_browser_visits[] = array(
                            'browser' => $browser,
                            'visits' => $visits,
                        );
                        $web_visit = $web_visit + $visits;
                    }

                    /* For Mobile */
                    if ($source == 'mobile') {
                        $mobile_browser_visits[] = array(
                            'browser' => $browser,
                            'visits' => $visits,
                        );
                        $mobile_visit = $mobile_visit + $visits;
                    }
                }
                $out_array = array(
                    'desktop' => round(($web_visit / $total_visits) * 100, 2),
                    'mobile' => round(($mobile_visit / $total_visits) * 100, 2),
                    'desktop_visits' => $web_browser_visits,
                    'mobile_visits' => $mobile_browser_visits,
                );
            } else {
                $out_array = array(
                    'desktop' => 0,
                    'mobile' => 0,
                    'desktop_visits' => 0,
                    'mobile_visits' => 0,
                );
            }
            /* ************ Output ************ */
            return $out_array;
        } catch (\Exception $e) {
            return false;
        }
    }

    /*------------ Function for get Trafic Sources from google analytics ------------ */
    public function getTrafficSources($start_date, $end_end, $profile_id, $total_visits)
    {
        try {
            $date_s = date('Y-m-d', strtotime($start_date));
            $date_e = date('Y-m-d', strtotime($end_end));
            $analytics = $this->initialGoogleAnalytics();
            $webvisits = array();
            $out = array();

            $optParams = array(
                'dimensions' => 'ga:source');

            $results = $analytics->data_ga->get(
                'ga:' . $profile_id,
                $date_s,
                $date_e,
                'ga:users',
                $optParams
            );

            $data = $results->getRows();
            if (!empty($data)) {
                foreach ($data as $key => $row) {
                    $source = $row[0];
                    $visit = $row[1];
                    $visit_per = round((($visit / $total_visits) * 100), 2);

                    $out[] = array(
                        'source' => ucwords(utf8_encode($source)),
                        'visits' => $visit,
                        'visit_per' => $visit_per,
                    );
                }
            }
            return $out;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getRoiData($start_date, $end_end, $profile_id, $company_id)
    {
        $date_s = date('Y-m-d', strtotime($start_date));
        $date_e = date('Y-m-d', strtotime($end_end));
        $goalsFromAdwords = 0;
        $roi_value = 0;
        $webvisits = array();
        $timezone = 'UTC';
        $results = self::fetchAnalytics($profile_id, $date_s, $date_e);
        $timezoneCompany = \App\Classes\CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($timezoneCompany != false) {
            $timezone = $timezoneCompany;
        }
        if (!empty($results->totalsForAllResults)) {
            $data = $results->totalsForAllResults;
            $goalsFromAdwords = $data['ga:goalCompletionsAll'];
        }

        /* Goal Conversions in Google analytics in given time */

        /* Goal From EverGenius */

        /*(Total Apts in given days)*/
        /*$AppointmentBetweenTime = AppointmentsHelper::getCountAppointmentBytime($start_date, $end_end, $company_id);*/

        $callsBetweenTime = \App\Classes\CallsHelper::getCallsLeadsByDate($company_id, $start_date, $end_end);

        $leadsBetweenTime = \App\Classes\LeadHelper::getCountLeadsByDate($start_date, $date_e, $company_id, $timezone);

        /* Total Goals (Total Apts in 30 days  + Calls Received in 30 Days + Goal Conversions in Google analytics in 30 days)*/

        $allGoals = $callsBetweenTime + $goalsFromAdwords + (int) $leadsBetweenTime['today_leads'];

        $settings = CompanySetting::where('company_id', $company_id)->where('name', 'ltv_value')->select('value')->first();

        $mmbudget = CompanySetting::where('company_id', $company_id)->where('name', 'monthly_marketing_budget')->select('value')->first();

        if ($settings && $mmbudget && $allGoals != 0) {
            /**days marketing cost**/

            $total_number_day_in_range = dateCountDiff($date_s, $date_e);
            $days_budget_cost = ($mmbudget->value / 30) * $total_number_day_in_range;
            /**days marketing cost**/

            $totalinvest = 0;
            $total_expenditure = 0;
            $total_appointments = 0;
            $revenuepercentage = 0;
            $exppercentage = 0;

            if ($mmbudget->value && $days_budget_cost != 0) {
                $totalrevenue = $allGoals * $settings->value;

                $totalinvest = round($days_budget_cost, 2);

                $roi_value = round((($totalrevenue - $days_budget_cost) / $days_budget_cost) * 100);
                $total_expenditure = $totalinvest;
                //$total_expenditure     =  $totalinvest-$adcost;
                $total_appointments = $allGoals;

                $revenuepercentage = round((($totalrevenue - $days_budget_cost) / $totalrevenue) * 100);
                $exppercentage = 100 - $revenuepercentage;
                $costperappointment = round($totalinvest / $allGoals, 2);
            }
            return array('roi' => $roi_value, 'revenue' => round($totalrevenue), 'totalexpenditure' => $totalinvest, 'totalinvest' => $totalinvest, 'total_appointments' => $total_appointments, 'revenuepercentage' => $revenuepercentage, 'exppercentage' => round($exppercentage), 'costperappointment' => round($costperappointment));
        }

        return array('roi' => 0, 'revenue' => 0, 'totalexpenditure' => 0, 'totalinvest' => 0, 'total_appointments' => 0, 'revenuepercentage' => 0, 'exppercentage' => 0, 'costperappointment' => 0);
    }

    public function postKeywordAnalytics()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $profile_id = GoogleanalyticsHelper::getAnalyticsProfileID($company_id);

        $input_data = input::all();
        if (!empty($profile_id)) {
            //$start_date = $input_data['start_date'];
            //$end_date     = $input_data['end_date'];
            $date_s = $input_data['customFilter']['start_time'];
            $date_e = $input_data['customFilter']['end_time'];
            //$date_s     = date('Y-m-d', strtotime("-10 day"));
            //$date_e     = date('Y-m-d');

            $analytics = $this->initialGoogleAnalytics();
            $optParams = array(
                'dimensions' => 'ga:campaign,ga:keyword,ga:adGroup',
                'sort' => '-ga:adClicks');

            $results = $analytics->data_ga->get(
                'ga:' . $profile_id,
                $date_s,
                $date_e,
                'ga:impressions,ga:adClicks,ga:adCost,ga:CPC,ga:CTR',
                $optParams
            );

            if (!empty($results->rows)) {
                $data = $results->rows;
                $i = 0;
                $analayticArray = array();
                foreach ($data as $item) {
                    $analayticArray[$i]['campaign'] = $item[0];
                    $analayticArray[$i]['keyword'] = $item[1];
                    $analayticArray[$i]['adGroup'] = $item[2];
                    $analayticArray[$i]['impressions'] = $item[3];
                    $analayticArray[$i]['adClicks'] = $item[4];
                    $analayticArray[$i]['adCost'] = "$" . $item[5];
                    $analayticArray[$i]['CPC'] = "$" . number_format($item[6], 3);
                    $analayticArray[$i]['CTR'] = "$" . number_format($item[7], 3);
                    $i++;
                }
                return Datatables::of(collect($analayticArray))->make(true);
            }
        }
        $analayticArray = [];
        return Datatables::of(collect($analayticArray))->make(true);
    }
    public function getKeywordAnalyticsTop()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $profile_id = GoogleanalyticsHelper::getAnalyticsProfileID($company_id);

        $input_data = input::all();
        if (!empty($profile_id)) {
            //$start_date = $input_data['start_date'];
            //$end_date     = $input_data['end_date'];
            $date_s = $input_data['start_date'];
            $date_e = $input_data['end_date'];
            //$date_s     = date('Y-m-d', strtotime("-10 day"));
            //$date_e     = date('Y-m-d');
            $analytics = $this->initialGoogleAnalytics();
            $optParams = array(
                'dimensions' => 'ga:campaign,ga:keyword,ga:adGroup',
                'sort' => '-ga:adClicks', 'max-results' => '20');

            $results = $analytics->data_ga->get(
                'ga:' . $profile_id,
                $date_s,
                $date_e,
                'ga:impressions,ga:adClicks,ga:adCost,ga:CPC,ga:CTR',
                $optParams
            );

            if (!empty($results->rows)) {
                $data = $results->rows;
                //dd($data);
                $i = 0;
                $analayticArray = array();
                foreach ($data as $item) {
                    $analayticArray[$i]['campaign'] = $item[0];
                    $analayticArray[$i]['keyword'] = $item[1];
                    $analayticArray[$i]['adGroup'] = $item[2];
                    $analayticArray[$i]['impressions'] = $item[3];
                    $analayticArray[$i]['adClicks'] = $item[4];
                    $analayticArray[$i]['adCost'] = $item[5];
                    $analayticArray[$i]['CPC'] = number_format($item[6], 3);
                    $analayticArray[$i]['CTR'] = number_format($item[7], 3);
                    $i++;
                }
                return response()->success($analayticArray);
            }

            return response()->success('No Record Found');
        }
    }

    public static function getGoogleAnalyticsData($company_id, $start_date, $end_date)
    {
        $google_c = new GoogleanalyticsContoller;
        $google_c->company_id = $company_id;

        $profile_id = GoogleanalyticsHelper::getAnalyticsProfileID($company_id);
        if (!empty($profile_id) && isset($start_date) && isset($end_date)) {
            $report = $google_c->getUsersReport($start_date, $end_date, $profile_id);
            $total_visits = $report['total_visits'];
            $browser_visits = $google_c->getBrowerDevicesVisits($start_date, $end_date, $profile_id, $total_visits);
            $traffic_sources = $google_c->getTrafficSources($start_date, $end_date, $profile_id, $total_visits);
            $roi_data = $google_c->getRoiData($start_date, $end_date, $profile_id, $company_id);
            $visitor_report = $report['visits'];
            $metrics = $report['metrics'];
            $sessions = $report['sessions'];
            $data = compact('visitor_report', 'metrics', 'browser_visits', 'traffic_sources', 'roi_data', 'sessions');
            return $data;
        }
    }

    public static function fetchAnalytics($profile_id, $date_s, $date_e)
    {
        //try {
        $obj = new GoogleanalyticsContoller;
        $analytics = $obj->initialGoogleAnalytics();
        //var_dump($analytics); die;
        if ($analytics = false) {
            $results = $analytics->data_ga->get('ga:' . $profile_id, $date_s, $date_e, 'ga:adCost,ga:goalCompletionsAll');
            return $results;
        }
        return false;
    }
    public function getConnectGoogleLoginUrl($apikey = null)
    {
        session_start();
        if ($apikey == null) {
            return response()->error('Wrong API key');
        }

        $companyID = \App\Classes\CompanyHelper::getCompanyIdByApi($apikey);
        if ($companyID) {
            $_SESSION['company_id'] = $companyID;
            \Session::put('companyId', $companyID);
            \session::keep(['companyId']);
            $auth_url = $this->getConnectGoogleLogin();
            return redirect($auth_url);
        }
    }

    public function getConnectGoogleLogin()
    {
        $analytics_key = storage_path() . '/app/google_analytics_token.json';
        $client = new \Google_Client();
        $client->setAuthConfig($analytics_key);
        $client->setAccessType("offline"); // offline access
        $client->setIncludeGrantedScopes(true); // incremental auth
        $client->setApprovalPrompt('force');
        $client->addScope(['https://www.googleapis.com/auth/analytics.readonly']);
        $callBackUrl = url('api/google/callback/');
        $client->setRedirectUri($callBackUrl);
        $auth_url = $client->createAuthUrl();
        return $auth_url;
    }

    public function getGoogleTokenCallack()
    {
        session_start();
        if (!isset($_SESSION['company_id'])) {
            $redirectUrl = url('/') . '/#/connect-google-analytics/';
            CompanyHelper::AddCompnyGoogleAnalyticsProfiles([], $company_id);
            return redirect($redirectUrl);
        }
        $profiles = [];
        $company_id = $_SESSION['company_id'];
        unset($_SESSION['company_id']);
        try {
            $input = Input::get('code');
            $analytics_key = storage_path() . '/app/google_analytics_token.json';
            $client = new \Google_Client();
            $client->setAuthConfig($analytics_key);
            $credentials = $client->authenticate($_GET['code']);
            $google_token = $client->getAccessToken();

            $access_token = $google_token['access_token'];
            $refresh_token = $google_token['refresh_token'];
            $is_toke_exists = CompanySettingsHelper::getSetting($company_id, 'google_analytics_token');
            if ($is_toke_exists == false) {
                CompanySettingsHelper::setSetting($company_id, 'google_analytics_token', $access_token);
            } else {
                CompanySettingsHelper::updateSetting($company_id, 'google_analytics_token', $access_token);
            }
            $is_refresh_token_exists = CompanySettingsHelper::getSetting($company_id, 'google_analytics_refresh_token');
            if ($is_refresh_token_exists == false) {
                CompanySettingsHelper::setSetting($company_id, 'google_analytics_refresh_token', $refresh_token);
            } else {
                CompanySettingsHelper::updateSetting($company_id, 'google_analytics_refresh_token', $refresh_token);
            }

            $analytics = new \Google_Service_Analytics($client);
            $accounts = $analytics->management_accounts->listManagementAccounts();
        } catch (\Exception $e) {
            /*When Not any google analytics Account is present*/
            $redirectUrl = url('/') . '/#/connect-google-analytics/';
            CompanyHelper::AddCompnyGoogleAnalyticsProfiles([], $company_id);
            return redirect($redirectUrl);
        }

        $items = $accounts->getItems();
        foreach ($items as $item) {
            $profiles[] = ['name' => $item['name'], 'id' => $item['id']];
        }
        CompanyHelper::AddCompnyGoogleAnalyticsProfiles($profiles, $company_id);
        $redirectUrl = url('/') . '/#/connect-google-analytics/';
        return redirect($redirectUrl);
    }

    public static function GetProfileID($firstAccountId, $analytics)
    {
        $properties = $analytics->management_webproperties
            ->listManagementWebproperties($firstAccountId);
        if (count($properties->getItems()) > 0) {
            $items = $properties->getItems();
            $firstPropertyId = $items[0]->getId();

            // Get the list of views (profiles) for the authorized user.
            $profiles = $analytics->management_profiles
                ->listManagementProfiles($firstAccountId, $firstPropertyId);

            if (count($profiles->getItems()) > 0) {
                $items = $profiles->getItems();
                // Return the first view (profile) ID.
                return $items[0]->getId();
            } else {
                throw new Exception('No views (profiles) found for this user.');
            }
        }
    }

    public static function intialGoogleAuth($company_id)
    {
        self::refreshGoogleToken($company_id);
        $analytics_key = storage_path() . '/app/google_analytics_token.json';
        $client = new \Google_Client();
        $client->setAuthConfig($analytics_key);
        $client->addScope(['https://www.googleapis.com/auth/analytics.readonly']);
        $token = CompanySettingsHelper::getSetting($company_id, 'google_analytics_token');
        if ($token) {
            $client->setAccessToken($token);
            return $client;
        }
        return false;
    }

    public static function refreshGoogleToken($company_id)
    {
        $analytics_key = storage_path() . '/app/google_analytics_token.json';
        $client = new \Google_Client();
        $client->setAuthConfig($analytics_key);
        $client->addScope(['https://www.googleapis.com/auth/analytics.readonly']);
        $token = CompanySettingsHelper::getSetting($company_id, 'google_analytics_token');
        if ($token) {
            $client->setAccessToken($token);
            $old_refresh_token = CompanySettingsHelper::getSetting($company_id, 'google_analytics_refresh_token');
            $client->refreshToken($old_refresh_token);
            $google_token = $client->getAccessToken();
            $access_token = $google_token['access_token'];
            $refresh_token = $google_token['refresh_token'];
            CompanySettingsHelper::updateSetting($company_id, 'google_analytics_token', $access_token);
            CompanySettingsHelper::updateSetting($company_id, 'google_analytics_refresh_token', $refresh_token);
            return true;
        }
        return false;
    }

    public function initialGoogleAnalytics()
    {
        try {
            if (Auth::user()) {
                $user = Auth::user();
                $company_id = $user->company_id;
            } else {
                $company_id = $this->company_id;
            }
            // echo $this->company_id; die;
            $is_profile_exists = CompanySettingsHelper::getSetting($company_id, 'analytics_profile_id');
            $google_analytics_setup = CompanySettingsHelper::getSetting($company_id, 'google_analytics_setup');

            if ($is_profile_exists && $google_analytics_setup == false) {
                $gc = new GoogleanalyticsContoller();
                $gc->company_id = $company_id;
                return $gc->initializeAnalytics();
            }
            $googleAuth = self::intialGoogleAuth($company_id);

            if ($googleAuth) {
                $analytics = new \Google_Service_Analytics($googleAuth);
                return $analytics;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getGooglAnalyticSites()
    {
        if (Auth::user()) {
            $out = [];
            $user = Auth::user();
            $company_id = $user->company_id;
            $terms = \App\Classes\ContactHelper::getTermsByType($company_id, 'google_analytics_profile');
            foreach ($terms as $profile) {
                $out[] = json_decode($profile['value']);
            }
            $sites = $out;
            return response()->success(compact('sites'));
        } else {
            return response()->error('Please Login To account');
        }
    }

    public function postGoogleAnalyticSite()
    {
        $site_id = Input::get('site_id');
        if ($site_id) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $analytics = $this->initialGoogleAnalytics();
            $profile_id = self::GetProfileID($site_id, $analytics);
            $is_profile_exists = CompanySettingsHelper::getSetting($company_id, 'analytics_profile_id');
            if ($is_profile_exists == false) {
                CompanyHelper::changeIntigrationStatus($company_id, 'google_analytics_setup');
                CompanySettingsHelper::setSetting($company_id, 'analytics_profile_id', $profile_id);
            } else {
                CompanyHelper::changeIntigrationStatus($company_id, 'google_analytics_setup');
                CompanySettingsHelper::updateSetting($company_id, 'analytics_profile_id', $profile_id);
            }
            return response()->success(['status' => 'success']);
        }
        return response()->error('Site Id is required.');
    }
}
