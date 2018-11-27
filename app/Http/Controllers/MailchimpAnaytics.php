<?php

namespace App\Http\Controllers;

use App\Classes\CompanySettingsHelper;
use App\Classes\MailchimpApiHelper;
use App\User;
use Auth;
use DateTime;
use Input;

class MailchimpAnaytics extends Controller
{
    public function getIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $mailchimp_api_key = CompanySettingsHelper::getSetting($company_id, 'mailchimp_api_key');
        $start_date = '';
        $end_date = '';
        if (Input::get('start_date') && Input::get('end_date')) {
            $start_date = date('Y-m-d', strtotime(Input::get('start_date')));
            $end_date = date('Y-m-d', strtotime(Input::get('end_date') . ' +1 day')); // Increment one day.
        }
        $date = new DateTime;
        $date = $date->format('Ymd');
        if (!empty($mailchimp_api_key)) {
            $listing = MailchimpApiHelper::getReports($mailchimp_api_key, $start_date, $end_date);
            return response()->success($listing);
        } else {
            return response()->error('Yext account details not Found');
        }
    }
}
