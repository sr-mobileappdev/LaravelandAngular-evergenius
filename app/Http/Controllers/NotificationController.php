<?php

namespace App\Http\Controllers;

use App\Classes\CompanyHelper;
use App\Classes\CronHelper;
use App\Classes\NotificationHelper;
use App\SmsRecord;
use DateTime;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public static function send_notification()
    {
        $date_time = new dateTime();
        $current_time = $date_time->format('H:i');
        $cron_id = CronHelper::createCronRecord('Reminder');
        $compnies = CompanyHelper::getAllCompanies();

        foreach ($compnies as $compny) {
            $compny_id = $compny['id'];
            $now_notifcations = NotificationHelper::getNotificationTime($current_time, $compny_id);
            NotificationHelper::SmsAppointementNotications($now_notifcations, $compny_id); // For SMS
            NotificationHelper::sendOneHourBeforeReminder($compny_id); // SMS One hour before appointment
            NotificationHelper::MailAppointementNotications($now_notifcations, $compny_id); // For Emails
        }
        echo 'send reminder';
        CronHelper::udateCronEndTime($cron_id);
    }
    public function updateSmsStatus(Request $request)
    {
        $date_time = new dateTime();
        $current_time = $date_time->format('H:i');
        $sid = $request['MessageSid'];
        $status = $request['MessageStatus'];
        $update = SmsRecord::where('sid', $sid)
            ->update(array('status' => $status));
        return true;
    }
}
