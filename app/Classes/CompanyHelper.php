<?php

namespace App\Classes;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Company;
use App\CompanySetting;
use App\EgTerm;
use App\NotificationLogs;
use App\SiNetwork;
use App\SocialAccountUrl;
use App\User;
use Auth;
use Carbon\Carbon;
use Curl;
use DateTime;

class CompanyHelper
{
    public static function getAllCompanies()
    {
        $companies = Company::where(['is_active' => 1])->get();
        if (count($companies) > 0) {
            $companies = $companies->toArray();
        } else {
            $companies = false;
        }
        return $companies;
    }

    public static function getAllCompaniesSuperadmin()
    {
        $companies = Company::select('id', 'name', 'api_key')->where(['is_active' => 1])->get();
        if (count($companies) > 0) {
            $companies = $companies->toArray();
        } else {
            $companies = false;
        }
        return $companies;
    }

    public static function getAllUserCompanies($userId, $role)
    {

        $companies = Company::select(['id', 'name', 'api_key'])->where(['is_active' => 1]);
        $user_companies = \App\Classes\UserHelper::getUserCompanies($userId);

        if ($role != 'super.admin.agent') {
            $companies = $companies
                ->whereIn('id', $user_companies);
        } else {
            $companies = $companies->where('owner_id', $userId);
        }

        $companies = $companies->get();
        if (count($companies) > 0) {
            $companies = $companies->toArray();
        } else {
            $companies = false;
        }
        return $companies;
    }

    public static function is_company_active($company_id)
    {
        $active = Company::where(['is_active' => 1, 'id' => $company_id])->first();
        if (count($active) > 0) {
            return true;
        }
        return false;
    }

    public static function is_api_active($api_key)
    {
        $active = Company::where(['is_active' => 1, 'api_key' => $api_key])->first();
        if (count($active) > 0) {
            return true;
        }
        return false;
    }

    public static function isApiExists($api_key)
    {
        $company = company::select('id')->where(['api_key' => $api_key, 'is_active' => 1])->first();
        if (count($company) > 0) {
            return $company->id;
        } else {
            return false;
        }
    }

    public static function getAllDoctors($company_id)
    {
        /* Get users who have role_id 5 */
        $users = User::with('roles')
            ->where('company_id', $company_id)
            ->whereHas(
                'roles',
                function ($q) {
                    $q->where('role_id', 5);
                }
            )
            ->select(['name', 'id'])
            ->get();

        return $users;
    }

    public static function getCompanyDetais($company_id)
    {
        $company = company::find($company_id);
        if (count($company) > 0) {
            return $company->toArray();
        }
        return false;
    }

    public static function getCompanyDetailsFields($company_id, $fields = null)
    {
        $select = "*";
        if ($fields != null) {
            $select = $fields;
        }

        $company = company::select($select)
            ->where('id', $company_id)
            ->first();
        if (count($company) > 0) {
            return $company->toArray();
        }
        return false;
    }

    public static function getCompanyDetaisByApi($api_key)
    {
        $company = company::where('api_key', $api_key)->first()->toArray();
        if (count($company) > 0) {
            return $company;
        }
        return false;
    }

    public static function getFacebookPageInsightData($start_date, $end_date, $company_id)
    {
        $e_date = new DateTime(date('Y-m-d', $end_date));
        $e_date->modify('+1 day');
        $end_date = strtotime($e_date->format('Y-m-d'));
        $fbpagedata = SiNetwork::where('company_id', $company_id)->where('network_name', 'facebook_pages')->first();
        if (!empty($fbpagedata)) {
            $facebook_page_id = $fbpagedata->net_id;
            $facebook_access_token = $fbpagedata->token;
            $fb_app_id = AppOptionsHelper::getOptionValue('facebook_app_id');
            $fb_app_secret = AppOptionsHelper::getOptionValue('facebook_app_secret');
            $APPLICATION_ID = $fb_app_id;
            $APPLICATION_SECRET = $fb_app_secret;

            $full_url = 'https://graph.facebook.com/v2.12/' . $facebook_page_id;
            $response = Curl::to($full_url)
                ->withData(array('access_token' => $facebook_access_token, 'fields' => 'access_token'))
                ->asJson(true)
                ->get();
            if (!isset($response['access_token'])) {
                return array(
                    'page_fans' => 0,
                    'page_impressions' => 0,
                    'page_post_engagements' => 0,
                    'page_views_total' => 0
                );
            }
            $page_access_token = $response['access_token'];
            $full_url = 'https://graph.facebook.com/v2.12/' . $facebook_page_id . '/insights';
            $response = Curl::to($full_url)
                ->withData(
                    array(
                        'access_token' => $page_access_token,
                        'metric' => 'page_impressions,page_post_engagements,page_views_total',
                        'since' => $start_date,
                        'until' => $end_date,
                    )
                )
                ->asJson()
                ->get();

            $full_url_page_like = 'https://graph.facebook.com/v2.12/' . $facebook_page_id;
            $response_page_like = Curl::to($full_url_page_like)
                ->withData(
                    array(
                        'access_token' => $page_access_token,
                        'fields' => 'fan_count',
                    )
                )
                ->asJson()
                ->get();
            $page_fans = 0;
            $page_impressions = 0;
            $page_views_total = 0;
            $page_engaged_users = 0;

            if (isset($response_page_like->fan_count)) {
                $page_fans = $response_page_like->fan_count;
            }

            if (count($response) > 0 && isset($response->data)) {
                foreach ($response->data as $data) {
                    if ($data->name == 'page_impressions' && $data->period == 'day') {
                        $page_impressions = 0;
                        if (!empty($data->values)) {
                            foreach ($data->values as $piv) {
                                $page_impressions += isset($piv->value) ? $piv->value : 0;
                            }
                        }
                    }
                    if ($data->name == 'page_post_engagements' && $data->period == 'day') {
                        $page_engaged_users = 0;
                        if (!empty($data->values)) {
                            foreach ($data->values as $peuv) {
                                $page_engaged_users += isset($peuv->value) ? $peuv->value : 0;
                            }
                        }
                    }
                    if ($data->name == 'page_views_total' && $data->period == 'day') {
                        $page_views_total = 0;
                        if (!empty($data->values)) {
                            foreach ($data->values as $psv) {
                                $page_views_total += $psv->value ? $psv->value : 0;
                            }
                        }
                    }
                }
            }

            return array(
                'page_fans' => $page_fans,
                'page_impressions' => $page_impressions,
                'page_post_engagements' => $page_engaged_users,
                'page_views_total' => $page_views_total
            );
        }
        return array('page_fans' => 0, 'page_impressions' => 0, 'page_post_engagements' => 0, 'page_views_total' => 0);
    }

    public static function getTwitterTimelineData($start_date, $end_date, $company_id)
    {
        $network_details = SocialconnectHelper::getNetworkDetails($company_id, 'twitter');
        if (!empty($network_details)) {
            $network_details = $network_details->toArray();
            $twitter_app_id = AppOptionsHelper::getOptionValue('twitter_app_id');
            $twitter_app_secret = AppOptionsHelper::getOptionValue('twitter_app_secret');
            $connection = new TwitterOAuth($twitter_app_id, $twitter_app_secret, $network_details['token'],
                $network_details['secret']);
            $tweets = $connection->get("statuses/user_timeline", array("user_id" => $network_details['net_id']));
            if (isset(current($tweets)->user)) {
                $userinfo = current($tweets)->user;
                return array(
                    'followers_count' => $userinfo->followers_count,
                    'friends_count' => $userinfo->friends_count,
                    'tweets' => $userinfo->statuses_count
                );
            }
            return array('followers_count' => 0, 'friends_count' => 0, 'tweets' => 0);
        }
        return array('followers_count' => 0, 'friends_count' => 0, 'tweets' => 0);
    }

    public static function getInstaData($company_id)
    {
        $network_details = SocialconnectHelper::getNetworkDetails($company_id, 'instagram');
        if (!empty($network_details)) {
            $network_details = $network_details->toArray();
            $full_url = "https://api.instagram.com/v1/users/self/";
            $response = Curl::to($full_url)->withData(
                array(
                    'access_token' => $network_details['token'],
                )
            )
                ->asJson()
                ->get();
        }
        if (isset($response)) {
            return array(
                'media' => $response->data->counts->media,
                'follows' => $response->data->counts->follows,
                'followed_by' => $response->data->counts->followed_by
            );
        } else {
            return array('media' => '0', 'follows' => '0', 'followed_by' => '0');
        }
    }

    public static function getlinkedInData($start_date, $end_date, $company_id)
    {
        $network_details = SocialconnectHelper::getNetworkDetails($company_id, 'linkedin');
        if (!empty($network_details)) {
            $network_details = $network_details->toArray();
            $full_url = "https://api.linkedin.com/v1/people/~:(id,first-name,last-name,num-connections)";
            $response = Curl::to($full_url)->withData(
                array(
                    'oauth2_access_token' => $network_details['token'],
                )
            )
                ->get();
            $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, true);
            if (isset($array['num-connections'])) {
                return $array['num-connections'];
            }
        }
        return 0;
    }

    public static function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0C2f) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0x2Aff),
            mt_rand(0, 0xffD3),
            mt_rand(0, 0xff4B)
        );
    }

    public static function deleteCompany($company_id)
    {
        Company::find($company_id)->delete();
        return true;
    }

    public static function deleteCompanyContacts($company_id)
    {
        \App\Contact::where('company_id', $company_id)->delete();
        return true;
    }

    public static function deleteCompanyAppointments($company_id)
    {
        \App\Appointment::where('company_id', $company_id)->delete();
        return true;
    }

    public static function deleteCompanySettings($company_id)
    {
        \App\CompanySetting::where('company_id', $company_id)->delete();
        return true;
    }

    public static function deleteCompanyNotificationMails($company_id)
    {
        \App\NotificationSetting::where('company_id', $company_id)->delete();
        return true;
    }

    public static function deleteCompanyCalls($company_id)
    {
        \App\CallRecord::where('company_id', $company_id)->delete();
        return true;
    }

    public static function deleteCompanySms($company_id)
    {
        \App\SmsRecord::where('company_id', $company_id)->delete();
        return true;
    }

    public static function deleteCompanyUsers($company_id)
    {
        \App\User::where('company_id', $company_id)->delete();
        return true;
    }

    public static function updateSocialCodes($company_id, $code)
    {
        company::where('id', $company_id)->update(['social_string_keys' => $code]);
    }

    public static function getSocialCodes($company_id)
    {
        $codes = company::select('social_string_keys')->where('id', $company_id)->first();
        if (count($codes) > 0) {
            return $codes->social_string_keys;
        }
        return false;
    }

    public static function getCompanyIdByApi($api_key)
    {
        $company_id = Company::select('id')->where('api_key', $api_key)->first();
        if (count($company_id) > 0) {
            return $company_id->id;
        }
        return false;
    }

    public static function updateCompanyStatus($company_id, $status)
    {
        Company::where('id', $company_id)->update(['is_active' => $status]);
        return true;
    }

    public static function getCompanyAgencyStatus($company_id)
    {
        $agency_status = Company::select('agency_status')->where('id', $company_id)->first();
        if (count($agency_status) > 0) {
            return $agency_status->agency_status;
        }
        return false;
    }

    public static function updateCompanyAgencyStatus($company_id, $status)
    {
        Company::where('id', $company_id)->update(['agency_status' => $status]);
        return true;
    }

    public static function convertCompanyTime($value, $company_id, $format = null)
    {
        if ($format == null) {
            $format = 'Y-m-d H:i:s';
        }

        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        /* If Timezone Set */
        if ($tz != '' && $tz != false) {
            return Carbon::createFromTimestamp(strtotime($value))
                ->timezone($tz)
                ->format($format);
        } else {
            return $value;
        }
    }

    public static function getCompanyID($api_key = null)
    {
        $company_id = false;
        if (!empty($api_key)) {
            $company = Company::where('api_key', $api_key)->first();
            if (!empty($company)) {
                $company_id = $company->id;
            }
        } else {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        return $company_id;
    }

    public static function getCompanyTimezone($company_id = null)
    {
        if (empty($company_id)) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $company = CompanySetting::where('name', 'timezone')->where('company_id', $company_id)->first();
        if ($company) {
            return $company->value;
        }
        return false;
    }

    public static function AnyTimeZoneToUTC($datetime, $company_timezone)
    {
        $given = new \DateTime($datetime, new \DateTimeZone($company_timezone));
        $given->setTimezone(new \DateTimeZone("UTC"));
        $output = $given->format("Y-m-d H:i:s");
        return $output;
    }

    public static function AnyUtcToTimeZone($datetime, $company_timezone)
    {
        $given = new \DateTime($datetime, new \DateTimeZone('UTC'));
        $given->setTimezone(new \DateTimeZone($company_timezone));
        $output = $given->format('Y-m-d H:i:s');
        return $output;
    }

    public static function getCompanyUserID($api_key = null)
    {
        $user_id = false;
        if (!empty($api_key)) {
            $company = Company::where('api_key', $api_key)->first();
            if (!empty($company)) {
                $user_id = $company->user_id;
            }
        } else {
            $user = Auth::user();
            $user_id = $user->id;
        }
        return $user_id;
    }

    public static function updateCompanyDetailsHDid($HD_id, $company_id, $hd_publish_status = null)
    {
        $update = company::where('id', $company_id)->update([
            'hd_post_id' => $HD_id,
            'claim_status' => 'Approved',
            'hd_publish_status' => $hd_publish_status
        ]);
        if ($update) {
            return true;
        }
    }

    public static function updateCompanyDetails($data_update, $company_id)
    {
        $update = company::where('id', $company_id)->update($data_update);
        if ($update) {
            return true;
        }
        return false;
    }

    public static function addSocialUrls($company_id, $term_id, $term_type, $url, $term_key)
    {
        $url_com = new SocialAccountUrl;
        $url_com->type = $term_type;
        $url_com->url = $url;
        $url_com->company_id = $company_id;
        $url_com->term_id = $term_id;
        $url_com->term_key = $term_key;
        $url_com->created_at = new DateTime();
        $url_com->save();
        return $url_com->id;
    }

    public static function isSocialUrlExists($company_id, $term_id, $term_type, $term_key)
    {
        $count_urls = SocialAccountUrl::where([
            'type' => $term_type,
            'term_id' => $term_id,
            'term_key' => $term_key,
            'company_id' => $company_id,
        ])->count();
        if ($count_urls > 0) {
            return true;
        }
        return false;
    }

    public static function getSocialUrlsTerm($company_id, $term_id, $term_type)
    {
        $urls = SocialAccountUrl::where([
            'type' => $term_type,
            'term_id' => $term_id,
            'company_id' => $company_id,
        ])->get();
        if (count($urls) > 0) {
            return $urls->toArray();
        }
        return false;
    }

    public static function bindUrls($data)
    {
        $out = [];
        if (count($data) > 0) {
            foreach ($data as $urls) {
                $out[$urls['term_key']] = $urls['url'];
            }
        }
        return $out;
    }

    public static function SocialUrlUpdate($company_id, $term_id, $term_type, $term_key, $url)
    {
        SocialAccountUrl::where([
            'type' => $term_type,
            'term_id' => $term_id,
            'term_key' => $term_key,
            'company_id' => $company_id,
        ])
            ->update(['url' => $url]);
        return true;
    }

    public static function GetNonHdCompanies()
    {
        $clinics = Company::whereNull('hd_post_id')->get();
        if (count($clinics) > 0) {
            return $clinics->toArray();
        }
        return [];
    }

    public static function recordNotificationLog(
        $objectId,
        $objecType,
        $typ,
        $notificationType,
        $companyId,
        $send_to,
        $message,
        $subject = null,
        $contact_id = null,
        $appointment_id = null
    ) {
        $log = new NotificationLogs();
        $log->object_id = $objectId;
        $log->object_type = $objecType;
        $log->notification_type = $notificationType;
        $log->company_id = $companyId;
        $log->type = $typ;
        $log->contact_id = $contact_id;
        $log->appointment_id = $appointment_id;
        $log->date_sent = new DateTime();
        $log->body = $message;
        $log->subject = $subject;
        $log->send_to = $send_to;
        $log->save();
        return $log->id;
    }

    public static function AddCompnyGoogleAnalyticsProfiles($profiles, $companyId)
    {
        EgTerm::where('term_type', 'google_analytics_profile')
            ->where('company_id', $companyId)
            ->delete();
        $ins_data = [];
        if (count($profiles) > 0) {
            foreach ($profiles as $key => $profile) {
                $data = json_encode($profile);
                $ins_data[] = [
                    'company_id' => $companyId,
                    'term_type' => 'google_analytics_profile',
                    'term_value' => $data,
                ];
            }
            EgTerm::insert($ins_data);
        }
    }

    public static function changeIntigrationStatus($companyId, $intigartion_method, $status = 1)
    {
        $intigration_status_exists = CompanySettingsHelper::getSetting($companyId, $intigartion_method);
        if ($intigration_status_exists === false) {
            CompanySettingsHelper::setSetting($companyId, $intigartion_method, $status);
        } else {
            CompanySettingsHelper::updateSetting($companyId, $intigartion_method, $status);
        }
        return true;
    }

    public static function checkConfig_status($company_id)
    {
        $status_list = configStatusesList();
        $isSkipped = (int)CompanySettingsHelper::getSetting($company_id, 'skip_integration');
        if ($isSkipped == 0) {
            foreach ($status_list as $configs) {
                $val = CompanySettingsHelper::getSetting($company_id, $configs);
                if ($val === false || $val == 0) {
                    return 0;
                }
            }
        } else {
            return 3;
        }

        return 1;
    }

    public static function getcompnayConfigStatus($company_id)
    {
        $status_list = configStatusesList();
        $status = 0;
        $out = [];
        foreach ($status_list as $configs) {
            $val = CompanySettingsHelper::getSetting($company_id, $configs);
            $out[$configs] = 1;
            if ($val === false || $val == 0) {
                $out[$configs] = 0;
            }
        }
        return $out;
    }
}
