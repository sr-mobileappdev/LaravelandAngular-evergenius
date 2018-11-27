<?php
namespace App\Classes;

use Auth;
use Curl;

class MailchimpApiHelper
{
    public static function getReports($api_key, $date_start = '', $date_end = '')
    {
        $user = Auth::user();
        $index_dc = strpos($api_key, "-us") + 3;
        $mailchimp_dc = "us" . substr($api_key, $index_dc);

        if ($mailchimp_dc == false) {
            $mailchimp_dc = 'us7';
        }

        $url = 'https://' . $mailchimp_dc . '.api.mailchimp.com/3.0/reports';
        $authrization = 'apikey ' . $api_key;
        $withData = array();
        if ($date_start != '' && $date_end != '') {
            $withData = array('since_send_time' => $date_start, 'before_send_time' => $date_end);
        }
        $response = Curl::to($url)
            ->withHeader('Authorization:' . $authrization)
            ->withData($withData)
            ->asJson()
            ->get();
        return $response;
    }
}
