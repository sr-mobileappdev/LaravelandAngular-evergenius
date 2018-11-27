<?php

namespace App\Http\Controllers;

use App\CallRecord;
use App\Classes\ActivityHelper;
use App\Classes\CallsHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\CronHelper;
use App\Classes\NotificationHelper;
use Auth;
use Carbon\Carbon;
use Datatables;
use DateTime;
use Illuminate\Http\Request;
use Input;

class CallsController extends Controller
{
    public static function StoreScheduledCalls()
    {
        // Create Cron Record
        $last_fetch_time = CronHelper::getRecentExecutedTime('calls_fetch');
        $cron_id = CronHelper::createCronRecord('calls_fetch');
        $compnies = CompanyHelper::getAllCompanies();
        $allCallsRecords = CallsHelper::SaveAllCallsRecords($compnies, $last_fetch_time);
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('success');
    }

    public function getListsCalls()
    {
        $compnies = CompanyHelper::getAllCompanies();
        CallsHelper::SaveAllCallsRecords($compnies);
    }

    public function postIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $where = array();
        $input_data = Input::get();
        $led_s = '';
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
            $d_s = array('call_start_at', '>=', $date_s);
            $d_e = array('call_start_at', '<=', $date_e);
            array_push($where, $d_s, $d_e);
        }
        if (isset($input_data['customFilter']['lead_status']) && $input_data['customFilter']['lead_status'] != '') {
            $led_s = $input_data['customFilter']['lead_status'];
            $l_s = array('lead_status', '=', $led_s);
            if ($led_s != null && $led_s != 3) {
                array_push($where, $l_s);
            }
        }

        if ($led_s != null || $led_s == 3) {
            $data = CallRecord::with('notes')
                ->select(
                    'id',
                    'call_start_at',
                    'call_from',
                    'caller_name',
                    'caller_city',
                    'caller_age',
                    'caller_gender',
                    'call_duration',
                    'recording_url',
                    'contact_id',
                    'lead_status'
                )
                ->where('company_id', $company_id)
                ->where($where)
                ->get();
        }

        if ($led_s == null) {
            $data = CallRecord::select('id', 'call_start_at', 'call_from', 'caller_name', 'caller_city', 'caller_age', 'caller_gender', 'call_duration', 'recording_url', 'contact_id', 'lead_status')
                ->where('company_id', $company_id)
                ->where($where)
                ->WhereNull('lead_status')
                ->get();
        }

        return Datatables::of($data)->make(true);
    }

    public function incomingCallBack(Request $request)
    {
        $call_sid = $request->input('CallSid');
        $RecordingUrl = $request->input('RecordingUrl');
        // If call Record Already Exists
        if (CallsHelper::isCallExists($call_sid)) {
            CallRecord::where('call_sid', '=', $call_sid)
                ->update(array('recording_url' => $RecordingUrl));
        } else {
            $twilioSid = $request->input('AccountSid');
            $company_id = CompanySettingsHelper::getCompanyIdfromValue('twilio_number', $request->input('To'));
            if ($company_id != false) {
                $ins_data = array(
                    'company_id' => $company_id,
                    'call_from' => $request->input('From'),
                    'call_to' => $request->input('To'),
                    'call_start_at' => new DateTime(),
                    'call_end_at' => new DateTime(),
                    'call_duration' => $request->input('RecordingDuration'),
                    'call_status' => $request->input('CallStatus'),
                    'call_sid' => $call_sid,
                    'call_direction' => $request->input('Direction'),
                    'caller_name' => $request->input('CallerName'),
                    'account_sid' => $request->input('AccountSid'),
                    'recording_url' => $request->input('RecordingUrl'),
                );

                $id_call = CallsHelper::StoreSingleTwilioCall($ins_data);
                ActivityHelper::createActivity($company_id, 'NEW_CALL', 'call_records', $id_call, null, null, null);
                /* Send Mail */
                $caller_contact = CallsHelper::isCallerContactExist($request->input('From'));

                $default_phone_country_code = default_phone_country_code();
                $company_information = CompanyHelper::getCompanyDetais($company_id);
                $email_message = NotificationHelper::getNotificationMethod(0, 'mail', 'RECEIVE_CALL_EMAIL');
                $email_subject = NotificationHelper::getNotificationSubject(0, 'mail', 'RECEIVE_CALL_EMAIL');
                $email_subject = str_replace("{{client_name}}", ucwords($company_information['name']), $email_subject);
                $client_phn = maskPhoneNumber(str_replace($default_phone_country_code, "", $request->input('From')));

                $call_timez = CallsHelper::getCallTime($company_id);
                $call_duration = CallsHelper::getCallTime($company_id);

                $contact_city = 'Unknown';
                $url_app = url('');
                $country_code = getenv('APP_PHONE_COUNTRY_CODE');
                $email_message = str_replace("{{phone_number}}", str_replace($country_code, '', $client_phn), $email_message);
                $email_message = str_replace("{{app_url}}", $url_app, $email_message);
                if ($caller_contact) {
                    $email_message = str_replace("{{contant_name}}", $caller_contact['first_name'] . ' ' . $caller_contact['last_name'], $email_message);

                    if ($caller_contact['city'] != null && empty($caller_contact['city'])) {
                        $contact_city = $caller_contact['city'];
                    }

                } else {
                    $email_message = str_replace("{{contant_name}}", "Unknown", $email_message);
                }
                $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
                $email_message = str_replace("{{contact_city}}", $contact_city, $email_message);
                $email_message = str_replace("{{call_time}}", $call_timez, $email_message);
                $email_message = str_replace("{{call_duration}}", secToMin($request->input('RecordingDuration')), $email_message);
                $email_message = str_replace("{{source}}", 'Website', $email_message);
                $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);
                $enable_sms_notification = CompanySettingsHelper::getSetting($company_id, 'new_call_notification_email');

                if ($email_message != false && $email_subject != false && $enable_sms_notification == 1) {
                    $message = nl2br($email_message);
                    $app_from_email = app_from_email();
                    $data['company_information'] = $company_information;
                    $data['company_information']['logo'] = 'img/mail_image_preview.png';
                    $data['content_data'] = $email_message;
                    $bcc_email = getenv('BCC_EMAIL');
                    CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
                    \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_message, $email_subject, $id_call, 'call', 'RECEIVE_CALL_EMAIL');
                }

                /* Send Mail */
            }
        }
        $content = '<Response>
                    <Say>Thank you.</Say>
                </Response>';
        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function postUpdateLeadStatus()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        if (isset($input['id']) && isset($input['lead_status'])) {
            $id = $input['id'];
            $status = $input['lead_status'];
            $statusup = CallsHelper::updateLeadStatus($id, $status, $company_id);
            if ($statusup) {
                return response()->success('success');
            }
            return response()->success('success');
        }
        return response()->error('Please enter Valid data.');
    }

    public function postUpdateNote()
    {
        $input = Input::get();
        if (isset($input['call_id']) && isset($input['note'])) {
            $call_id = $input['call_id'];
            $note = $input['note'];
            CallsHelper::updateCallNote($call_id, $note);
            return response()->success('success');
        }
        return response()->error('Please enter Valid data.');
    }
}
