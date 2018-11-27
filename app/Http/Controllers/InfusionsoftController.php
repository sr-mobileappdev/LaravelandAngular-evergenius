<?php
namespace App\Http\Controllers;

use App\Classes\AppOptionsHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\LeadHelper;
use App\Classes\NotificationHelper;
use App\Contact;
use App\ContactComment;
use App\ContactTag;
use App\InfusionDataQueue;
use Curl;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Input;

class InfusionsoftController extends Controller
{

    public function addContactHook(Request $request)
    {
        /* Verify Hook Secret */
        if ($request->header('X-Hook-Secret')) {
            AppOptionsHelper::updateOptionValue('infusionsoft_header', $request->header('X-Hook-Secret'));

            $hook_sec = $request->header('X-Hook-Secret');
            $key = AppOptionsHelper::getOptionValue('infusionsoft_add_contact_key');
            $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
            $api_url = 'https://api.infusionsoft.com/crm/rest/v1/hooks/' . $key . '/delayedVerify?access_token=' . $inf_token;
            $response = Curl::to($api_url)
                ->withHeader('X-Hook-Secret:' . $hook_sec)
                ->post();
        }

        $request_input = $request->input();

        if (isset($request_input['verification_key'])) {
            AppOptionsHelper::updateOptionValue('infusionsoft_header', $request_input['verification_key']);
            $hook_sec = $request_input['verification_key'];
            $key = AppOptionsHelper::getOptionValue('infusionsoft_add_contact_key');
            $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
            $api_url = 'https://api.infusionsoft.com/crm/rest/v1/hooks/' . $key . '/delayedVerify?access_token=' . $inf_token;
            $response = Curl::to($api_url)
                ->withHeader('X-Hook-Secret:' . $hook_sec)
                ->post();
        }

        if (isset($request_input['object_keys'])) {
            $contact_id = $request_input['object_keys'][0]['id'];
            //sleep(5);
            $this->setContactInQueue($contact_id);
            //$this->fetchContactDetails($contact_id);
        }

        return response()->success('contact added');
    }

    /**
     * Set lead in queue
     **/

    public function setContactInQueue($inf_object_id)
    {
        $inf_get = InfusionDataQueue::where('inf_object_id', $inf_object_id)->count();
        if ($inf_get==0) {
            $idq = new InfusionDataQueue;
            $idq->inf_object_id = $inf_object_id;
            $idq->status = 0;
            $idq->save();
        }
    }

    public static function fetchContactDetails()
    {
        $name = '';
        $email = '';
        $phone = '';
        $notes = '';

        $ins_data = [];
        $message_obj = "COMPANY_NOT_FOUND";

        $custon_fields_c = InfusionsoftController::getAllCustomFields();

        $company_custom_field_index = searchForLabel('Company Name', $custon_fields_c);
        $company_custom_field_id = $custon_fields_c[$company_custom_field_index]['id'];
       
        $date = new \DateTime;
        $date->modify('-5 minutes');
        $formatted_date = $date->format('Y-m-d H:i:s');

        $allRecords = InfusionDataQueue::Where('status', '0')->where('created_at', '<=', $formatted_date)->get();
        if (count($allRecords) > 0 && $company_custom_field_index != null) {
            InfusionsoftController::refreshToken();

            foreach ($allRecords as $lead) {
                $contact_id = $lead->inf_object_id;
                $InfusionDataQueue_ID = $lead->id;
                $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
                $api_url = 'https://api.infusionsoft.com/crm/rest/v1/contacts/' . $contact_id . '?optional_properties=custom_fields&access_token=' . $inf_token;
                $response = Curl::to($api_url)->asJson()->get();
                if ($response == null) {
                    $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
                    $api_url = 'https://api.infusionsoft.com/crm/rest/v1/contacts/' . $contact_id . '?optional_properties=custom_fields&access_token=' . $inf_token;
                    $response = Curl::to($api_url)
                        ->asJson()
                        ->get();
                }
                $cf_comp_index = null;

                if (isset($response->custom_fields)) {
                    $contact_cf = $array = json_decode(json_encode($response->custom_fields), true);
                    $cf_comp_index = searchForId($company_custom_field_id, $contact_cf);
                }

                if ($cf_comp_index != null && !empty($cf_comp_index)) {
                    $company_name = trim($contact_cf[$cf_comp_index]['content']);
                    $company_id = CompanySettingsHelper::findCompanyBySingleName($company_name);
                    if ($company_id == false) {
                        $message_obj = "COMPANY_NOT_FOUND";
                        $company_id = 1;
                        InfusionsoftController::updateInfusionDataQueue($InfusionDataQueue_ID, null, $message_obj);
                        continue;
                    } else {
                        $message_obj = "COMPANY_FOUND";
                    }
                } else {
                    $message_obj = "CUSTOM_FIELDS_NOT_FOUND";
                    InfusionsoftController::updateInfusionDataQueue($InfusionDataQueue_ID, null, $message_obj, 3);
                    continue;
                }
                /* Contact Already Exists */
                $conatct_exists = Contact::where(['infusionsoft_id' => $contact_id , 'company_id' => $company_id])->count();
                if ($conatct_exists > 0) {
                    $message_obj = "CONTACT_EXISTS";
                    InfusionsoftController::updateInfusionDataQueue($InfusionDataQueue_ID, null, $message_obj, 2);
                    continue;
                }
                /* Contact Already Exists */

                $ins_data['infusionsoft_id'] = $contact_id;
                $api_response_object = serialize($response);
                if (isset($response->custom_fields)) {
                    $custom_fields = $response->custom_fields;
                    unset($response->custom_fields);
                }

                $ins_data['company_id'] = $company_id;
                if (isset($response->given_name)) {
                    $ins_data['first_name'] = $response->given_name;
                    $name = $response->given_name;
                    unset($response->given_name);
                }
                if (isset($response->family_name)) {
                    $ins_data['last_name'] = $response->family_name;
                    unset($response->family_name);
                }
                if (isset($response->email_addresses) && empty($response->email_addresses) != true) {
                    $ins_data['email'] = $response->email_addresses[0]->email;
                    $email = $response->email_addresses[0]->email;
                    unset($response->email_addresses);
                }

                if (isset($response->phone_numbers) && empty($response->phone_numbers) != true) {
                    $default_phone_country_code = default_phone_country_code();
                    $ins_data['phone_country_code'] = $default_phone_country_code;
                    $ins_data['mobile_number'] = $default_phone_country_code . format_phone_number($response->phone_numbers[0]->number);
                    $phone = $response->phone_numbers[0]->number;
                    unset($response->phone_numbers);
                }
                if (isset($response->addresses) && empty($response->addresses) != true) {
                    if (isset($response->addresses[0]->line1)) {
                        $ins_data['address'] = $response->addresses[0]->line1;
                    }
                    if (isset($response->addresses[0]->line2)) {
                        $ins_data['address_2'] = $response->addresses[0]->line2;
                    }
                    $ins_data['zip_code'] = $response->addresses[0]->postal_code;
                    unset($response->addresses);
                }
                if (isset($response->birthday)) {
                    $pos = strpos($response->birthday, 'T');
                    $dob = substr($response->birthday, 0, $pos);
                    $ins_data['birth_date'] = $dob;
                    unset($response->birthday);
                }
                if (isset($response->tag_ids)) {
                    $tags = $response->tag_ids;
                    unset($response->tag_ids);
                }
                if (isset($response->last_updated)) {
                    unset($response->last_updated);
                }
                if (isset($response->date_created)) {
                    unset($response->date_created);
                }
                $ins_data['is_existing'] = 0;

                $contact_id = DB::table('contacts')->insertGetId($ins_data);
                
                /*Add New Contact To newsletter*/
                \App\Classes\EmailMarketingHelper::addContactNewsletter($contact_id, $company_id);

                InfusionsoftController::updateInfusionDataQueue($InfusionDataQueue_ID, $api_response_object, $message_obj); /**Update infusion queue table with data**/
                $notes = '';
                if (isset($custom_fields)) {
                    $notes = InfusionsoftController::customFieldsSave($custon_fields_c, $custom_fields, $contact_id, $company_id);
                }
                $con_info = ContactHelper::getContactInfo($contact_id);
                $source_id = $con_info->source_id;
                $service_id = null;
                $stage_id = LeadHelper::getStageBySlug($company_id, 'prospects');
                $lead_id = LeadHelper::add_lead($company_id, $contact_id, $stage_id, $service_id, $source_id, null, null, null, null, $notes);
                sleep(1);
                /* Email Notify */
                /*InfusionsoftController::NotifyAdminEmail($name, $email, $phone, $notes, $company_id);*/
            }
        }
    }

    public static function attachContactTags($contact_id, $con_tags, $company_id)
    {
        $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
        $api_url = 'https://api.infusionsoft.com/crm/rest/v1/tags?access_token=' . $inf_token;
        $response = Curl::to($api_url)
            ->get();
        $res = json_decode($response, true);
        $ins = array();
        $tags = $res['tags'];
        foreach ($con_tags as $key => $con_tag) {
            $term_title = InfusionsoftController::getRoleTitle($tags, $con_tag);
            $con_tag_id = ContactHelper::addCustonTerm($term_title, 'tag', $company_id);
            $ins[] = array(
                'tag_id' => $con_tag_id,
                'contact_id' => $contact_id,
            );
        }
        if (count($ins) > 0) {
            ContactTag::insert($ins);
        }
    }

    public static function getAllCustomFields()
    {

        if (Cache::has('InfusionsoftCustomFields')) {
            $res = Cache::get('InfusionsoftCustomFields');
        } else {
            $minutes = 24 * 60;
            $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
            $api_url = 'https://api.infusionsoft.com/crm/rest/v1/contactCustomFields?access_token=' . $inf_token;
            $response = Curl::to($api_url)
                ->get();
            $res = json_decode($response, true);
            Cache::add('InfusionsoftCustomFields', $res, $minutes);
        }
        return $res;
    }

    public function getTagsNames($con_tags)
    {
        $out = [];
        $inf_token = AppOptionsHelper::getOptionValue('infusionsoft_token');
        $api_url = 'https://api.infusionsoft.com/crm/rest/v1/tags?access_token=' . $inf_token;
        $response = Curl::to($api_url)
            ->get();
        $res = json_decode($response, true);
        $tags = $res['tags'];
        foreach ($con_tags as $key => $con_tag) {
            $tag_title = $this->getRoleTitle($tags, $con_tag);
            $out[] = $tag_title;
        }

        print_r($out);
        die();
        return $out;
    }

    public static function updateInfusionDataQueue($id, $data_object, $message, $status = 1)
    {
        $InfusionDataQueue = InfusionDataQueue::find($id);
        $InfusionDataQueue->response_object = $data_object;
        $InfusionDataQueue->status = $status;
        $InfusionDataQueue->error_message = $message;
        $InfusionDataQueue->save();
    }

    public static function getRoleTitle($tags, $tag_id)
    {
        foreach ($tags as $key => $tag) {
            if ($tag['id'] == $tag_id) {
                return $tag['name'];
            }
        }
        return 'unknown';
    }

    public static function customFieldsSave($custon_fields_c, $fields, $contact_id, $company_id)
    {
        $find_source = false;
        $all_fields = $custon_fields_c;
        $notes = '';
        foreach ($fields as $key => $single_field) {
            if ($single_field->content != null) {
                $label_field = InfusionsoftController::getCustomFieldTitle($all_fields, $single_field->id);
                $notes .= $label_field . ': ' . $single_field->content . "<br>";
                /* update Source of lead */
                if ($label_field == 'Funnel') {
                    $find_source = true;
                    $source_id = ContactHelper::addCustonTerm($single_field->content, 'source', $company_id);
                    ContactHelper::updateConatctSource($company_id, $contact_id, $source_id);
                }
            }
        }

        if (!$find_source) {
            $default_infusionsoft_source = AppOptionsHelper::getOptionValue('infusionsoft_default_source');
            $source_id = ContactHelper::addCustonTerm($default_infusionsoft_source, 'source', $company_id);
            ContactHelper::updateConatctSource($company_id, $contact_id, $source_id);
        }

        $admin_user_id = CompanySettingsHelper::findCompanyAdminId($company_id);

        if ($notes != '' && $admin_user_id != false) {
            $comment = new ContactComment;
            $comment->contact_id = $contact_id;
            $comment->created_by = $admin_user_id;
            $comment->comment = $notes;
            $status = $comment->save();
            return $notes;
        }
        return false;
    }

    public static function getCustomFieldTitle($fields, $fiels_id)
    {
        foreach ($fields as $key => $field) {
            //echo $tag['id'].'<br>';
            if ($field['id'] == $fiels_id) {
                return $field['label'];
            }
        }
        return 'unknown';
    }

    public static function NotifyAdminEmail($name, $email, $phone, $notes, $company_id)
    {
        $email_message = NotificationHelper::getNotificationMethod(0, 'mail', 'LEAD_ADD_INFUSIONSOFT');
        $email_subject = NotificationHelper::getNotificationSubject(0, 'mail', 'LEAD_ADD_INFUSIONSOFT');
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_email = $company_information['email'];

        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $email_message = str_replace("{{contant_name}}", $name, $email_message);
        $email_message = str_replace("{{contact_phone}}", $phone, $email_message);
        $email_message = str_replace("{{contact_email}}", $email, $email_message);
        $email_message = str_replace("{{notes}}", $notes, $email_message);
        $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);
        $email_message = str_replace("{{time}}", date('M d h:i A', time()), $email_message);

        if ($email_message != false && $email_subject != false) {
            $message = nl2br($email_message);
            $app_from_email = app_from_email();
            $data['company_information'] = $company_information;
            $data['company_information']['logo'] = 'img/mail_image_preview.png';
            $data['content_data'] = $email_message;
            $bcc_email = getenv('BCC_EMAIL');
            CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
            //\App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_message, $email_subject, $appointment_id, 'appointment', 'NEW_APPOINTMENT_WEB');
        }
    }

    public static function refreshToken()
    {
        $url = "https://api.infusionsoft.com/token";
        $old_refresh_token = AppOptionsHelper::getOptionValue('infusionsoft_refresh_token');
        $base_64_client = base64_encode(getenv('INFUSIONSOFT_CLIENT_ID') . ':' . getenv('INFUSIONSOFT_SECRET'));
        $response = Curl::to($url)
            ->withData(array('grant_type' => 'refresh_token',
                'refresh_token' => $old_refresh_token,
            ))
            ->withHeader('Authorization: Basic ' . $base_64_client)
            ->withHeader('Content-Type:application/x-www-form-urlencoded')
            ->post();
        $res = json_decode($response, true);
        if (isset($res['refresh_token'])) {
            AppOptionsHelper::updateOptionValue('infusionsoft_refresh_token', $res['refresh_token']);
            AppOptionsHelper::updateOptionValue('infusionsoft_token', $res['access_token']);
        }
    }
}
