<?php
namespace App\Classes;

use App\Classes\ActivityHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\NotificationHelper;
use App\Classes\SocketHelper;
use App\Contact;
use App\SmsRecord;
use Auth;
use Carbon\Carbon;
use DateTime;
use DB;
use Twilio\Rest\Client;

class SmsHelper
{
    public static function sendSms($number, $text_body, $company_id, $type, $contact_id = '', $broadcast = false)
    {
        $c_user_id = null;
        if (Auth::user()) {
            $user = Auth::user();
            $c_user_id = $user->id;
        }

        // Get Twilio infomation
        $twilio_enable = CompanySettingsHelper::getSetting($company_id, 'twilio_enable');
        $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
        $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
        $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');

        if (!empty($number) && $twilio_enable == 1 && !empty($twilio_sid) && !empty($twilio_auth_id) && !empty($twilio_number) && $twilio_number != $number) {
            // Step 3: Instantiate a new Twilio Rest Client
            $client = new Client($twilio_sid, $twilio_auth_id);

            try {
                $call_back_url = url('/') . "/sms_callback";
                // Twilio SMS Send

                if ($twilio_number == $number) {
                    return false;
                }

                $response = $client->messages->create(
                    $number,
                    array(
                        'from' => $twilio_number,
                        'body' => $text_body,
                    ),
                    array('statusCallback' => $call_back_url)
                );
                $sid = $response->sid;

                $direction = 'outbound-api';
                if ($contact_id != '') {
                    $ins_id = SmsHelper::insertSmsRecord($company_id, $sid, $twilio_number, $number, $text_body, $type, $contact_id, $direction);
                }
                //$ins_id = '';
                /* *********************** Add Activity *********************** */
                ActivityHelper::createActivity($company_id, 'SMS_SEND', 'sms_records', $ins_id, $contact_id, null, $c_user_id);
                /* *********************** / Add Activity *********************** */
                if ($ins_id) {
                    return $ins_id;
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                $sid = 0000;
                $direction = 'outbound-api';
                if ($contact_id != '' && $broadcast=false) {
                    $ins_id = SmsHelper::insertSmsRecord($company_id, $sid, $twilio_number, $number, $text_body, $type, $contact_id, $direction);
                    return $ins_id;
                }
                $data['content'] = "error";
                $logFile = 'twilio.log';
                \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                \Log::emergency("Company ID:" . $company_id . "-" . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    public static function insertSmsRecord($cmpny_id, $sid, $from, $to, $body, $type, $contact_id, $direction)
    {

        //If Contact ID not passed then search from Contacts Table
        if ($contact_id == '') {
            $contact_info = Contact::select('id')->where('mobile_number', '=', $to)->first();
            $contact_id = $contact_info->id;
        }

        $contact_info = Contact::select(array('first_name', 'last_name'))
            ->where('id', '=', $contact_id)
            ->first();
        $contact_name = ucfirst(($contact_info['first_name'] . ' ' . $contact_info['last_name']));

        $sms_record = new smsRecord;
        $sms_record->company_id = $cmpny_id;
        $sms_record->contact_id = $contact_id;
        $sms_record->receiver_name = $contact_name;
        $sms_record->sid = $sid;
        $sms_record->sms_from = $from;
        $sms_record->sms_to = $to;
        $sms_record->sms_body = $body;
        $sms_record->sent_time = new datetime();
        $sms_record->status = 'Sent';
        $sms_record->type = $type;
        $sms_record->direction = $direction;
        $sms_record->created_at = new datetime();
        $sms_record->save();
        return $sms_record->id;
    }

    public static function get_contact_sms($contact_id)
    {
        $sms = SmsRecord::where('contact_id', $contact_id)
            ->orderBy('id', 'desc')
            ->get();
        return $sms;
    }

    /* store Fetch SMS */
    public static function SaveIncomingSmsRecords($compnies, $starttimeAfter = false)
    {
        foreach ($compnies as $company) {
            $company_id = $company['id'];
            // Get Twilio infomation
            $twilio_enable = CompanySettingsHelper::getSetting($company_id, 'twilio_enable');
            $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
            $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
            $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');

            // Is twilio enable and all information is provided
            if ($twilio_enable == 1 && !empty($twilio_sid) && !empty($twilio_auth_id) && !empty($twilio_number)) {
                try {
                    $accountId = $twilio_sid;
                    $token = $twilio_auth_id;
                    $twilio = new Client($accountId, $token);

                    if ($starttimeAfter != false) {
                        $sms = $twilio->messages->read(
                            array(
                                "datesentAfter" => $starttimeAfter,
                            )
                        );
                    } else {
                        $sms = $twilio->messages->read();
                    }
                } catch (\Exception $e) {
                    $data['content'] = "error";
                    $logFile = 'twilio.log';
                    \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                    \Log::emergency("Company ID:" . $company_id . "-" . $e->getMessage());
                }
            }
        }
        return true;
    }

    public static function StoreTwilioSms($sms_records, $company_id)
    {
        $ins_data = array();
        if (count($sms_records) > 0 && !empty($sms_records)) {
            foreach ($sms_records as $key => $record) {
                if ($record->direction != 'outbound-api') {
                    $contact = SmsHelper::getContactSms($record->from, $record->to);
                    if ($contact != false) {
                        if ($record->direction == 'inbound') {
                            $sms_from = $record->to;
                            $sms_to = $record->from;
                        } else {
                            $sms_from = $record->from;
                            $sms_to = $record->to;
                        }

                        $ins_data = array(
                            'company_id' => $company_id,
                            'contact_id' => $contact['id'],
                            'receiver_name' => $contact['name'],
                            'sms_from' => $sms_from,
                            'sms_to' => $sms_to,
                            'sms_body' => $record->body,
                            'sent_time' => $record->dateSent,
                            'status' => $record->status,
                            'sid' => $record->sid,
                            'type' => 'fetch',
                            'direction' => $record->direction,
                            'created_at' => new DateTime(),
                        );
                    }
                    // If Record Not Exists Then Add New Record
                    if (!SmsHelper::isSmsRecordExists($record->sid) && $contact != false) {
                        $id = DB::table('sms_records')->insertGetId($ins_data);
                        /* *********************** Add Activity *********************** */
                        $contact_id = $contact['id'];
                        ActivityHelper::createActivity($company_id, 'SMS_RECEIVED', 'sms_records', $id, $contact_id, null, null);
                    /* *********************** / Add Activity *********************** */
                    } // Update Existing Record
                    else {
                        SmsRecord::where('sid', $record->sid)->update($ins_data);
                    }
                }
            }
            return true;
        }
    }

    public static function getContactSms($from_number, $to_number, $company_id = null)
    {
        $where = [];
        if ($company_id != null) {
            $d_s = array('company_id', '=', $company_id);
            array_push($where, $d_s);
        }

        $contact_info = Contact::where(
            function ($query) use ($to_number, $from_number) {
                $query->where('mobile_number', '=', $to_number)
                    ->orWhere('mobile_number', '=', $from_number);
            }
        )
            ->where($where)
            ->first();

        if (count($contact_info) > 0) {
            return array(
                'id' => $contact_info->id,
                'name' => ucwords($contact_info->first_name . " " . $contact_info->last_name),
                'company_id' => $contact_info->company_id,
            );
        } else {
            return false;
        }
    }

    public static function isSmsRecordExists($sid)
    {
        $record = SmsRecord::where('sid', $sid)
            ->count();
        if ($record > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function getPhoneRecivedNumber($id)
    {
        $record = SmsRecord::select('sms_from')->where('id', $id)->first();
        if (count($record > 0)) {
            return $record->sms_from;
        } else {
            return false;
        }
    }

    public static function getSmsStaticByDate($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));

        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $start_time = Carbon::createFromTimestamp(strtotime($start_time))
                ->timezone($tz)
                ->toDateTimeString();
            $end_time = Carbon::createFromTimestamp(strtotime($end_time))
                ->timezone($tz)
                ->toDateTimeString();
        }

        $out = array();

        /* *************  Get Data From Database ************* */
        $sms = SmsRecord::groupBy(DB::raw('date(sent_time)'), 'direction')
            ->select(DB::raw('count(id) as total'), DB::raw('date(sent_time) as date'), 'direction')
            ->whereBetween('sent_time', array($start_time, $end_time))
            ->where('company_id', '=', $company_id)
            ->get()
            ->toArray();
        $dates = getDatesFromRange($start_date, $end_date);
        foreach ($dates as $key => $date) {
            $inbound = 0;
            $outbond = 0;
            foreach ($sms as $smss) {
                if ($smss['date'] == $date) {
                    $total_calls = $smss['total'];
                    if ($smss['direction'] == 'inbound') {
                        $inbound = $total_calls;
                    } else {
                        $outbond = $total_calls;
                    }
                }
            }
            $out[] = array('date' => date('M d', strtotime($date)), 'inbound' => $inbound, 'outbond' => $outbond);
        }
        return $out;
    }

    public static function getSmsStaticByWeek($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));

        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $start_time = Carbon::createFromTimestamp(strtotime($start_time))
                ->timezone($tz)
                ->toDateTimeString();
            $end_time = Carbon::createFromTimestamp(strtotime($end_time))
                ->timezone($tz)
                ->toDateTimeString();
        }

        $out = array();

        /* *************  Get Data From Database ************* */
        $sms = SmsRecord::groupBy(DB::raw('week(sent_time)'), 'direction')
            ->select(DB::raw('count(id) as total'), DB::raw('week(sent_time) as week'), 'direction')
            ->whereBetween('sent_time', array($start_time, $end_time))
            ->where('company_id', '=', $company_id)
            ->get()
            ->toArray();
        $dates = getWeeksDaysBetweenDays($start_date, $end_date, "Y-m-d");
        foreach ($dates as $key => $date) {
            $total_calls = 0;
            $inbound = 0;
            $outbond = 0;
            $direction = '';

            $week_n = date("W", strtotime($date));
            $date_full = date("d M", strtotime($date));

            foreach ($sms as $key => $smss) {
                if ($smss['week'] == $week_n) {
                    $total_calls = $smss['total'];
                    if ($smss['direction'] == 'inbound') {
                        $inbound = $total_calls;
                    } else {
                        $outbond = $total_calls;
                    }
                }
            }

            $out[] = array('date' => $date_full, 'inbound' => $inbound, 'outbond' => $outbond);
        }
        return $out;
    }
    public static function getSmsStaticByMonth($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));

        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $start_time = Carbon::createFromTimestamp(strtotime($start_time))
                ->timezone($tz)
                ->toDateTimeString();
            $end_time = Carbon::createFromTimestamp(strtotime($end_time))
                ->timezone($tz)
                ->toDateTimeString();
        }

        $out = array();

        /* *************  Get Data From Database ************* */
        $sms = SmsRecord::groupBy(DB::raw('month(sent_time)'), 'direction')
            ->select(DB::raw('count(id) as total'), DB::raw('month(sent_time) as month'), 'direction')
            ->whereBetween('sent_time', array($start_time, $end_time))
            ->where('company_id', '=', $company_id)
            ->get()
            ->toArray();
        $dates = getMonthsDaysBetweenDays($start_date, $end_date, "Y-m-d");
        foreach ($dates as $key => $date) {
            $mnth = date("m", strtotime($date));
            $mnth_full = date("M", strtotime($date));
            $total_calls = 0;
            $inbound = 0;
            $outbond = 0;
            $direction = '';

            foreach ($sms as $key => $smss) {
                if ($smss['month'] == $mnth) {
                    $total_calls = $smss['total'];
                    if ($smss['direction'] == 'inbound') {
                        $inbound = $total_calls;
                    } else {
                        $outbond = $total_calls;
                    }
                }
            }

            $out[] = array('date' => $mnth_full, 'inbound' => $inbound, 'outbond' => $outbond);
        }
        return $out;
    }

    public static function getSmsSummaryByDate($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));

        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $start_time = Carbon::createFromTimestamp(strtotime($start_time))
                ->timezone($tz)
                ->toDateTimeString();
            $end_time = Carbon::createFromTimestamp(strtotime($end_time))
                ->timezone($tz)
                ->toDateTimeString();
        }
        $out = array();
        $total = 0;
        $inbound = 0;
        $outbound = 0;

        $sms = SmsRecord::groupBy('direction')
            ->select(DB::raw('count(*) as total'), 'direction')
            ->whereBetween('sent_time', array($start_time, $end_time))
            ->where('company_id', '=', $company_id)
            ->get()
            ->toArray();
        foreach ($sms as $value) {
            if ($value['direction'] == 'inbound') {
                $inbound = $inbound + $value['total'];
            } else {
                $outbound = $outbound + $value['total'];
            }
            $total = $total + $value['total'];
        }
        $out = array('total' => $total, 'inbound' => $inbound, 'outbond' => $outbound);
        return $out;
    }

    public static function storeIncmoingSms($record)
    {
        $cmpny_id = CompanySettingsHelper::getCompanyIdfromValue('twilio_number', $record['To']);

        $contact = SmsHelper::getContactSms($record['From'], '00', $cmpny_id);
        if ($contact === false) {
            $cmpny_id = CompanySettingsHelper::getCompnayIdbyOption('twilio_sid', $record['AccountSid']);
            /* If Company not exists */
            if ($cmpny_id === false) {
                return response()->error('Company not exists');
            }

            $first_name = $record['From'];
            $last_name = '';
            $phone = $record['From'];
            $email = '';
            ContactHelper::createContact($cmpny_id, $first_name, $last_name, $phone, $email);
            $contact = SmsHelper::getContactSms($record['From'], '00', $cmpny_id);
        }
        if ($contact != false) {
            $twilio_number = CompanySettingsHelper::getSetting($contact['company_id'], 'twilio_number');

            $ins_data = array(
                'company_id' => $contact['company_id'],
                'contact_id' => $contact['id'],
                'receiver_name' => $contact['name'],
                'sms_from' => $record['From'],
                'sms_to' => $twilio_number,
                'sms_body' => $record['Body'],
                'sent_time' => new DateTime(),
                'status' => $record['SmsStatus'],
                'sid' => $record['SmsMessageSid'],
                'type' => 'fetch',
                'not_seen' => 1,
                'direction' => 'inbound',
                'created_at' => new DateTime(),
            );
        }
        // If Record Not Exists Then Add New Record
        if (!SmsHelper::isSmsRecordExists($record['SmsMessageSid']) && $contact != false) {
            $default_phone_country_code = default_phone_country_code();
            $id = DB::table('sms_records')->insertGetId($ins_data);
            // /* *********************** Add Activity *********************** */
            $contact_id = $contact['id'];
            ActivityHelper::createActivity($contact['company_id'], 'SMS_RECEIVED', 'sms_records', $id, $contact_id, null, null);
            /* *********************** / Add Activity *********************** */

            /* For Fire event to socket */
            //SocketHelper::IncomingSmsNotify($ins_data, $contact_id);

            $company_id = $contact['company_id'];
            $company_information = CompanyHelper::getCompanyDetais($contact['company_id']);
            $company_email = $company_information['email'];
            $company_phone = $default_phone_country_code . $company_information['phone'];
            $email_message = NotificationHelper::getNotificationMethod(0, 'mail', 'RECEIVE_SMS_EMAIL');
            $email_subject = NotificationHelper::getNotificationSubject(0, 'mail', 'RECEIVE_SMS_EMAIL');
            $email_subject = str_replace("{{client_name}}", ucwords($company_information['name']), $email_subject);
            $email_subject = str_replace('{$client_name}', ucwords($company_information['name']), $email_subject);
            $client_phn = maskPhoneNumber(str_replace($default_phone_country_code, "", $record['From']));
            $url_app = url('');
            $country_code = getenv('APP_PHONE_COUNTRY_CODE');
            $email_message = str_replace("{{phone_number}}", str_replace($country_code, '', $client_phn), $email_message);
            $email_message = str_replace('{$phone_number}', str_replace($country_code, '', $client_phn), $email_message);
            $con_link = '<a href="' . url('/') . '#/contact/' . $contact_id . '">' . $contact['name'] . '</a>';
            $email_message = str_replace("{{contant_name}}", $con_link, $email_message);
            $email_message = str_replace("{{message}}", $record['Body'], $email_message);
            $email_message = str_replace("{{app_url}}", $url_app, $email_message);

            $email_message = str_replace('{$name}', $con_link, $email_message);
            $email_message = str_replace('{$message}', $record['Body'], $email_message);
            $email_message = str_replace('{$app_url}', $url_app, $email_message);

            $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
            $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);
            $email_message = str_replace('{$bob_signature}', $bob_s, $email_message);

            $enable_email_notification = CompanySettingsHelper::getSetting($contact['company_id'], 'new_sms_notification_email');
            if ($email_message != false && $email_subject != false && $enable_email_notification == 1) {
                $message = nl2br($email_message);
                $app_from_email = app_from_email();
                $data['company_information'] = $company_information;
                $data['company_information']['logo'] = '/img/mail_image_preview.png';
                $data['content_data'] = $email_message;
                $bcc_email = getenv('BCC_EMAIL');
                /**Send Email to admin on New Notification**/
                CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
                \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_message, $email_subject, $id, 'sms', 'RECEIVE_SMS_EMAIL');
            }

            $email_message = str_replace("{{contant_name}}", $con_link, $email_message);
            $email_message = str_replace("{{message}}", $record['Body'], $email_message);

            $email_message = str_replace("{{message}}", $record['Body'], $email_message);
            $email_message = str_replace("{{app_url}}", $url_app, $email_message);

            $email_message = str_replace('{$contant_name}', $con_link, $email_message);
            $email_message = str_replace('{$message}', $record['Body'], $email_message);

            $email_message = str_replace('{$message}', $record['Body'], $email_message);
            $email_message = str_replace('{$app_url}', $url_app, $email_message);

            $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
            $email_message = str_replace('{$bob_signature}', $bob_s, $email_message);

            $email_admin = $company_email;

            $enable_email_notification = CompanySettingsHelper::getSetting($contact['company_id'], 'new_sms_notification_email');

            if ($email_message != false && $email_subject != false && $enable_email_notification == 1) {
                $message = nl2br($email_message);
                $app_from_email = app_from_email();
                $data['company_information'] = $company_information;
                $data['company_information']['logo'] = '/img/mail_image_preview.png';
                $data['content_data'] = $email_message;
                $bcc_email = getenv('BCC_EMAIL');
            }
            $sms_message = NotificationHelper::getNotificationMethod(0, 'sms', 'RECEIVE_SMS_SMS');

            $sms_subject = NotificationHelper::getNotificationSubject(0, 'sms', 'RECEIVE_SMS_SMS');
            $sms_message = str_replace('{$phone_number}', $client_phn, $sms_message);
            $sms_message = str_replace('{$message}', $record['Body'], $sms_message);
            $sms_message = str_replace('{$app_url}', $url_app, $sms_message);
            $sms_message = str_replace('{{phone_number}}', $client_phn, $sms_message);
            $sms_message = str_replace('{{message}}', $record['Body'], $sms_message);
            $sms_message = str_replace('{{app_url}}', $url_app, $sms_message);

            $enable_sms_notification = CompanySettingsHelper::getSetting($contact['company_id'], 'new_sms_notification_sms');
            /**Notify admin via sms for new sms**/
            if ($enable_sms_notification == 1) {
                $twilio_number = CompanySettingsHelper::getSetting($contact['company_id'], 'twilio_number');
                if ($twilio_number!=false && $record['From'] != $twilio_number) {
                    CompanySettingsHelper::sendSmsToCompanyNotifyUsers($company_id, $sms_message, $company_phone);
                    \App\Classes\CompanySettingsHelper::sendCompanySmsNotifcationLogs($company_id, $sms_message, $company_phone, $id, 'sms', 'RECEIVE_SMS_SMS', $company_id);
                }
            }
        } // Update Existing Record
        else {
        }
        return true;
    }

    public static function getTopConversationsContacts($company_id)
    {
        $conversation = SmsRecord::
            select(['contacts.id as contact_id', 'sms_records.sent_time', 'contacts.mobile_number as phone_number', 'sms_records.created_at', 'sms_records.sms_body as last_sms', DB::raw('CONCAT_WS(" ",contacts.first_name,NULL,contacts.last_name) as contact_name'), 'not_seen_count'])
            ->join(
                DB::raw('(select max(created_at) maxtime,contact_id from sms_records group by contact_id) latest'),
                function ($join) {
                    $join->on('sms_records.created_at', '=', 'latest.maxtime')
                        ->on('sms_records.contact_id', '=', 'latest.contact_id');
                }
            )
            ->join(
                'contacts',
                function ($join2) {
                    $join2->on('sms_records.contact_id', '=', 'contacts.id');
                }
            )
            ->leftJoin(
                DB::raw('(SELECT contact_id,COUNT(not_seen) as not_seen_count FROM
                    sms_records where not_seen is not null GROUP BY contact_id) seen'),
                function ($join3) {
                    $join3->on('contacts.id', '=', 'seen.contact_id');
                }
            )
            ->where('sms_records.company_id', $company_id)
            ->whereNull('contacts.deleted_at')
            ->groupBy('sms_records.contact_id')
            ->orderBy('sms_records.created_at', 'desc')
            ->get();
        if (count($conversation) > 0) {
            return $conversation;
        }
        return [];
    }

    public static function getSearchConversationsContacts($company_id, $q)
    {
        $conversation = Contact::select(
            [
                'contacts.id as contact_id',
                'contacts.mobile_number as phone_number',
                'sms_records.sent_time', 'sms_records.sms_body as last_sms',
                DB::raw('CONCAT(contacts.first_name," ",contacts.last_name) as contact_name'),
                'not_seen_count',
            ]
        )
            ->leftJoin(
                DB::raw('(select max(created_at) maxtime,contact_id from sms_records group by contact_id) latest'),
                function ($join) {
                    $join->on('contacts.id', '=', 'latest.contact_id');
                }
            )
            ->leftJoin(
                'sms_records',
                function ($join2) {
                    $join2->on('contacts.id', '=', 'latest.contact_id')
                        ->on('sms_records.created_at', '=', 'latest.maxtime');
                }
            )
            ->leftJoin(
                DB::raw(
                    '(SELECT contact_id,COUNT(not_seen) as not_seen_count FROM
                    sms_records where not_seen is not null GROUP BY contact_id) seen'
                ),
                function ($join3) {
                    $join3->on('contacts.id', '=', 'seen.contact_id');
                }
            )
            ->where(
                function ($query) use ($q) {
                    $query->where('contacts.first_name', 'like', "%$q%")
                        ->orwhere('contacts.last_name', 'like', "%$q%")
                        ->orwhere(DB::raw("concat_ws(' ',first_name,last_name)"), 'like', "%$q%")
                        ->orwhere('contacts.mobile_number', 'like', "%$q%");
                }
            )
            ->whereNull('contacts.deleted_at')
            ->where('contacts.company_id', $company_id)
            ->groupBy('contacts.id')
            ->orderBy('sms_records.created_at', 'desc')
            ->get();
        if (count($conversation) > 0) {
            return $conversation;
        }
        return [];
    }

    public static function get_contact_sms_conversation($contact_id, $company_id)
    {
        $sms = SmsRecord::where(
            ['contact_id' => $contact_id,
                'company_id' => $company_id]
        )
            ->orderBy('id', 'desc')
            ->get();

        if (count($sms) > 0) {
            SmsHelper::updateSeenStatus($contact_id, $company_id);
            return $sms;
        }
        return [];
    }

    public static function updateSeenStatus($contact_id, $company_id)
    {
        SmsRecord::where(
            ['contact_id' => $contact_id,
                'company_id' => $company_id]
        )->update(['not_seen' => null]);
        return true;
    }

    public static function getCompanyTimeNow($company_id)
    {
        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $timeNow = Carbon::createFromTimestamp(time())
                ->timezone($tz)
                ->toDateTimeString();
            return $timeNow;
        }
        return date('Y-m-d H:i:s', time());
    }
}
