<?php

namespace App\Classes;

use App\Appointment;
use App\Classes\ActivityHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\SmsHelper;
use App\Company;
use App\NotificationSetting;
use Carbon\Carbon;
use DateTime;
use Mail;

class NotificationHelper
{
    public static function getNotificationTime($time, $company_id)
    {
        $time = date('Y-m-d ' . $time, time());
        /* Get TimeZone Time */
        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $time = Carbon::createFromTimestamp(strtotime($time))
                ->timezone($tz)
                ->toDateTimeString();
        }
        $time = date('H:i', strtotime($time));
        $notications_now = NotificationSetting::where('time', $time)
            ->where('company_id', $company_id)
            ->where('status', 1)
            ->get();
        if (count($notications_now) > 0) {
            return $notications_now->toArray();
        }
        return [];
    }

    public static function sendOneHourBeforeReminder($companyId)
    {
        $hours = +1;
        $logType = 'one_hour_reminder_appointment';
        $hourAppointmets = self::getReminderAppointments($hours, $companyId, $logType); // 1 Hour Reminder appointments
        self::SendReminder($hourAppointmets, $companyId, 'ONE_HOUR_APPOINTMENT_REMINDER', $logType, true, true);
    }

    public static function SmsAppointementNotications($now_notifcations, $companyId)
    {
        foreach ($now_notifcations as $notifctn) {
            if ($notifctn['type'] == 'sms') {
                $logTye = 'reminder_before_1day_9am';
                $schedule_day = $notifctn['schedule'];
                $today_date = new DateTime();

                $timeZone = CompanySettingsHelper::getSetting($companyId, 'timezone');
                /* If Timezone Set */
                if ($timeZone != '' && $timeZone != false) {
                    $today_date = Carbon::createFromTimestamp(time())
                        ->timezone($timeZone)
                        ->modify($schedule_day . ' day');
                } else {
                    $today_date->modify($schedule_day . ' day');
                }
                $start_day = $today_date->format('Y-m-d 00:00:00');
                $end_day = $today_date->format('Y-m-d 23:59:59');
                //Get Appointent in day
                $appnts = Appointment::with('contacts')
                    ->whereBetween('start_datetime', array($start_day, $end_day))
                    ->where('available_status', 0)
                    ->where('appointment_status_id', 1)
                    ->where('company_id', $companyId)
                    ->whereNotExists(function ($query) use ($logTye) {
                        $query->from('notification_logs')
                            ->whereRaw('notification_logs.appointment_id = appointments.id')
                            ->where('type', $logTye);
                    })
                    ->get();
                if (count($appnts) > 0) {
                    NotificationHelper::sendSmstoApointments($appnts->toArray(), $notifctn, $logTye);
                }
                return true;
            }
        }
    }

    public static function SendReminder($appointments, $company_id, $type, $logType, $mail = false, $sms = false)
    {
        $email_message = NotificationHelper::getNotificationMethod($company_id, 'mail', $type);
        $email_subject = NotificationHelper::getNotificationSubject($company_id, 'mail', $type);
        if (count($appointments) > 0 && $email_message != false && $email_subject != false && $mail) {
            foreach ($appointments as $appointment) {
                $mail_message = self::replaceShortCodes($email_message, $appointment);
                $mail_subject = self::replaceShortCodes($email_subject, $appointment);
                if (empty($appointment['contacts']['email']) == false && empty($appointment['contacts']['first_name']) == false) {
                    self::sendEmailClient($appointment['company_id'], $mail_message, $mail_subject, $appointment['contacts']['email'], $logType);
                    \App\Classes\LeadHelper::Lead_email_notification_log(null, $logType, $mail_message, $mail_subject, $appointment['contacts']['id'], $appointment['id'], $appointment['id'], 'appointment', $appointment['contacts']['email'], 'mail', $company_id);
                }
            }
        }
        $sms_message = NotificationHelper::getNotificationMethod($company_id, 'sms', $type);
		if(!empty($sms_message)){
			foreach ($appointments as $appointment) {
				$sms_message = self::replaceShortCodes($sms_message, $appointment);
				$sms_body = strip_tags($sms_message);
				//If phone number is not empty
				if (empty($appointment['contacts']['mobile_number']) == false && empty($appointment['contacts']['first_name']) == false) {
					$sms_id = SmsHelper::sendSms($appointment['contacts']['mobile_number'], $sms_body, $company_id, $type, $appointment['contacts']['id']);
					/************************ Add Activity *********************** */
					ActivityHelper::createActivity($company_id, 'SMS_REMINDER_SEND', 'sms_records', $sms_id, $appointment['contacts']['id'], null, null);
					/************************ / Add Activity *********************** */
				}

				\App\Classes\LeadHelper::Lead_email_notification_log(null, $logType, $sms_body, null, $appointment['contacts']['id'], $appointment['id'], $appointment['id'], 'appointment', $appointment['contacts']['mobile_number'], 'sms', $company_id);
			}
		}
    }

    public static function getReminderAppointments($hours, $companyId, $logTye)
    {
        $out = [];

        $timeUtc = date('Y-m-d H:i:s', strtotime($hours . ' hour'));
        $timeCompanyStart = CompanyHelper::convertCompanyTime($timeUtc, $companyId, 'Y-m-d H:i:s');
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $timeCompanyStart);
        $datetime->modify('+5 minutes');
        $timeCompanyEnd = $datetime->format('Y-m-d H:i:s');
        $appointments = Appointment::with('contacts')
            ->select('appointments.*')
            ->where('company_id', $companyId)
            ->where('available_status', 0)
            ->where('appointment_status_id', 1)
            ->whereBetween('start_datetime', array($timeCompanyStart, $timeCompanyEnd))
            ->whereNotExists(function ($query) use ($logTye) {
                $query->from('notification_logs')
                    ->whereRaw('notification_logs.appointment_id = appointments.id')
                    ->where('type', $logTye);
            })->get();

        if (count($appointments) > 0) {
            $out = $appointments->toArray();
        }

        return $out;
    }

    public static function replaceShortCodes($message_body, $appointment)
    {
        $company_info = Company::where('id', $appointment['company_id'])->first();
        $comapny_name = $company_info->name;
        $website_url = $company_info->site_url;
        $contact_fname = ucfirst($appointment['contacts']['first_name']);
        $contact_lname = ucfirst($appointment['contacts']['last_name']);
        $gender = $appointment['contacts']['gender'];
        $contact_id = $appointment['contacts']['id'];
        $appointment_id = $appointment['id'];

        $appointment_time = date('h:i A', strtotime($appointment['start_datetime']));

        if ($gender == 'Male') {
            $prefix = 'Mr.';
        } else {
            $prefix = 'Miss ';
        }

        $message_body = str_replace('{$service_provider}', $comapny_name, $message_body);
        $message_body = str_replace('{$prefix}', $prefix, $message_body);
        $message_body = str_replace('{$first_name}', $contact_fname, $message_body);
        $message_body = str_replace('{$last_name}', $contact_lname, $message_body);
        $message_body = str_replace('{$appointment_reference}', $appointment_id, $message_body);
        $message_body = str_replace('{$time}', $appointment_time, $message_body);
        //$message_body=str_replace("{$service_provider}",$appointment_time,$message_body);
        $message_body = str_replace('{$service_provider}', $prefix, $message_body);
        $message_body = str_replace('{$website_url}', $website_url, $message_body);
        $message_body = str_replace('{$service_provider}', $comapny_name, $message_body);
        $message_body = str_replace('{$customer_firstname}', $contact_fname, $message_body);
        $message_body = str_replace('{$customer_lastname}', $contact_lname, $message_body);
        $message_body = str_replace('{$appointment_reference}', $appointment_id, $message_body);
        $message_body = str_replace('{$time}', $appointment_time, $message_body);
        $message_body = str_replace('{$customer_prefix}', $prefix, $message_body);
        return $message_body;
    }

    public static function sendSmstoApointments($appointments, $notifctn, $logType)
    {
        foreach ($appointments as $appointment) {
            $phone_number = $appointment['contacts']['mobile_number'];
            //$contry_code = $appointment['contacts']['mobile_number'];
            $sms_body = $notifctn['message'];
            $type = 'Appointment Reminder';
            $company_id = $appointment['company_id'];

            $company_info = Company::where('id', $company_id)->first();
            if (!count($company_info) > 0) {
                continue;
            }
            //Replace Content of SMS with Appointment Content
            $contact_id = $appointment['contacts']['id'];
            $appointment_id = $appointment['id'];
            $sms_body = self::replaceShortCodes($sms_body, $appointment);
            $sms_body = strip_tags($sms_body);
            //If phone number is not empty
            if (!empty($phone_number)) {
                $sms_id = SmsHelper::sendSms($phone_number, $sms_body, $company_id, $type, $contact_id);

                /* *********************** Add Activity *********************** */
                ActivityHelper::createActivity($company_id, 'SMS_REMINDER_SEND', 'sms_records', $sms_id, $contact_id, null, null);
                /* *********************** / Add Activity *********************** */
                \App\Classes\LeadHelper::Lead_email_notification_log(null, $logType, $sms_body, 'SMS_reminder_before', $contact_id, $appointment_id, $appointment_id, 'appointment', $phone_number, 'sms');
            }
        }
    }

    public static function getcompanyNotifications($company_id)
    {
        $notifcations = NotificationSetting::where('company_id', $company_id)
            ->where('type_key', '!=', 'REVIEW_EMAILS')
            ->get();
        return $notifcations;
    }

    public static function updateNotifications($notifcations)
    {
        foreach ($notifcations as $key => $notification) {
            $notification_id = $notification['id'];
            NotificationSetting::where('id', $notification_id)->update($notification);
        }
    }

    /*Send APOINTMENT REMINDER AT 8 AM*/
    public static function MailAppointementNotications($now_notifcations, $company_id)
    {
        $appointments = array();
        foreach ($now_notifcations as $notifctn) {
            if ($notifctn['type'] == 'mail' && $notifctn['type_key'] == 'APPOINTMENT_REMINDER_8AM') {
                $schedule_day = $notifctn['schedule'];
                $today_date = new DateTime();
                $today_date->modify($schedule_day . ' day');
                $start_day = $today_date->format('Y-m-d 00:00:00');
                $end_day = $today_date->format('Y-m-d 23:59:59');
                $logTye = $notifctn['type_key'];
                // Get Appointent in day
                $appnts = Appointment::with('contacts')
                    ->whereBetween('start_datetime', array($start_day, $end_day))
                    ->where('available_status', 0)
                    ->where('company_id', $company_id)
                    ->where('appointment_status_id', 1)
                    ->whereNotExists(function ($query) use ($logTye) {
                        $query->from('notification_logs')
                            ->whereRaw('notification_logs.appointment_id = appointments.id')
                            ->where('type', $logTye);
                    })
                    ->get();
                if (count($appnts) > 0) {
                    $appnts = $appnts->toArray();
                    NotificationHelper::sendMailtoApointments($appnts, $notifctn);
                }
                return true;
            }
        }
    }

    public static function sendMailtoApointments($appointments, $notifctn)
    {
        foreach ($appointments as $key => $appointment) {
            $mail_message = self::replaceShortCodes($notifctn['message'], $appointment);
            $mail_subject = self::replaceShortCodes($notifctn['email_subject'], $appointment);
            $logType = $notifctn['type_key'];
            if (empty($appointment['contacts']['email']) == false && empty($appointment['contacts']['first_name']) == false) {
                self::sendEmailClient($appointment['company_id'], $mail_message, $mail_subject, $appointment['contacts']['email'], $logType);
                \App\Classes\LeadHelper::Lead_email_notification_log(null, $logType, $mail_message, $mail_subject, $appointment['contacts']['id'], $appointment['id'], $appointment['id'], 'appointment', $appointment['contacts']['email'], 'mail');
                $is_added_log = 1;
            }
        }
    }

    public static function sendEmailClient($company_id, $mail_body, $mail_subject, $toEmail, $logType)
    {
        $companyInfo = Company::find($company_id)->toArray();
        $data['mail_body'] = $mail_body;
        $data['company_information'] = $companyInfo;
        $data['content_data'] = $mail_body;
        $app_from_email = app_from_email();
        $email_id = $toEmail;
        if (!empty($email_id)) {
            Mail::send('emails.social_post_publish', compact('data'), function ($mail) use ($email_id, $app_from_email, $mail_subject) {
                $mail->to($email_id)
                    ->from($app_from_email)
                    ->subject($mail_subject);
            });
            return true;
        }
    }

    public static function getNotificationMethod($company_id, $type, $type_key)
    {
        $mail_content = NotificationSetting::select('message')
            ->where(
                ['company_id' => $company_id,
                    'type_key' => $type_key,
                    'type' => $type,
                    'status' => 1,
                ]
            )->first();

        if (count($mail_content) > 0) {
            return $mail_content->message;
        }
        return false;
    }

    public static function getNotificationSubject($company_id, $type, $type_key)
    {
        $mail_content = NotificationSetting::select('email_subject')
            ->where(
                ['company_id' => $company_id,
                    'type_key' => $type_key,
                    'type' => $type,
                    'status' => 1,
                ]
            )->first();
        if ($mail_content) {
            $mail_content = $mail_content->toArray();
            if (count($mail_content) > 0) {
                return $mail_content['email_subject'];
            }
        } /* If email not found then find from company 0 */
        else {
            $mail_content = NotificationSetting::select('email_subject')
                ->where(
                    ['company_id' => 0,
                        'type_key' => $type_key,
                        'type' => $type,
                        'status' => 1,
                    ]
                )->first();
            if ($mail_content) {
                $mail_content = $mail_content->toArray();
                if (count($mail_content) > 0) {
                    return $mail_content['email_subject'];
                }
            }
            return false;
        }
        return false;
    }

    public static function getCompanyReviesEmails($company_id)
    {
        $mails = NotificationSetting::select('title', 'id', 'status')->where(
            [
                'company_id' => $company_id,
                'type_key' => 'REVIEW_EMAILS',
            ]
        )->get()->toArray();
        return $mails;
    }

    public static function createCompanyReviesEmails($company_id)
    {
        $mails = NotificationSetting::where(
            [
                'company_id' => 0,
                'type_key' => 'REVIEW_EMAILS',
            ]
        )
            ->get()
            ->toArray();
        if (count($mails) > 0) {
            $ins_array = [];
            foreach ($mails as $value) {
                unset($value['id']);
                $value['company_id'] = $company_id;
                $ins_array[] = $value;
            }
            NotificationSetting::insert($ins_array);
        }
        return true;
    }

    public static function getNotificationEmail($id)
    {
        $mail = NotificationSetting::where('id', $id)->first();
        if (count($mail) > 0) {
            return $mail;
        }
        return false;
    }

    public static function updateNotificationEmail($id, $data)
    {
        $mail_update = NotificationSetting::where('id', $id)->update($data);
    }

    public static function updateNotificationEmailStatus($id, $status)
    {
            NotificationSetting::where('id', $id)->update(['status' => $status]);
    }

    public static function getNotification($notificationId)
    {
        $notification = NotificationSetting::
        select(['id', 'title', 'message', 'email_subject', 'status'])
            ->where('id', $notificationId)->first();
        if (count($notification) > 0) {
            return $notification->toArray();
        }
        return false;
    }

    public static function sendEmailNotificationUser($email_to, $data, $subject, $from_email_id = null)
    {
        if ($from_email_id == null) {
            $admin_email_id = app_from_email();
        }
        $admin_email_id = $from_email_id;
        $bcc_email = getenv('BCC_EMAIL');

        Mail::send(
            'emails.add_new_user',
            compact('data'),
            function ($mail) use ($admin_email_id, $email_to, $subject, $bcc_email) {
                $mail->to(trim(strtolower($email_to)))
                ->from($admin_email_id)
                ->subject($subject);

                if ($bcc_email != false) {
                    $mail->bcc($bcc_email, "EverGenius:".$subject);
                }
            }
        );
    }
}
