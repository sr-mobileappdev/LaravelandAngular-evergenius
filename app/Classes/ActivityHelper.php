<?php
namespace App\Classes;

use App\Activity;
use App\Classes\CallsHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\EmailMarketingHelper;
use App\Classes\LeadHelper;
use App\Classes\PushNotificationHelper;
use App\Classes\UserHelper;
use App\Resource;
use Auth;
use Carbon\Carbon;
use DateTime;

class ActivityHelper
{
    public static function createActivity($company_id, $activity_type, $object_type, $object_id, $contact_id = null, $user_id = null, $created_by = null)
    {

        /* Time When Activity Created */
        $date_now = new dateTime();
        $actitity = new Activity;
        $actitity->company_id = $company_id;
        $actitity->activity_type = $activity_type;
        $actitity->object_type = $object_type;
        $actitity->object_id = $object_id;
        $actitity->contact_id = $contact_id;
        $actitity->user_id = $user_id;
        $actitity->created_by = $created_by;
        $actitity->created_at = $date_now;
        $actitity->save();

        PushNotificationHelper::sendPushNotification($company_id, $activity_type, $object_type, $object_id, $contact_id, $user_id, $created_by, $date_now);
        return $actitity->id;
    }

    public static function getRecentActivity($date_to = '', $date_from = '', $contact_id = '', $last_id = '', $user_id = '')
    {
        $where = array();
        $user = Auth::user();
        $company_id = $user->company_id;
        $com_filter = array('company_id', '=', $company_id);
        array_push($where, $com_filter);
        if ($date_to != '' && $date_from != '') {
            $date_s = date('Y-m-d 00:00:00', strtotime($date_to));
            $date_e = date('Y-m-d 23:59:59', strtotime($date_from));
            $d_s = array('created_at', '>=', $date_s);
            $d_e = array('created_at', '<=', $date_e);
            array_push($where, $d_s, $d_e);
        }

        if ($contact_id != '') {
            $contact_s = array('contact_id', '=', $contact_id);
            array_push($where, $contact_s);
        }

        if ($last_id != '') {
            $last_s = array('id', '<', $last_id);
            array_push($where, $last_s);
        }

        if ($user_id != '') {
            $da = array('activity_type', '!=', 'NEW_DOCTOR_ADDED');
            array_push($where, $da);
            $us = array('user_id', '=', $user_id);
            array_push($where, $us);
        }

        $activites = Activity::where($where)->where('company_id', $company_id)->orderBy('id', 'desc')->offset(0)
            ->limit(20)->get()->toArray();
        $out = ActivityHelper::translateActivites($activites);

        return $out;
    }

    public static function translateActivites($activites)
    {
        $out = array();
        foreach ($activites as $key => $activity) {
            $tz = CompanySettingsHelper::getSetting($activity['company_id'], 'timezone');

            $activity_type = $activity['activity_type'];
            $activity_id = $activity['id'];
            $object_type = $activity['object_type'];
            $company_id = $activity['company_id'];
            $object_id = $activity['object_id'];
            $contact_id = $activity['contact_id'];
            $user_id = $activity['user_id'];
            $created_by = $activity['created_by'];
            $created_at = $activity['created_at'];

            $msg = ActivityHelper::translateActivity($company_id, $activity_type, $object_type, $object_id, $contact_id, $user_id, $created_by, $created_at);

            /* Get TimeZone Time */
            if ($tz != '' && $tz != false) {
                $created_at = Carbon::createFromTimestamp(strtotime($created_at))
                    ->timezone($tz)
                    ->toDateTimeString();
            }

            $out[] = array('message' => $msg,
                'icon' => ActivityHelper::getResourceIcon($activity_type),
                'created_at' => $created_at,
                'activity_id' => $activity_id,
            );
        }
        return $out;
    }

    public static function translateActivity($company_id, $activity_type, $object_type, $object_id, $contact_id, $user_id, $created_by, $created_at)
    {
        $message = '';
        $resource_val = ActivityHelper::getResourceValue($activity_type);

        if ($resource_val) {
            $message = ActivityHelper::parseResource($resource_val, $company_id, $object_type, $object_id, $contact_id, $user_id, $created_by, $created_at);
        }

        return $message;
    }

    public static function getResourceValue($resource_key)
    {
        $resource = Resource::select('resource_value')->where('resource_key', $resource_key)->first();
        if (count($resource) > 0) {
            return $resource->resource_value;
        } else {
            return false;
        }
    }

    public static function getResourceIcon($resource_key)
    {
        $resource = Resource::select('resource_icon')->where('resource_key', $resource_key)->first();
        if (count($resource) > 0) {
            $resource = $resource->toArray();
            return $resource['resource_icon'];
        } else {
            return false;
        }
    }

    public static function parseResource($resource, $company_id, $object_type, $object_id, $contact_id, $user_id, $created_by, $created_at)
    {
        $resource_parsed = $resource;

        /* For Contact details */
        if ($contact_id != null) {
            $contact = ContactHelper::getContactName($contact_id, $company_id);

            if ($contact) {
                $contact_name = ucwords($contact->first_name . ' ' . $contact->last_name);
                $resource_parsed = str_replace('{{contact_name}}', $contact_name, $resource_parsed);
            } else {
                $resource_parsed = '';
            }
        }

        if ($object_type == 'call_records') {
            $phn_num = CallsHelper::getMobileReciveNumber($object_id);
            $resource_parsed = str_replace('{{phone_number}}', '<a href="/#/call-records">' . $phn_num . '</a>', $resource_parsed);
        }

        if ($object_type == 'appointment') {
            $app_id = $object_id;
            $app_url = '<a href="#/appointment/' . $app_id . '" >';
            $resource_parsed = str_replace('[a href="{{appointment_url}}"]', $app_url, $resource_parsed);
            $resource_parsed = str_replace('[/a]', '</a>', $resource_parsed);
        }

        if ($user_id != null) {
            $user_details = UserHelper::getUserDetails($user_id);

            if ($user_details != false) {
                $user_name = ucwords($user_details['name']);
                $resource_parsed = str_replace('{{user_name}}', $user_name, $resource_parsed);
            } else {
                $resource_parsed = '';
            }
        }

        if ($created_by != null) {
            $user_details = UserHelper::getUserDetails($created_by);
            if ($user_details != false) {
                $created_by_name = ucwords($user_details['name']);
                $resource_parsed = str_replace('{{created_by_name}}', $created_by_name, $resource_parsed);
            } else {
                $resource_parsed = '';
            }
        }

        if (strpos($resource_parsed, '<a href="{{contact_link}}" >') !== false) {
            $contact_link = '<a href="#/contact/' . $contact_id . '" >';
            $resource_parsed = str_replace('<a href="{{contact_link}}" >', $contact_link, $resource_parsed);
        }

        if (strpos($resource_parsed, '<a href="{{appointment_link}}" >') !== false) {
            $contact_link = '<a href="#/appointment/' . $object_id . '" >';
            $resource_parsed = str_replace('<a href="{{appointment_link}}" >', $contact_link, $resource_parsed);
        }

        if (strpos($resource_parsed, '<a href="{{doctor_link}}" >') !== false) {
            $user_link = '<a href="#/user-edit/' . $user_id . '" >';
            $resource_parsed = str_replace('<a href="{{doctor_link}}" >', $user_link, $resource_parsed);
        }

        if ($object_type == 'task') {
            $task_info = LeadHelper::getTask($object_id);
            if (count($task_info) > 0) {
                $resource_parsed = str_replace('{{task_title}}', '"' . $task_info['title'] . '"', $resource_parsed);
            } else {
                $resource_parsed = '';
            }
        }

        if ($object_type == 'list_subscribe') {
            $list = EmailMarketingHelper::getListNameById($object_id);
            $list_name = '';
            if (is_array($list)) {
                $list_name = $list['name'];
            }
            $resource_parsed = str_replace('{subscription_list}', '"' . $list_name . '"', $resource_parsed);
        }

        return $resource_parsed;
    }
}
