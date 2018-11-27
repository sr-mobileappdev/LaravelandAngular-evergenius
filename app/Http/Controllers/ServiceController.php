<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\LeadHelper;
use App\Classes\EmailMarketingHelper;
use Input;

class ServiceController extends Controller
{
    public function addOppertunity()
    {
        $f_name = null;
        $l_name = null;
        $gender = null;
        $address = null;
        $city = null;
        $state = null;
        $country = null;
        $country_code = '+1';
        $zip_code = null;
        $source_id = null;
        $dnd = null;
        $service_id = null;
        $user_id = null;
        $ltv = null;
        $source = null;
        $input = Input::get();
        $notes = '';
        $contact_existing = 0;
        $email = null;
        $phone = null;

        if (isset($input['first_name'])) {
            $f_name = $input['first_name'];
        }
        if (isset($input['last_name'])) {
            $l_name = $input['last_name'];
        }
        if (isset($input['phone'])) {
            $phone = $input['phone'];
        }
        if (isset($input['email'])) {
            $email = $input['email'];
        }
        if (isset($input['address'])) {
            $address = $input['address'];
        }
        if (isset($input['gender'])) {
            $gender = $input['gender'];
        }
        if (isset($input['city'])) {
            $city = $input['city'];
        }
        if (isset($input['state'])) {
            $state = $input['state'];
        }
        if (isset($input['zip_code'])) {
            $zip_code = $input['zip_code'];
        }
        if (isset($input['ltv'])) {
            $ltv = $input['ltv'];
        }
        if (isset($input['additional_info'])) {
            $input['notes'] = str_replace("\n", "<br>", $input['additional_info']);
        }

        $company_id = 1;
        if (isset($input['company_name'])) {
            $company_id = CompanySettingsHelper::findCompanyBySingleName(trim($input['company_name']));
            if ($company_id == false) {
                $company_id = 1;
            }
        }

        if (isset($input['service'])) {
            $service_id = LeadHelper::getServiceByName(trim($input['service']), $company_id);
        }

        if (isset($input['source'])) {
            $source_id = ContactHelper::addCustonTerm($input['source'], 'source', $company_id);
        }
        /* If Contact Exists get id */
        if ($phone != null) {
            $existContact = ContactHelper::isContactExistsByPhone($company_id, $phone);
        } elseif ($email != null) {
            $existContact = ContactHelper::getContactIdByEmail($company_id, $email);
        }

        if (!$existContact) {
            $contact_id = ContactHelper::storeContact($company_id, $f_name, $l_name, $email, $phone, $gender, $address, $city, $state, $country, $zip_code, $source_id, 0, $country_code, $contact_existing);
        } else {
            $contact_id = $existContact;
            ContactHelper::updateContactById($contact_id, $input);
        }

        $stage = LeadHelper::getStageBySlug($company_id, 'prospects');

        if (isset($input['notes'])) {
            ContactHelper::attachContactNote($contact_id, $input['notes'], null);
            $notes = $input['notes'];
        }
        /* Subscribed To news Letter */
        if (isset($input['newsletter_list']) && $input['newsletter_list'] != '') {
            $newsLetterId = EmailMarketingHelper::getListIdByName($input['newsletter_list'], $company_id);
            if ($newsLetterId != false) {
                EmailMarketingHelper::UserSubscribeContact($newsLetterId, $contact_id, $company_id);
            }
        }

        ContactHelper::updateConatctSource($company_id, $contact_id, $source_id);
        if (LeadHelper::getLeadByContact($contact_id, $company_id) == false) {
            $lead_id = LeadHelper::add_lead($company_id, $contact_id, $stage, $service_id, $source_id, $user_id, $ltv, 1,  null, $notes);
        } else {
            $lead_info = LeadHelper::getLeadByContact($contact_id, $company_id);
            $lead_id = $lead_info['id'];
            //LeadHelper::notificationOnLead($contact_id, $lead_id, $notes, $source_id);
        }

        /* Update action where manual lead added*/
        if (isset($input['action_take'])) {
            LeadHelper::updateActionTaken($lead_id);
        }
        return response()->success('success');
    }

    /* Addlead from Click Funnel */
    public function addOppertunityClickFunnel()
    {
        $f_name = null;
        $l_name = null;
        $gender = 'male';
        $address = null;
        $city = null;
        $state = null;
        $country = null;
        $country_code = '+1';
        $zip_code = null;
        $source_id = null;
        $dnd = null;
        $service_id = null;
        $user_id = null;
        $c_user_id = null;
        $ltv = null;
        $source = null;
        $input = Input::get();
        $notes = '';
        $contact_existing = 0;
        $phone = null;
        if (!isset($input['id'])) {
            return response()->success('failed', 200);
        }
        //print_r($input); die;
        if (isset($input['first_name'])) {
            $f_name = $input['first_name'];
        }
        if (isset($input['last_name'])) {
            $l_name = $input['last_name'];
        }
        if (isset($input['phone'])) {
            $phone = $input['phone'];
        }
        if (isset($input['email'])) {
            $email = $input['email'];
        }
        if (isset($input['address'])) {
            $address = $input['address'];
        }
        if (isset($input['gender'])) {
            $gender = $input['gender'];
        }
        if (isset($input['city'])) {
            $city = $input['city'];
        }
        if (isset($input['state'])) {
            $state = $input['state'];
        }
        if (isset($input['zip_code'])) {
            $zip_code = $input['zip_code'];
        }
        if (isset($input['ltv'])) {
            $ltv = $input['ltv'];
        }

        if (isset($input['additional_info'])) {
            $input['notes'] = getClickFunnelNotes($input['additional_info']);
        }
        $company_id = 1;
        if (isset($input['company_name'])) {
            $company_id = CompanySettingsHelper::findCompanyBySingleName($input['company_name']);
            if ($company_id == false) {
                $company_id = 1;
            }
        }

        /* If Contact Exists get id */
        if ($phone != null) {
            $existContact = ContactHelper::isContactExistsByPhone($company_id, $phone);
        } elseif ($email != null) {
            $existContact = ContactHelper::getContactIdByEmail($company_id, $email);
        }

        if (!$existContact) {
            $contact_id = ContactHelper::storeContact($company_id, $f_name, $l_name, $email, $phone, $gender, $address, $city, $state, $country, $zip_code, $source_id, 0, $country_code, $contact_existing);
        } else {
            $contact_id = $existContact;
            ContactHelper::updateContactById($contact_id, $input);
        }

        $stage = LeadHelper::getStageBySlug($company_id, 'prospects');

        if (isset($input['notes'])) {
            ContactHelper::attachContactNote($contact_id, $input['notes'], null);
            $notes = $input['notes'];
        }

        ContactHelper::updateConatctSource($company_id, $contact_id, $source_id);
        if (LeadHelper::getLeadByContact($contact_id, $company_id) == false) {
            $lead_id = LeadHelper::add_lead($company_id, $contact_id, $stage, $service_id, $source_id, $user_id, $ltv, 1, $created_by = null, $notes);
        } else {
            $lead_info = LeadHelper::getLeadByContact($contact_id, $company_id);
            $lead_id = $lead_info['id'];
            //LeadHelper::notificationOnLead($contact_id, $lead_id, $notes, $source_id);
        }

        /* Update action where manual lead added*/
        if (isset($input['action_take'])) {
            LeadHelper::updateActionTaken($lead_id);
        }
        return response()->success('success', 200);
    }

    /*Add new Agent User*/
    public function addNewAgentUser(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|min:3',
                'email' => 'required|email|unique:users,email,' . $request->email,
                'phone' => 'required|min:3',
            ]
        );
        if (!isset($request->phone_country_code)) {
            $request->phone_country_code = '+1';
        }
        if (!isset($request->agency_name)) {
            $request->agency_name = $request->name;
        }
        /*Number of license */
        $request->num_license = 10;


        $request->role = 'super.admin.agent';
        $userId = \App\Classes\UserHelper::createSuperAdminUser($request);
        if ($userId) {
            return response()->success(['satus' => 'success']);
        }
    }
}
