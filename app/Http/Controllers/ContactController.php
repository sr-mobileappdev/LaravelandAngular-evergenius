<?php

namespace App\Http\Controllers;

use App\Classes\CallsHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\EmailMarketingHelper;
use App\Classes\LeadHelper;
use App\Classes\SmsHelper;
use App\Contact;
use App\ContactComment;
use App\ContactsDt;
use App\ContactTblView;
use App\EgTerm;
use App\InsuranceCompany;
use App\User;
use Auth;
use Carbon\Carbon;
use Datatables;
use DateTime;
use Excel;
use Input;
use L5Redis;
use DB;

class ContactController extends Controller
{
    public $aray_validate = array('first_name', 'last_name', 'gender', 'email', 'birth_date', 'mobile_number', 'city');
    public $outut_data;
    public function postIndex()
    {
        $user = Auth::user();

        $company_id = $user->company_id;
        $user_id = $user->id;
        $user_role = $user
            ->roles()
            ->select(['slug'])
            ->first()->toArray();

        $input = Input::get();
        $where = [];
        if (isset($input['customFilter']['assignee'])) {
            $w = array('assignee_id', '=', $input['customFilter']['assignee']);
            array_push($where, $w);
        }

        if (isset($input['customFilter']['stage'])) {
            $w = array('stage_id', '=', $input['customFilter']['stage']);
            array_push($where, $w);
        }

        if (isset($input['customFilter']['source'])) {
            $w = array('source_id', '=', $input['customFilter']['source']);
            array_push($where, $w);
        }

        if (isset($input['customFilter']['tags'])) {
            $w = array('tags', 'like', '%' . $input['customFilter']['tags'] . '%');
            array_push($where, $w);
        }

        //Fetch data for doctor
        if ($user_role['slug'] == 'doctor') {
            $contacts = ContactsDt::where('company_id', $company_id)
                ->where('provider_user_id', '=', $user_id)
                ->orWhere('assignee_id', '=', $user_id)
                ->orderBy('id', 'desc')->get();
        } else {
            $contacts = ContactTblView::select('*')
                ->where('company_id', $company_id)
                ->where($where)
                ->orderBy('id', 'desc')->get();
        }

        return Datatables::of($contacts)->make(true);
    }
    public function getShow($id)
    {
        $referred_by = '';
        $refer = array();
        $tag = array();
        $source = array();
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        $contact = Contact::find($id);

        /* Modify Date by Timezone  */
        if ($contact->company_id != $company_id) {
            return response()->error('Wrong Contact Id');
        }

        if (Auth::user()) {
            $contact['appointments'] = $contact->appointments()->with('appointment_reason', 'appointment_provider', 'appointment_status')->where('company_id', $company_id)->get();
            $contact['sms_list'] = SmsHelper::get_contact_sms($id);
            $contact['calls_list'] = CallsHelper::get_contact_calls($id);
            $contact['subscribe_list'] = EmailMarketingHelper::getSubscriptionList($id);
            if ($contact->referred_by != null) {
                $referred_by = Contact::find($contact->referred_by);
                $refer = array(
                    'title' => ucwords($referred_by->first_name . " " . $referred_by->last_name),
                    'image' => '',
                    'description' => 'ddd',
                    'originalObject' => $referred_by,
                );
            }

            if ($contact->source_id != null && empty($contact->source_id) == false) {
                $source_con = EgTerm::find($contact->source_id);
                if ($source_con != null) {
                    $source = array(
                        'title' => ucwords($source_con->term_value),
                        'image' => '',
                        'description' => 'ddd',
                        'originalObject' => $source,
                    );
                }
            }

            if ($contact->tag_id != null) {
                $souce_con = EgTerm::find($contact->tag_id);
                $tag = array(
                    'title' => ucwords($source_con->term_value),
                    'image' => '',
                    'description' => 'ddd',
                    'originalObject' => $source,
                );
            }

            $contact['reffer_by'] = $refer;
            $contact['tag'] = $tag;
            $contact['tags'] = ContactHelper::getContactTags($id);
            $contact['source'] = $source;
            $contact['lead_info'] = LeadHelper::getLeadByContact($id, $company_id);
            $contact['list_tags'] = EmailMarketingHelper::getSubscriptionList($id, $company_id);
            if ($contact['lead_info']) {
                $contact['next_lead'] = LeadHelper::getNextPreviousContact($contact['lead_info']['id'], $contact['lead_info']['stage_id'], 'next', $company_id);
                $contact['previous_lead'] = LeadHelper::getNextPreviousContact($contact['lead_info']['id'], $contact['lead_info']['stage_id'], 'previous', $company_id);
            }
            $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
            /* If Timezone Set */
            if ($tz != '' && $tz != false) {
                $time_cns = strtotime($contact->created_at);
                $contact->created_at = Carbon::createFromTimestamp($time_cns)
                    ->timezone($tz)
                    ->toDateTimeString();
                if ($contact['lead_info'] != false) {
                    $contact->created_at = $contact['lead_info']['created_at'];
                }
            }
        }

        return response()->success($contact);
    }

    public function putContactsShow()
    {
        $contactForm = Input::get('data');
        $reffer_by = null;
        $tag = null;
        $source = null;
		
        if (isset($contactForm['reffer_by']['custom']) && $contactForm['reffer_by'] != '') {
            $reffer_by = ContactHelper::addCustonRefferByContact($contactForm['reffer_by'], $contactForm['company_id']);
        } elseif (isset($contactForm['reffer_by']) && $contactForm['reffer_by'] != null && $contactForm['reffer_by'] != '') {
            $reffer_by = $contactForm['reffer_by']['id'];
        }

        if (isset($contactForm['source']) && $contactForm['source'] != '' && $contactForm['source'] != null) {
            $source = ContactHelper::addCustonTerm($contactForm['source'], 'source', $contactForm['company_id']);
        }

        if (isset($contactForm['tags'])) {
            ContactHelper::updateContactTags($contactForm['tags'], intval($contactForm['id']), $contactForm['company_id']);
        }

        if (isset($contactForm['list_tags'])) {
            EmailMarketingHelper::UserSubscribe($contactForm['list_tags'], intval($contactForm['id']), $contactForm['company_id']);
        }

        unset($contactForm['reffer_by']);
        unset($contactForm['tags']);
        unset($contactForm['tag']);
        unset($contactForm['list_tags']);
        unset($contactForm['source']);
        $contactForm['source_id'] = $source;

        $contactForm['referred_by'] = $reffer_by;
        $data_post = $contactForm;
        unset($contactForm['appointments']);
        $phone_country_code = $contactForm['phone_country_code'];
        $mobile_number = $contactForm['mobile_number'];
        $mobile_number = str_replace($phone_country_code, "", $mobile_number);
        $mobile_number = $phone_country_code . $mobile_number;
        $contactForm['mobile_number'] = $mobile_number;
        $affectedRows = Contact::where('id', '=', intval($contactForm['id']))->update($contactForm);

        return response()->success($data_post);
    }
    public function getContactsShow($id)
    {
		$user = Auth::user();
        $company_id = $user->company_id;
        $referred_by = '';
		
        $contact = Contact::find($id);
        $refer = array();
        $source = array();
		
		if (empty($contact) || $contact->company_id != $company_id) {
            return response()->error('Wrong Contact Id');
        }
        if ($contact->referred_by != null) {
            $referred_by = Contact::find($contact->referred_by);
            $refer = array(
                'title' => ucwords($referred_by->first_name . " " . $referred_by->last_name),
                'image' => '',
                'description' => 'ddd',
                'originalObject' => $referred_by,
            );
        }
        if ($contact->source_id != null) {
            $source_con = EgTerm::find($contact->source_id);
            if (count($source_con) > 0) {
                $source = array(
                    'term_value' => ucwords($source_con->term_value),
                    'image' => '',
                    'description' => ucwords($source_con->term_value),
                    'originalObject' => $source,
                );
            } else {
                $source = [];
            }
        }

        $contact['tags'] = ContactHelper::getContactTags($id);
        $contact['source'] = $source;

        $contact['appointments'] = $contact->appointments()->with('appointment_provider')->get();
        $contact['reffer_by'] = $refer;
        $contact['list_tags'] = EmailMarketingHelper::getSubscriptionList($id);
        return response()->success($contact);
    }

    public function postImportContacts()
    {
        $import_fields_validate = array('first_name', 'email', 'mobile_number');
        $user = Auth::user();
        $company_id = $user->company_id;
        $file = Input::file('contact_file');
        if (!empty($file)) {
            $out = array();
            Excel::load(
                Input::file('contact_file'),
                function ($reader) use ($company_id, $import_fields_validate) {
                    $success_count = 0;
                    $Faied_count = 0;
                    $contact_exists_count = 0;
                    foreach ($reader->toArray() as $row) {
                        $tags = null;
                        if (0 === count(array_diff($this->aray_validate, array_keys($row)))) {
                            $emplty_vals = 0;
                            $contacts = new Contact;
                            $k = [];

                            foreach ($row as $key => $single_rec) {
                                $k[] = $key;

                                if ($key == 'first_name' && !empty($single_rec)) {
                                    $contacts->first_name = $single_rec;
                                    $emplty_vals++;
                                }
                                if ($key == 'last_name' && !empty($single_rec)) {
                                    $contacts->last_name = $single_rec;
                                }
                                if ($key == 'email' && !empty($single_rec)) {
                                    $contacts->email = $single_rec;
                                    $user_tower_data = "";
                                    $contacts->additional_information = "";
                                    $emplty_vals++;
                                }
                                if ($key == 'gender' && !empty($single_rec)) {
                                    $contacts->gender = $single_rec;
                                }
                                if ($key == 'birth_date' && !empty($single_rec)) {
                                    $contacts->birth_date = date('Y-m-d', strtotime($single_rec));
                                }
                                if ($key == 'mobile_number' && !empty($single_rec)) {
                                    $default_phone_country_code = default_phone_country_code();
                                    $phone_number = $default_phone_country_code . format_phone_number($single_rec);
                                    $contacts->mobile_number = $phone_number;
                                    $contacts->phone_country_code = $default_phone_country_code;
                                    $emplty_vals++;
                                }
                                if ($key == 'address') {
                                    $contacts->address = $single_rec;
                                }
                                if ($key == 'city' && !empty($single_rec)) {
                                    $contacts->city = $single_rec;
                                }
                                if ($key == 'state') {
                                    $contacts->state = $single_rec;
                                }
                                if ($key == 'zip_code') {
                                    $contacts->zip_code = $single_rec;
                                }
                                if ($key == 'notes') {
                                    $contacts->notes = $single_rec;
                                }
                                if ($key == 'insurance_id') {
                                    $contacts->insurance_Id = $single_rec;
                                }
                                if ($key == 'insurance_provider') {
                                    $contacts->insurance_provider = $single_rec;
                                }
                                if ($key == 'insurance_group') {
                                    $contacts->insurance_group = $single_rec;
                                }
                                if ($key == 'insurance_phone') {
                                    $contacts->insurance_phone = $single_rec;
                                }

                                /* Source */
                                if ($key == 'source') {
                                    $source = ContactHelper::addCustonTerm($single_rec, 'source', $company_id);
                                    $contacts->source_id = $source;
                                }
                                /* Tags */
                                if ($key == 'tags') {
                                    $tags = explode(",", $single_rec);
                                }

                                $contacts->is_existing = 0;
                                $contacts->company_id = $company_id;
                                $contacts->created_at = new dateTime();
                                $contacts->updated_at = new dateTime();
                            }

                            $reason_exists = Contact::where('email', $row['email'])->where('company_id', $company_id)->count();
                            if (count($import_fields_validate) == $emplty_vals && $reason_exists == 0) {
                                $save_status = 'Added';
                                $contacts->save();
                                $contact_id = $contacts->id;
                                EmailMarketingHelper::addContactNewsletter($contact_id, $company_id);
                                /* Tags */
                                if ($tags != null) {
                                    ContactHelper::updateContactTags($tags, intval($contact_id), $company_id, true);
                                }
                                $success_count++;
                            } else {
                                $save_status = 'Failed';
                                $Faied_count++;
                                if ($reason_exists > 0) {
                                    $contact_exists_count++;
                                    $save_status = 'Exist';
                                }
                            }
                            $row['status'] = $save_status;
                            $out[] = $row;
                        } else {
                            return response()->error('Fields Missing');
                        }
                    }
                    $output = array('upload_status' => 'success', 'success_upload' => $success_count, 'already_exists' => $contact_exists_count, 'failed' => $Faied_count, 'contatcs' => $out);
                    $this->outut_data = $output;
                }
            );
            if (!empty($this->outut_data)) {
                return response()->success($this->outut_data);
            } else {
                $out_msg = array('upload_status' => 'failed', 'message' => 'Fields not Found');
                return response()->success($out_msg);
            }
        }
        $out_msg = array('upload_status' => 'failed', 'message' => 'File not Found');
        return response()->success($out_msg);
    }

    public function deleteContact($id)
    {
        $contact = Contact::find($id);
        $contact->appointments()->delete();
        $contact->delete();
        LeadHelper::deleteLeadByContact($id);
        return response()->success('success');
    }

    public function postDelContacts()
    {
        $contatcs_data = Input::all();
        foreach ($contatcs_data['selected_del'] as $contatcs_data_single) {
            $this->deleteContact($contatcs_data_single['value']);
        }
    }

    public function putSendSms($contact_id)
    {
        $input_data = Input::all();
        $contact_id = $input_data['id'];
        $sms_body = $input_data['sms_body'];
        $sms_to = $input_data['to'];
        $user = Auth::user();
        $company_id = $user->company_id;

        //Contact Mobile number
        $phn = Contact::where('id', '=', $contact_id)->select('mobile_number')->first()->toArray();
        $sms_to = $phn['mobile_number'];

        /* Update lead Action if lead exists */
        $lead_info = LeadHelper::getLeadByContact($contact_id, $company_id);
        if ($lead_info != false && $lead_info['action_taken'] != 1) {
            LeadHelper::updateActionTaken($lead_info['id']);
        }

        $sent_sms = SmsHelper::sendSms($sms_to, $sms_body, $company_id, 'general', $contact_id);
        return response()->success('success');
    }

    public function getFindContact()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('s');
        $contacts = ContactHelper::findContactByName($search, $company_id);
        $out = array(
            'total_count' => count($contacts),
            'incomplete_results' => false,
            'items' => $contacts,
        );
        return $out;
    }

    public function getFindTags()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('s');
        $sources = ContactHelper::findTermByName($search, $company_id, 'tag');
        $out = [];
        foreach ($sources as $value) {
            $out[] = array('text' => $value['term_value']);
        }
        return $out;
    }

    public function getFindSource()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('s');
        $sources = ContactHelper::findTermByName($search, $company_id, 'source');
        $out = array(
            'total_count' => count($sources),
            'incomplete_results' => false,
            'items' => $sources,
        );
        return $out;
    }
    public function getFindTag()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('s');
        $sources = ContactHelper::findTermByName($search, $company_id, 'tag');
        $out = array(
            'total_count' => count($sources),
            'incomplete_results' => false,
            'items' => $sources,
        );
        return $out;
    }

    public function postAddContact($list = false, $csvdata = false, $is_list = false)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $import_contact = false;
        if ($is_list == false) {
            $input = Input::get();
        } else {
            $input = $csvdata;
            $import_contact = true;
        }
        $phone_number = '';
        $email = '';
        $flag = false;
        $default_code = (isset($input['phone_country_code']) && !empty($input['phone_country_code']))?$input['phone_country_code']:"+1";

        if (isset($input['mobile_number'])) {
            if (strlen($input['mobile_number'])>9 && strlen($input['mobile_number'])<=10) {
                $phone_number = trim($default_code.$input['mobile_number']);
            } else {
                $phone_number =  trim($input['mobile_number']);
            }
        }

        if (!array_key_exists('email', $input) && !array_key_exists('mobile_number', $input)) {
            return false;
        }
        if (isset($input['email']) && empty($input['email']) && isset($input['mobile_number']) && empty($input['mobile_number'])) {
            return false;
        }

        /*       if(isset($input['email']) &&  empty(trim($input['email'])) && $import_contact){
                  $flag = true;
               } */
        /* if(isset($input['mobile_number']) && empty(trim($input['mobile_number'])) && $import_contact)
        { $flag = true; } */

        if ($flag) {
            return false;
        }

        if (isset($input['email'])) {
            $email = strtolower(trim($input['email']));
        }

        $exists = Contact::where(
            function ($query) use ($email, $phone_number) {
                if (!empty($email)) {
                    $query->where('email', '=', $email);
                }
                if (!empty($phone_number)) {
                    $query->orWhere('mobile_number', '=', $phone_number);
                }
            }
        )->where('company_id', $company_id)->count();
        $source = null;
        if (!$exists) {
            $default_phone_country_code = "";
            $reffer_by = null;
            $phone_number = "";
            if (isset($input['reffer_by']['custom']) && $input['reffer_by'] != '') {
                $reffer_by = ContactHelper::addCustonRefferByContact($input['reffer_by'], $company_id);
            } elseif (isset($input['reffer_by']) && !empty($input['reffer_by'])) {
                $reffer_by = $input['reffer_by']['id'];
            }

            $contact = new Contact;

            if (isset($input['phone_country_code'])) {
                $default_phone_country_code = $input['phone_country_code'];
            }
            if (!isset($input['phone_country_code']) || empty($input['phone_country_code'])) {
                $default_phone_country_code = "+1";
            }
            if (isset($input['mobile_number']) && !empty(trim($input['mobile_number']))) {
                if (strlen($input['mobile_number'])>9 && strlen($input['mobile_number'])<=10) {
                    $input_phone_number = $input['mobile_number'];
                    $phone_number = $default_phone_country_code . format_phone_number($input_phone_number);
                }
            }

            $dob = null;
            if (isset($input['birth_date'])) {
                $dob = $input['birth_date'];
            }

            if (isset($input['source']) && $input['source'] != '' && $input['source'] != null) {
                $source = ContactHelper::addCustonTerm($input['source'], 'source', $company_id);
            }

            $user_tower_data = "";
            if (isset($input['email']) && !empty($input['email']) && !$csvdata) {
                $user_tower_data = ContactHelper::getTowerDataFromEmail($input['email']);
            }

            if ($user_tower_data == 'Usage limit exceeded.' || $user_tower_data == 'Usage limit exceeded') {
                $user_tower_data = "";
            }
            $contact->company_id = $company_id;
            if (isset($input['first_name'])) {
                $contact->first_name = $input['first_name'];
            }
            if (isset($input['last_name'])) {
                $contact->last_name = $input['last_name'];
            }
            if (isset($input['email'])) {
                if (!empty($input['email'])) {
                    $contact->email = strtolower(trim($input['email']));
                } else {
                    $contact->email = "";
                }
            }
            $contact->mobile_number = $phone_number;
            if (isset($input['city'])) {
                $contact->city = $input['city'];
            }
            if (isset($input['gender'])) {
                $contact->gender = $input['gender'];
            }
            if (isset($input['state'])) {
                $contact->state = $input['state'];
            }
            if (isset($input['country'])) {
                $contact->country = $input['country'];
            }
            if (isset($input['address'])) {
                $contact->address = $input['address'];
            }
            if (isset($input['zip_code'])) {
                $contact->zip_code = $input['zip_code'];
            }
            $contact->source_id = $source;
            if (isset($input['address_2'])) {
                $contact->address_2 = $input['address_2'];
            }

            $contact->phone_country_code    = $default_phone_country_code;
            $contact->birth_date            = $dob;
            $contact->insurance_Id          = isset($input['insurance_Id']) ? $input['insurance_Id'] : "";
            $contact->insurance_group       = isset($input['insurance_group']) ? $input['insurance_group'] : "";
            $contact->insurance_phone       = isset($input['insurance_phone']) ? $input['insurance_phone'] : "";
            $contact->insurance_provider    = isset($input['insurance_provider']) ? $input['insurance_provider'] : "";
            $contact->referred_by           = $reffer_by;
            $contact->additional_information = $user_tower_data;
            $contact->created_at            = new dateTime();
            $status = $contact->save();
            $contact_id = $contact->id;
            EmailMarketingHelper::addContactNewsletter($contact_id, $company_id);
            if ($contact_id) {
                /* If Tags */

                if (isset($input['tags'])) {
                    ContactHelper::updateContactTags($input['tags'], intval($contact_id), $company_id, $is_list);
                }

                if (isset($input['notes'])) {
                    self::saveContactComment($input['notes'], intval($contact_id), $user->id);
                }
                if (isset($input['list_tags'])) {
                    EmailMarketingHelper::UserSubscribeContact($input['list_tags'], intval($contact_id), $company_id, true); //save to the list
                }

                if ($is_list) {
                    EmailMarketingHelper::UserSubscribeContact($list, intval($contact_id), $company_id, false); //save to the list
                    return array('status' => 'ok', 'message' => 'contact saved');
                }

                return response()->success('Contact have been saved successfully');
            } else {
                if ($is_list) {
                    return array('status' => 'fail', 'message' => 'contact not saved');
                }
                return response()->error('Unable to save contact');
            }
        } else {
            if ($is_list) {
                $user = Auth::user();
                $company_id = $user->company_id;
                if (!isset($input['email']) && !empty($phone_number)) {
                    $data = Contact::where('mobile_number', $phone_number)->where('company_id', $company_id)->first();
                    if ($data) {
                        $data = $data->toArray();
                    }
                } elseif (isset($input['email']) && !empty($input['email'])) {
                    $data = Contact::where('email', $input['email'])->where('company_id', $company_id)->first();
                    if ($data) {
                        $data = $data->toArray();
                    }
                }
                if (isset($data)) {
                    EmailMarketingHelper::UserSubscribeContact($list, $data['id'], $company_id);
                }

                return array('status' => 'exists', 'message' => 'contact saved to list');
            }
            return response()->error('Contact Already Exists');
        }
    }

    public function getInsuranceData()
    {
        $InsuranceCompanieslist = InsuranceCompany::select('insurance_company_name as name')->get();
        if ($InsuranceCompanieslist) {
            $InsuranceCompanieslist = $InsuranceCompanieslist->toArray();
            return response()->success($InsuranceCompanieslist);
        }
        return response()->error('No insurance company found');
    }

    public function postSearchContacts()
    {
        $data = Input::get();
        if (isset($data['searched_text']) && $data['searched_text'] != "") {
            $user = Auth::user();
            $searchtext = $data['searched_text'];
            $company_id = $user->company_id;
            $user_role  = $user->roles()->select(['slug'])->first()->toArray();
            $contacts = array();
            /*if($user_role['slug']=='doctor'){
                $contacts   = ContactsDt::with('contact_info')->where('company_id',$company_id)->where('provider_user_id',$user->id);
                $contacts->where(function($query) use($searchtext){
                $query->orWhere('fullname','LIKE',"%".$searchtext);
                $query->orWhere('first_name','LIKE',"%".$searchtext."%");
                $query->orWhere('mobile_number','LIKE',"%".$searchtext."%");
                });
                $contacts= $contacts->get();
            }
            else{*/
            $contacts   = ContactTblView::with(['contact_info','lead'])
                //->where('company_id',$company_id)
                ;
            $contacts->where(function ($query) use ($searchtext) {
                $query->orWhere('fullname', 'LIKE', "%".$searchtext);
                $query->orWhere('first_name', 'LIKE', "%".$searchtext."%");
                $query->orWhere('mobile_number', 'LIKE', "%".$searchtext."%");
            })
                ->where('company_id', $company_id);
            $contacts= $contacts->get();
            //}


            if ($contacts) {
                $contact = $contacts->toArray();
                return response()->success($contacts);
            }
            return response()->error('No contact found !!');
        } else {
            $contacts = array();
            return response()->success($contacts);
        }
    }

    public function getContactInfoByPhone()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        $tag = array();
        $source = array();
        $out = array('found' => false);
        if (isset($input['phone'])) {
            $phone_country_code = default_phone_country_code();
            $phone_num = $phone_country_code . $input['phone'];
            $contact = Contact::where('mobile_number', $phone_num)->where('company_id', $company_id)->first();
            if (count($contact) > 0) {
                if ($contact->source_id != null) {
                    $source_con = EgTerm::find($contact->source_id);
                    $source = array(
                        'title' => ucwords($source_con->term_value),
                        'image' => '',
                        'description' => 'ddd',
                        'originalObject' => $source,
                    );
                }

                if ($contact->tag_id != null) {
                    $tag_con = EgTerm::find($contact->tag_id);
                    $tag = array(
                        'title' => ucwords($tag_con->term_value),
                        'image' => '',
                        'description' => 'ddd',
                        'originalObject' => $tag,
                    );
                }
                $contact['tags'] = ContactHelper::getContactTags($contact->id);
                $contact['source'] = $source;
                $out = array('found' => true, 'contact_info' => $contact);
            }
        }
        return response()->success($out);
    }

    public function postAddContactModal()
    {
        $input = Input::get();
        $empty = empty($input['id']);
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $exists = Contact::where('email', $input['email'])
        ->where('company_id', $company_id)
        ->count();
        $source = null;

        if ($empty && $exists != true) {
            $reffer_by = null;

            if (isset($input['reffer_by']['custom']) && $input['reffer_by'] != '') {
                $reffer_by = ContactHelper::addCustonRefferByContact($input['reffer_by'], $company_id);
            } elseif (isset($input['reffer_by']) && !empty($input['reffer_by'])) {
                $reffer_by = $input['reffer_by']['id'];
            }
            $contact = new Contact;
            $default_phone_country_code = default_phone_country_code();

            if (isset($input['phone_country_code'])) {
                $default_phone_country_code = $input['phone_country_code'];
            }

            $input_phone_number = $input['mobile_number'];
            $phone_number = $default_phone_country_code . format_phone_number($input_phone_number);

            if (isset($input['source']) && $input['source'] != '' && $input['source'] != null) {
                $source = ContactHelper::addCustonTerm($input['source'], 'source', $company_id);
            }

            $dob = '';
            if (isset($input['birth_date'])) {
                $dob = $input['birth_date'];
            }
            $user_tower_data = "";
            $user_tower_data = ContactHelper::getTowerDataFromEmail($input['email']);
            $contact->company_id = $company_id;
            $contact->first_name = $input['first_name'];
            $contact->last_name = $input['last_name'];
            $contact->email = $input['email'];
            $contact->mobile_number = $phone_number;
            if (isset($input['city'])) {
                $contact->city = $input['city'];
            }
            if (isset($input['state'])) {
                $contact->state = $input['state'];
            }
            if (isset($input['country'])) {
                $contact->country = $input['country'];
            }
            if (isset($input['address'])) {
                $contact->address = $input['address'];
            }
            if (isset($input['zip_code'])) {
                $contact->zip_code = $input['zip_code'];
            }
            $contact->source_id = $source;
            $contact->address = isset($input['address']) ? $input['address'] : "";
            $contact->phone_country_code = $default_phone_country_code;
            $contact->birth_date = $dob;
            $contact->insurance_Id = isset($input['insurance_Id']) ? $input['insurance_Id'] : "";
            $contact->insurance_group = isset($input['insurance_group']) ? $input['insurance_group'] : "";
            $contact->insurance_phone = isset($input['insurance_phone']) ? $input['insurance_phone'] : "";
            $contact->insurance_provider = isset($input['insurance_provider']) ? $input['insurance_provider'] : "";
            $contact->referred_by = $reffer_by;
            $contact->additional_information = $user_tower_data;
            $contact->created_at = new dateTime();
            $contact->save();
            $contact_id = $contact->id;
            if ($contact_id) {
                /*Save To news letter */
                EmailMarketingHelper::addContactNewsletter($contact_id, $company_id);

                if (isset($input['tags'])) {
                    ContactHelper::updateContactTags($input['tags'], intval($contact_id), $company_id);
                }
                $input['id'] = $contact_id;
                return response()->success(array('update' => true, 'input' => $input));
            } else {
                return response()->error('Unable to save contact');
            }
            $input['id'] = $contact_id;
        } else {
            $input['id'] = ContactHelper::updateContactModal($input);
        }
        $out = array('update' => true, 'input' => $input);
        return response()->success($out);
    }

    public function getContactComments($contact_id)
    {
        $tz = '';
        $user = Auth::user();
        $company_id = $user->company_id;
        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');

        $comments = ContactComment::with('user')->where('contact_id', $contact_id)->orderBy('id', 'asc')->get();
        $dataArray = array();
        $i = 0;
        foreach ($comments as $comment) {
            $date = \Carbon\Carbon::createFromTimestamp(strtotime($comment->updated_at))
                ->timezone($tz)
                ->toDateTimeString();

            $comment_at = \Carbon\Carbon::createFromTimestamp(strtotime($comment->updated_at))
                ->timezone($tz)
                ->toDateTimeString();

            $datetime = \Carbon\Carbon::parse($comment->updated_at);
            if ($comment->created_by != null) {
                $dataArray[$i]['created_by_name'] = $comment->user->name;
                $dataArray[$i]['created_by_id'] = $comment->created_by;
            }
            $dataArray[$i]['comment'] = $comment->comment;
            $dataArray[$i]['comment_at'] = $comment_at;
            $dataArray[$i]['updated_at'] = $date;
            $dataArray[$i]['relativeTime'] = relativeTime($datetime);
            $i++;
        }

        return response()->success(array_reverse($dataArray));
    }

    public function postComment()
    {
        $data = Input::get();
        $user = Auth::user();
        $comment = new ContactComment;
        $comment->contact_id = $data['contact_id'];
        $comment->created_by = $user->id;
        $comment->comment = $data['comment'];
        $status = $comment->save();
        if ($status) {
            /* Update lead Action if lead exists */
            $lead_info = LeadHelper::getLeadByContact($data['contact_id'], $user->company_id);
            if ($lead_info != false && $lead_info['action_taken'] != 1) {
                LeadHelper::updateActionTaken($lead_info['id']);
            }

            return response()->success('Comment has been added successfully');
        } else {
            return response()->warning('Comment has not been added');
        }
    }

    public function getSystemMessage()
    {
        $redis = L5Redis::connection();
        $redis->publish(
            'chat.message',
            json_encode(
                [
                    'msg' => 'System message',
                    'nickname' => 'System',
                    'system' => true,
                ]
            )
        );
    }

    public function getContactDetails($contact_id)
    {
        $contact_details = ContactHelper::getContactInfo($contact_id);
        if ($contact_details) {
            return response()->success(compact('contact_details'));
        }
        return response()->error('contact not found');
    }

    public function postContactDetailsUpdate()
    {
        $input = Input::get();
        $id = $input['id'];
        unset($input['id']);
        ContactHelper::updateContact($id, $input);
        return response()->success('contact update successfully');
    }
    public function getSources()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $sources = ContactHelper::getTermsByType($company_id, 'source');
        return response()->success(compact('sources'));
    }

    public static function saveContactComment($comment_txt, $contact_id, $user_id)
    {
        $comment = new ContactComment;
        $comment->contact_id = $contact_id;
        $comment->created_by = $user_id;
        $comment->comment = $comment_txt;
        $status = $comment->save();
    }

    public function deleteDuplicateEntries()
    {
        $del_c_activity_time = 0;
        $del_no_activity_time = 0;
        $contacts = DB::select('SELECT n.company_id, n.email, n.mobile_number, max(n.activity_time) as "act", COUNT(*) FROM contacts n WHERE n.email <> "" AND n.deleted_at is  null GROUP BY n.company_id, n.email, n.mobile_number HAVING count(*) > 1');

        //print_r($contacts); die;
        if (count($contacts)>0) {
            foreach ($contacts as $contact) {
                $company_id = $contact->company_id;
                $email = $contact->email;
                $mobile_number = $contact->mobile_number;
                $activity_time = $contact->act;
                $del_contacts = DB::table('contacts')
                ->where('contacts.company_id', $company_id)
                ->where('contacts.mobile_number', 'like', '%'.$mobile_number.'%')
                ->where('contacts.email', 'like', '%'.$email.'%')
                ->first();
                if (count($del_contacts)>0) {
                    $del_contacts = (array) $del_contacts;

                    if ($del_contacts['activity_time']!=='0000-00-00 00:00:00') {
                        $updateDelete = DB::table('contacts')->where('contacts.company_id', $company_id)
                        ->where('contacts.mobile_number', 'like', '%'.$mobile_number.'%')
                        ->where('contacts.email', 'like', '%'.$email.'%')
                        ->where('activity_time', '!=', $del_contacts['activity_time'])
                        ->update(['temp_del'=>10]);
                        $del_c_activity_time++;
                    } else {
                        $min_id = DB::table('contacts')
                        ->select(DB::raw('min(id) as min_id'))
                        ->where('contacts.company_id', $company_id)
                        ->where('contacts.mobile_number', 'like', '%'.$mobile_number.'%')
                        ->where('contacts.email', 'like', '%'.$email.'%')
                        ->first();
                        $updateDelete = DB::table('contacts')
                        ->where(['company_id'=>$company_id,
                        'email' =>$email,
                        'mobile_number' =>$mobile_number
                        ])
                        ->where('id', '>', $min_id->min_id)
                        ->update(['temp_del'=>11]);
                        $del_no_activity_time++;
                    }
                }
            }
        }
        echo 'update no activity '.  $del_no_activity_time.'<br>';
        echo 'update activity '.  $del_c_activity_time;
    }
}
