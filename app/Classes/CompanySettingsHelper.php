<?php
namespace App\Classes;

use App\Company;
use App\CompanyNotification;
use App\CompanySetting;
use App\EmNewsletterList;
use App\ReviewSetting;
use App\User;
use DateTime;
use Mail;

class CompanySettingsHelper
{
    public static function getSetting($company_id, $name)
    {
        $data = CompanySetting::where('company_id', $company_id)
        ->where('name', $name)
        ->where('value', '!=', '')
        ->first();
        if (count($data) > 0) {
            return $data->value;
        } else {
            return false;
        }
    }

    public static function setSetting($company_id, $name, $value)
    {
        $update_array = ['company_id' => $company_id, 'name' => $name, 'value' => $value, 'created_at' => new DateTime];
        CompanySetting::insert($update_array);
        return true;
    }

    public static function deleteSetting($company_id, $name)
    {
        CompanySetting::where('company_id', $company_id)
        ->where('name', $name)
        ->delete();
        return true;
    }

    public static function SettingExists($company_id, $name)
    {
        $data = CompanySetting::where('company_id', $company_id)->where('name', $name)->first();
        if (count($data) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function updateSetting($company_id, $name, $value)
    {
        $update_array = ['value' => $value, 'updated_at' => new DateTime];
        $where_error = ['company_id' => $company_id, 'name' => $name];
        $status = CompanySetting::where($where_error)->update($update_array);
        if ($status == 0) {
            $cmp = new CompanySetting;
            $cmp->value = $value;
            $cmp->company_id = $company_id;
            $cmp->name = $name;
            $cmp->save();
        }
        return true;
    }

    public static function addSetting($company_id, $name, $value)
    {
        $add_array = ['name' => $name, 'company_id' => $company_id, 'value' => $value, 'created_at' => new DateTime];
        CompanySetting::insert($add_array);
        return true;
    }

    public static function getCompanyIdfromTwilioSid($twilioSid)
    {
        $settings_data = CompanySetting::select('company_id')
            ->where('name', 'twilio_sid')
            ->where('value', $twilioSid)
            ->first();
        if (count($settings_data) > 0) {
            return $settings_data->company_id;
        }
        return false;
    }

    public static function getCompanyIdfromValue($name, $value)
    {
        $settings_data = CompanySetting::select('company_id')
            ->where('name', $name)
            ->where('value', $value)
            ->first();
        if (count($settings_data) > 0) {
            return $settings_data->company_id;
        }
        return false;
    }

    public static function getCompanyReviewSettings($company_id)
    {
        $settings = ReviewSetting::where('company_id', $company_id)->first();
        if (count($settings) > 0) {
            return $settings;
        }
        return false;
    }

    public static function createNewCompanySettings($company_id)
    {
        $time = new \DateTime();
        $ins_data = array('company_id' => $company_id, 'created_at' => $time);
        ReviewSetting::insert($ins_data);
    }
    public static function createNotificationControls($company_id)
    {
        $ins_array = [];
        $notifications = ['new_review_notification_sms', 'new_sms_notification_sms', 'new_review_notification_email', 'new_call_notification_email', 'new_sms_notification_email', 'daily_performance_report'];

        foreach ($notifications as $notfctn) {
            $settings_exists = CompanySettingsHelper::SettingExists($company_id, $notfctn);
            if (!$settings_exists) {
                $ins_array[] = array('company_id' => $company_id,
                    'name' => $notfctn,
                    'value' => 1,
                    'created_at' => new DateTime,
                );
            }
        }
        if (count($ins_array) > 0) {
            CompanySetting::insert($ins_array);
        }
        return true;
    }

    public static function getNotificationControls($company_id)
    {
        CompanySettingsHelper::createNotificationControls($company_id);
        $settings_array = [];
        $notifications = ['new_review_notification_sms', 'new_sms_notification_sms', 'new_review_notification_email', 'new_call_notification_email', 'new_sms_notification_email', 'daily_performance_report', 'new_opertunity', 'new_opertunity_SMS', 'social_post_publish_email'];
        foreach ($notifications as $key => $notfctn) {
            $settings_exists = CompanySettingsHelper::getSetting($company_id, $notfctn);
            if ($settings_exists !== false) {
                $settings_array[$notfctn] = $settings_exists;
            } else {
                CompanySettingsHelper::addSetting($company_id, $notfctn, '');
                $settings_exists = CompanySettingsHelper::getSetting($company_id, $notfctn);
                $settings_array[$notfctn] = $settings_exists;
            }
        }

        return $settings_array;
    }

    public static function updatetNotificationControls($controls, $company_id)
    {
        foreach ($controls as $name => $value) {
            CompanySettingsHelper::updateSetting($company_id, $name, $value);
        }
    }
    public static function findCompanyByNames($names)
    {
        $company = Company::select('id')->whereIn('name', $names)
            ->where(['is_active' => 1])
            ->first();
        if (count($company) > 0) {
            return $company->id;
        }
        return false;
    }

    public static function findCompanyBySingleName($name)
    {
        $company = Company::select('id')
            ->where('name', 'like', '%' . $name . '%')
            ->where(['is_active' => 1])
            ->first();
        if (count($company) > 0) {
            return $company->id;
        }
        return false;
    }

    public static function findCompanyAdminId($company_id)
    {
        $user = User::select('id')->where('company_id', $company_id)->first();
        if (count($user) > 0) {
            return $user->id;
        }
        return false;
    }
    public static function getCompnayIdbyOption($key, $value)
    {
        echo "key: " . $key;
        echo " val: " . $value;

        $settingsId = CompanySetting::select('company_id')
            ->where(['name' => $key, 'value' => $value])
            ->first();
        if (count($settingsId) > 0) {
            return $settingsId->company_id;
        }
        return true;
    }
    public static function updatetNotifyUserEmails($data, $company_id)
    {
        $value = array();
        foreach ($data as $item) {
            $value[] = array('company_id' => $company_id, 'type' => 'email', 'user_id' => $item['id']);
        }
        CompanyNotification::where('company_id', $company_id)->where('type', 'email')->delete();
        CompanyNotification::insert($value);
    }

    public static function updatetNotifyUserPhones($data, $company_id)
    {
        $value = array();
        foreach ($data as $item) {
            $value[] = array('company_id' => $company_id, 'type' => 'phone', 'user_id' => $item['id']);
        }
        CompanyNotification::where('company_id', $company_id)->where('type', 'phone')->delete();
        CompanyNotification::insert($value);
    }

    public static function getNotifyUserEmails($company_id)
    {
        $objectArray = array();
        $data = CompanyNotification::with('users')->where('company_id', $company_id)->where('type', 'email')->get();
        if ($data) {
            $data = $data->toArray();
            foreach ($data as $item) {
                if ($item['users']['id']!=null && $item['users']['email']!=null) {
                    $objectArray[] = array('id' => $item['users']['id'], 'email' => $item['users']['email']);
                }
            }
        }
        return $objectArray;
    }
    public static function getNotifyUserPhones($company_id)
    {
        $objectArray = array();
        $data = CompanyNotification::with('users')->where('company_id', $company_id)->where('type', 'phone')->get();
        if ($data) {
            $data = $data->toArray();
            foreach ($data as $item) {
                $objectArray[] = array('id' => $item['users']['id'], 'phone' => $item['users']['phone']);
            }
        }
        return $objectArray;
    }

    public static function fetchNotifyUsersData($company_id, $type)
    {
        return CompanyNotification::with('users')->whereHas(
            'users',
            function ($q) {
                $q->where('status', '1');
            }
        )->where('type', $type)->where('company_id', $company_id)->get();
    }

    public static function fetchNotifyUsersContacts($company_id, $company_phone)
    {
        $company_notify_contacts = CompanyNotification::with('users')->whereHas(
            'users',
            function ($q) {
                $q->where('status', '1');
            }
        )->where('type', 'phone')->where(
            'company_id',
            $company_id
        )
            ->get();
        $contacts_array = array();
        if (count($company_notify_contacts) > 0) {
            $company_notify_contacts = $company_notify_contacts->toArray();
            if (!empty($company_notify_contacts)) {
                foreach ($company_notify_contacts as $num) {
                    if (!empty($num['users']['phone']) && $num['users']['phone'] != "") {
                        $contacts_array[] = $num['users']['phone_country_code'] . $num['users']['phone'];
                    }
                }
                if ($contacts_array) {
                    return $contacts_array;
                }
            }
        }

        $contacts_array[] = $company_phone;
        return $contacts_array;
    }

    public static function deleteNotifyByType($company_id, $type)
    {
        CompanyNotification::where('company_id', $company_id)->where('type', $type)->delete();
    }

    public static function fetchNotifyEmails($company_id, $type)
    {
        $emails_array = array();
        // Email if company is active
        if (CompanyHelper::is_company_active($company_id)) {
            $company_notify_emails = CompanyNotification::with('users')
                ->whereHas(
                    'users',
                    function ($q) {
                        $q->where('status', '1');
                    }
                )
                ->where('type', $type)
                ->where('company_id', $company_id)
                ->get();
            if ($company_notify_emails->count() > 0) {
                $company_notify_emails = $company_notify_emails->toArray();
                foreach ($company_notify_emails as $mail) {
                    $emails_array[] = $mail['users']['email'];
                }
            } else {
                $company_mail = Company::where('id', $company_id)->first()->toArray();
                $emails_array[] = $company_mail['email'];
            }
        }

        return $emails_array;
    }

    public static function sendCompanyEmailNotifcation($company_id, $email_content, $email_subject, $bcc_email, $template, $app_from_email, $email_type = null)
    {
        $data = $email_content;
        $company_notify_emails = self::fetchNotifyEmails($company_id, 'email');
        if ($email_type != null) {
        }
        if (!empty($company_notify_emails)) {
            foreach ($company_notify_emails as $email_admin) {
                Mail::send(
                    $template,
                    compact('data'),
                    function ($mail) use ($email_admin, $app_from_email, $email_subject, $bcc_email) {
                        $mail->to($email_admin)->from($app_from_email)->subject($email_subject);
                        if ($bcc_email != false) {
                            $mail->bcc($bcc_email, "EverGenius");
                        }
                    }
                );

                /* Email Logs to file */
                $mail_log = [
                    'subject' => $email_subject,
                    'message' => $data,
                    'email_to' => $email_admin,
                    'email_from' => $app_from_email,
                    'bcc_email' => $bcc_email,
                ];

                $logFile = 'emails_logs.log';
                \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                \Log::info("Email Sent", $mail_log);

                /* /Email Logs */
            }
        }
    }

    public static function sendSmsToCompanyNotifyUsers($company_id, $sms_message, $company_phone)
    {
        $enable_sms_notification = self::getSetting($company_id, 'new_sms_notification_sms');
        $enable_sms_notification_review = CompanySettingsHelper::getSetting($company_id, 'new_review_notification_sms');
        $enable_new_opertunity_SMS = CompanySettingsHelper::getSetting($company_id, 'new_opertunity_SMS');

        if ($enable_sms_notification == 1 || $enable_sms_notification_review == 1 || $enable_new_opertunity_SMS == 1) {
            $company_notify_contacts = self::fetchNotifyUsersContacts($company_id, $company_phone);
            if (!empty($company_notify_contacts)) {
                foreach ($company_notify_contacts as $num) {
                    SmsHelper::sendSms($num, $sms_message, $company_id, 'office_notification', '');
                }
            }
        }
    }
    public static function sendCompanySmsNotifcationLogs($company_id, $sms_message, $company_phone, $object_id, $type, $object_type)
    {
        $company_notify_contacts = self::fetchNotifyUsersContacts($company_id, $company_phone);
        if (!empty($company_notify_contacts)) {
            foreach ($company_notify_contacts as $num) {
                \App\Classes\CompanyHelper::recordNotificationLog($object_id, $type, $object_type, 'sms', $company_id, $num, $sms_message, null, null, null);
            }
        }
    }

    public static function addSubscriptionList($company_id)
    {
        $subscription_list = EmNewsletterList::Where('company_id', '0')->get();
        $data = array();
        if ($subscription_list->count() > 0) {
            $i = 0;
            foreach ($subscription_list as $value) {
                $data[$i]['name'] = $value['name'];
                $data[$i]['unique_id'] = uniqid();
                $data[$i]['company_id'] = $company_id;
                $i++;
            }
            if (count($data) > 0) {
                EmNewsletterList::insert($data);
            }
        }
    }
    public static function sendClientEmailNotifcation($company_id, $to_email, $email_content, $email_subject, $bcc_email, $template, $app_from_email, $email_type = null)
    {
        //$data = $email_content;
        Mail::send(
            $template,
            $email_content,
            function ($mail) use ($to_email, $app_from_email, $email_subject, $bcc_email) {
                $mail->to($to_email)->from($app_from_email)->subject($email_subject);
                if ($bcc_email != false) {
                    $mail->bcc($bcc_email, "EverGenius");
                }
            }
        );
    }
    public static function sendCompanyEmailNotifcationLogs($company_id, $email_content, $email_subject, $object_id, $type, $object_type)
    {
        $company_notify_emails = self::fetchNotifyEmails($company_id, 'email');
        if (!empty($company_notify_emails)) {
            foreach ($company_notify_emails as $email_admin) {
                \App\Classes\CompanyHelper::recordNotificationLog($object_id, $type, $object_type, 'mail', $company_id, $email_admin, $email_content, $email_subject, $contact_id = null, $appointment_id = null);
            }
        }
    }

    public static function isTwillioSetup($company_id)
    {
        $twilio_enable = CompanySettingsHelper::getSetting($company_id, 'twilio_enable');
        $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
        $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
        $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');
        if (!empty($twilio_number) && $twilio_enable == 1 && !empty($twilio_sid) && !empty($twilio_auth_id) && !empty($twilio_number)) {
            return true;
        }
        return false;
    }
}
