<?php

namespace App\Http\Controllers;

use App\Classes\CompanySettingsHelper;
use App\Classes\yextApiHelper;
use App\User;
use Auth;
use DateTime;

class ProfilelistngController extends Controller
{
    public function getIndex()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $yext_location_id = CompanySettingsHelper::getSetting($company_id, 'yext_location_id');
        $yex_api_key = getenv('YEXT_API_KEY');
        $yex_account_ID = getenv('YEXT_ACCOUNT_ID');
        $yex_api_url = getenv('YEXT_API_URL');

        /* Current Date */
        $date = new DateTime;
        $date = $date->format('Ymd');

        if (!empty($yext_id) || ($yex_api_key && $yex_account_ID && $yex_api_url)) {
            $listing = yextApiHelper::getProfileListingCurl($yex_api_url, $yex_api_key, $yex_account_ID, $yext_location_id, $date);

            return response()->success($listing);
        } else {
            return response()->error('Yext account details not Found');
        }
    }

    public function getPublisherList()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $yext_location_id = CompanySettingsHelper::getSetting($company_id, 'yext_location_id');
        $yex_api_key = getenv('YEXT_API_KEY');
        $yex_account_ID = getenv('YEXT_ACCOUNT_ID');
        $yex_api_url = getenv('YEXT_API_URL');

        /* Current Date */
        $date = new DateTime;
        $date = $date->format('Ymd');

        if (!empty($yext_id) || ($yex_api_key && $yex_account_ID && $yex_api_url)) {
            $listing = yextApiHelper::getPublisherListingCurl($yex_api_url, $yex_api_key, $yex_account_ID, $yext_location_id, $date);

            return response()->success($listing);
        } else {
            return response()->error('Yext account details not Found');
        }
    }
}
