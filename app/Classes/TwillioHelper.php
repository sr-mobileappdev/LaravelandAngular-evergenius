<?php
namespace App\Classes;

use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\ReviewHelper;
use App\Review;
use Curl;
use DateTime;
use DB;
use Twilio\Rest\Client;
use Twilio\Twiml;

class TwillioHelper
{
    public static function createSubAccountGetPhone($area_code, $company_name, $company_id)
    {
        $twilio_account_sid = trim(getenv('TWILLIO_ACCOUNT_SID'));
        $twilio_auth_token = trim(getenv('TWILLIO_AUTH_TOKEN'));
        if ($twilio_account_sid!=false && $twilio_auth_token!=false) {
            self::createSubAccount($company_id, $company_name, $twilio_account_sid, $twilio_auth_token);
            $number = self::getTwilioNumber($company_id, $area_code);
            return $number;
        }
    }

    public static function createSubAccount($company_id, $company_name, $twilio_account_sid, $twilio_auth_token)
    {
        $client = new Client($twilio_account_sid, $twilio_auth_token);
        $account = $client->api->v2010->accounts->create(array("friendlyName" => $company_name));
        $account_sid = $account->sid;
        $account_auth_token = $account->authToken;
        /* Twilio SID  Update to Company*/
        $account_sid_exists = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
        if ($account_sid_exists===false) {
            CompanySettingsHelper::setSetting($company_id, 'twilio_sid', $account_sid);
        } else {
            CompanySettingsHelper::updateSetting($company_id, 'twilio_sid', $account_sid);
        }
        /* Twilio twilio_auth_id  Update to Company*/
        $account_auth_token_exists = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
        if ($account_auth_token_exists===false) {
            CompanySettingsHelper::setSetting($company_id, 'twilio_auth_id', $account_auth_token);
        } else {
            CompanySettingsHelper::updateSetting($company_id, 'twilio_auth_id', $account_auth_token);
        }
        CompanySettingsHelper::setSetting($company_id, 'twilio_enable', 1);
        return compact('account_sid', 'account_auth_token');
    }



    public static function getTwilioNumber($company_id, $area_code)
    {
        $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
        $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
        $client = new Client($twilio_sid, $twilio_auth_id);
        $numbers = $client->availablePhoneNumbers('US')->local->read(
            array("areaCode" => $area_code)
        );
        if (count($numbers)<1) {
            $numbers = $client->availablePhoneNumbers('US')->local->read();
        }
       
        // Purchase the first number on the list.
        $number = $client->incomingPhoneNumbers
            ->create(
                array(
                    "phoneNumber" => $numbers[0]->phoneNumber
                )
            );
        $twilio_number = $number->phoneNumber;
        $phone_sid = $number->sid;
        $phone_fiendly_num = $number->friendlyName;
        $twilio_number_exists = CompanySettingsHelper::getSetting($company_id, 'twilio_number');
        if ($twilio_number_exists===false) {
            CompanySettingsHelper::setSetting($company_id, 'twilio_number', $twilio_number);
        } else {
            CompanySettingsHelper::updateSetting($company_id, 'twilio_number', $twilio_number);
        }
        self::configureNumer($company_id, $phone_sid);
        return $phone_fiendly_num;
    }


    public static function configureNumer($company_id, $phoneSid)
    {
        $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
        $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
        $client = new Client($twilio_sid, $twilio_auth_id);
        $sms_url = url('/').'/sms/incoming/call-back';
        $company_info = CompanyHelper::getCompanyDetais($company_id);
        $call_url = url('/').'/twiml/incoming-call/'.$company_info['api_key'];
        $incoming_phone_number = $client->incomingPhoneNumbers($phoneSid)
        ->update(
            array(
                    "smsUrl" => $sms_url,
                    "voiceMethod" => "GET",
                    "voiceUrl" => $call_url
                )
        );
        if ($incoming_phone_number) {
            return true;
        }
        return false;
    }

    public static function twilioforwaringStatusChange($company_id, $forward_to, $recoring_status)
    {
        $twillio_forwaring_to_exists = CompanySettingsHelper::getSetting($company_id, 'twillio_forwaring_to');
        if ($twillio_forwaring_to_exists===false) {
            CompanySettingsHelper::setSetting($company_id, 'twillio_forwaring_to', $forward_to);
        } else {
            CompanySettingsHelper::updateSetting($company_id, 'twillio_forwaring_to', $forward_to);
        }

        $recoring_status_exists = CompanySettingsHelper::getSetting($company_id, 'twillio_recording_status');
        if ($recoring_status_exists===false) {
            CompanySettingsHelper::setSetting($company_id, 'twillio_recording_status', $recoring_status);
        } else {
            CompanySettingsHelper::updateSetting($company_id, 'twillio_recording_status', $recoring_status);
        }
        return true;
    }

    public static function getTwimlCallHandle($company_id)
    {
        $recoring_status = CompanySettingsHelper::getSetting($company_id, 'twillio_recording_status');
        $twillio_forwaring_to = CompanySettingsHelper::getSetting($company_id, 'twillio_forwaring_to');
        $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
        $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
        $action_url = url('/').'/call/incoming/call-back';
        $response = new Twiml();
        if ($twilio_sid!==false && $twilio_auth_id!==false && $twillio_forwaring_to!==false) {
            $dial = $response
            ->dial(['timeout'=>60,'record'=>true,
            'action'=>$action_url,
            "method"=>'GET'
            ]);
            $dial->number($twillio_forwaring_to);
            return $response;
        }
        $response
        ->say(
            'Thank you for calling',
            ['voice' => 'woman']
        );
        return $response;
    }
}
