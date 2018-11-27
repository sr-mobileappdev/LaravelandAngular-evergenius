<?php

namespace App\Http\Controllers;

use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\LeadHelper;
use App\Classes\SmsHelper;
use Auth;
use Input;

class ConversationController extends Controller
{
    public function getConversationContacts()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        if (isset($input['q']) && $input['q'] != '') {
            $query = $input['q'];
            $conversations = SmsHelper::getSearchConversationsContacts($company_id, $query);
        } else {
            $conversations = SmsHelper::getTopConversationsContacts($company_id);
        }
        return response()->success(compact('conversations'));
    }

    public function getContactConversation()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        if (isset($input['contact_id']) && $input['contact_id'] != '') {
            $contact_id = $input['contact_id'];
            $contact_conversations = SmsHelper::get_contact_sms_conversation($contact_id, $company_id);
            return response()->success(compact('contact_conversations'));
        }
        return response()->error('contact id missing.');
    }

    public function postSendMessage()
    {
        $input_data = Input::all();

        $contact_id = $input_data['contact_id'];
        $sms_body = $input_data['sms_body'];
        $contact_info = ContactHelper::getContactInfo($contact_id);
        $sms_to = $contact_info->mobile_number;
        $user = Auth::user();
        $company_id = $user->company_id;
        $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');

        /* Update lead Action if lead exists */
        $lead_info = LeadHelper::getLeadByContact($contact_id, $company_id);
        if ($lead_info != false && $lead_info['action_taken'] != 1) {
            LeadHelper::updateActionTaken($lead_info['id']);
        }

        //Contact Mobile number
        $sent_sms = SmsHelper::sendSms($sms_to, $sms_body, $company_id, 'conversation', $contact_id);
        $sent_sms = [
            "id" => $sent_sms,
            "company_id" => $company_id,
            "contact_id" => $contact_id,
            "receiver_name" => $contact_info->first_name . " " . $contact_info->last_name,
            "sid" => $sent_sms,
            "sms_from" => $twilio_number,
            "sms_to" => $sms_to,
            "sms_body" => $sms_body,
            "sent_time" => SmsHelper::getCompanyTimeNow($company_id),
            "status" => "Sent",
            "type" => "conversation",
            "direction" => "outbound-api",
            "deleted_at" => null,
        ];
        if ($sent_sms) {
            return response()->success(compact('sent_sms'));
        } else {
            return response()->error('Something Missing');
        }
    }
}
