<?php

namespace App\Http\Controllers;

use App\Classes\BeeFreeHelper;
use App\Classes\CompanyHelper;
use App\Classes\EmailMarketingHelper;
use App\EmNewsletterContacts;
use App\EmNewsletterList;
use App\Http\Controllers\ContactController;
use App\User;
use Auth;
use Datatables;
use Excel;
use Illuminate\Http\Request;
use Input;

//use App\Repositories\EmailMarketing\EloquentEmailMarketing;

class EmailMarketingController extends Controller
{
    /**
     * function to add a list
     **/

    public function postAddNewList()
    {
        // /email-marketing/add-new-list
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = Input::get();
        if ($data && $company_id) {
            $enll_object = new EmNewsletterList;
            $enll_object->company_id = $company_id;
            $enll_object->name = $data['name'];
            $enll_object->unique_id = uniqid();
            $enll_object->description = "";
            if (isset($data['description'])) {
                $enll_object->description = $data['description'];
            }
            $status = $enll_object->save();
            if ($status) {
                return response()->success(array('status' => 'ok', 'message' => 'Newsletter list created'));
            } else {
                return response()->success(array('status' => 'fail', 'message' => 'Something went wrong !!'));
            }
        } else {
            return response()->success(array('status' => 'fail', 'message' => 'Something went wrong !!'));
        }
    }
    /**
     * function to show created list
     **/
    public function postEmailList()
    {
        // /email-marketing/email-list
        $user = Auth::user();
        $company_id = $user->company_id;
        $resultant = EmNewsletterList::withCount('today', 'yesterday', 'sub', 'unsub', 'sent', 'funnel')->where('company_id', $company_id)->orderBy('created_at', 'desc')->get();
        return Datatables::of(collect($resultant))->make(true);
    }

    /**
     * function to upload the csv and return array of column names
     **/
    public function postUploadContactList(Request $request)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $file = Input::file('contact_file');
        $type = $file->getClientOriginalExtension();
        $size = $file->getClientSize();

        if (!$file) {
            return response()->json(array('status' => 'fail', 'message' => 'File not found', 'csv_fields' => array(), 'filename' => ""));
        }

        if ($type != 'csv') {
            return response()->json(array('status' => 'fail', 'message' => 'Invalid file type,Please file type of csv', 'csv_fields' => array(), 'filename' => ""));
        }

        if ($size > 5242880) {
            return response()->json(array('status' => 'fail', 'message' => 'Exceed max file,Allowed file size only 5mb', 'csv_fields' => array(), 'filename' => ""));
        }

        $data = array();
        $destinationPath = public_path() . '/marketing_uploads';

        if (!file_exists($destinationPath)) {
            \File::makeDirectory($destinationPath, 0777);
        }

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $filepath = $destinationPath . "/" . $filename;
        $file->move($destinationPath, $filename);
        $data = Excel::load(
            $filepath,
            function ($reader) {
            }
        )->get();
        if ($data) {
            $data = $data->toArray();
            if (isset($data[0])) {
                $data = array_keys($data[0]);
                // $table    = (new Contact)->getTable();
                //$columns  = \Schema::getColumnListing($table);
                $columns = array("first_name", "last_name", "gender", "email", "birth_date", "mobile_number", "address", "city", "state", "country", "zip_code", "source", "tags", 'notes');

                $list = EmNewsletterList::select('id', 'name')->where('company_id', $company_id)->get()->toArray();

                return response()->success(array('status' => 'ok', 'message' => 'keys fetched successfully', 'csv_fields' => $data, 'filename' => $filename, 'fields' => $columns, 'list' => $list));
            } else {
                return response()->json(array('status' => 'fail', 'message' => 'something went wrong', 'csv_fields' => array(), 'filename' => $filename));
            }
        } else {
            return response()->json(array('status' => 'fail', 'message' => 'something went wrong', 'csv_fields' => array(), 'filename' => $filename));
        }
    }

    /**
     * Function to show user list after mapping the fields with csv
     **/
    public function postContactsList($list_id = null)
    {
        if ($list_id == null) {
            return response()->error('list ID is required!!');
        }
        $user = Auth::user();
        $company_id = $user->company_id;
        if ($list_id && $company_id) {
            $contacts = EmNewsletterContacts::select(
                ['em_newsletter_contacts.list_id',
                    'em_newsletter_contacts.contact_id',
                    'em_newsletter_contacts.id',
                    'em_newsletter_contacts.status_id',
                    'em_newsletter_contacts.created_at',
                    'contacts.first_name',
                    'contacts.last_name',
                    'contacts.email',
                    'contacts.mobile_number',
                    'em_list_status.name as contact_status',
                ]
            )
                ->from('em_newsletter_contacts')
                ->join('contacts', 'em_newsletter_contacts.contact_id', '=', 'contacts.id')
                ->join('em_list_status', 'em_newsletter_contacts.status_id', '=', 'em_list_status.id')
                ->where('em_newsletter_contacts.company_id', $company_id)
                ->where('em_newsletter_contacts.list_id', $list_id)
                ->orderBy('em_newsletter_contacts.contact_id', 'desc')
                ->get();
            return Datatables::of($contacts)->make(true);
        }
    }

    public function getListStatuses()
    {
        $user = Auth::user();
        $statuses = EmailMarketingHelper::getstatuses();
        return response()->success(compact('statuses'));
    }

    public function putContactStatus($contact_id = null)
    {
        if ($contact_id == null) {
            return response()->error('ID is required!!');
        }
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();

        if (isset($input['status'])) {
            $status = $input['status'];
        }

        $update = EmailMarketingHelper::updateContactStatus($contact_id, $status);
        if ($update) {
            return response()->success('update success');
        }
        return response()->error('something went wrong!!');
    }

    public function getContactListStat($list_id = null)
    {
        if ($list_id == null) {
            return response()->error('ID is required!!');
        }
        $user = Auth::user();
        $company_id = $user->company_id;
        $statuses = EmailMarketingHelper::getstatuses();
        $listname = EmailMarketingHelper::getListNameById($list_id);
        $stats = [];
        $stats['list_name'] = $listname;
        foreach ($statuses as $status) {
            $stats[$status['name']] = EmailMarketingHelper::getCountContactsByStatus($list_id, $status['id']);
        }
        $stats['sent'] = EmailMarketingHelper::getSentCount($list_id);
        return response()->success(compact('stats'));
    }

    public function postProcessCsvContacts()
    {
        $data = Input::get();
        $mapped_variables = $data['mapped']['csv'];
        $csvfilename = $data['csvfilename'];
        $filepath = public_path() . "/marketing_uploads/" . $csvfilename;
        $filedata = Excel::load(
            $filepath,
            function ($reader) {
            }
        )->get();
        $final_output = array();
        $errors = array();
        $unset_value = array();
        if ($filedata) {
            $i = 0;
            $csvdata = $filedata->toArray();
            //dd($mapped_variables);
            foreach ($csvdata as $item) {
                foreach ($mapped_variables as $key => $value) {
                    $valid = self::checkvalid($item[$key], $key, $value);
                    if ($valid['status'] == 'ok') {
                        if ($value=='email') {
                            $final_output[$i][$value] = trim(strtolower($item[$key]));
                        } elseif ($value=='first_name') {
                            $final_output[$i][$value] = $item[$key]==null?'':$item[$key] ;
                        } elseif ($value=='last_name') {
                            $final_output[$i][$value] = $item[$key]==null?'':$item[$key] ;
                        } elseif ($value=='mobile_number') {
                            $mb = EmailMarketingHelper::cleanStr($item[$key]);
                            if (!empty($mb) && strlen($mb)>=10) {
                                $final_output[$i][$value] = $mb;
                            } else {
                                $final_output[$i][$value]  = "";
                            }
                        } else {
                            $final_output[$i][$value] = $item[$key];
                        }
                    }
                    if ($valid['status'] == 'empty') {
                        $unset_value[$i] = 1;
                    }
                    if ($valid['status'] == 'fail') {
                        if (isset($valid['message'])) {
                            $errors[] = $valid['message'];
                        }
                    }
                }
                $i++;
            }
            if (count($unset_value) > 0) {
                foreach ($unset_value as $key => $value) {
                    unset($final_output[$key]);
                }
            }
        }
        $collections = collect($final_output);
        return Datatables::of($collections)->make(true);
    }

    public function postSaveContacts(Request $request)
    {
        $savedrecords = 0;
        $notsavedRecords = 0;
        $existsrecords = 0;
        $data  = Input::get();
        if (!isset($data['mapped'])) {
            return response()->json(array('status' => 'fail', 'message' => 'Something went wrong,Please refresh and try again !!'));
        }

        $mapped_variables = $data['mapped']['csv'];
        $filename = $data['csvfilename'];
        $list = isset($data['mapped']['list']) && $data['mapped']['list'] != "" ? $data['mapped']['list'] : "";
        $filepath = public_path() . "/marketing_uploads/" . $filename;
        $filedata = Excel::load(
            $filepath,
            function ($reader) {
            }
        )->get();
        $final_output = array();
        $unset_value = array();
        if ($filedata) {
            $i = 0;
            $csvdata = $filedata->toArray();
            foreach ($csvdata as $item) {
                foreach ($mapped_variables as $key => $value) {
                    $valid = self::checkvalid($item[$key], $key, $value);
                    if ($valid['status'] == 'ok') {
                        if ($value=='email') {
                            $final_output[$i][$value] = trim(strtolower($item[$key]));
                        } elseif ($value=='first_name') {
                            $final_output[$i][$value] = $item[$key]==null?'':$item[$key] ;
                        } elseif ($value=='last_name') {
                            $final_output[$i][$value] = $item[$key]==null?'':$item[$key] ;
                        } elseif ($value=='mobile_number') {
                            $mb = EmailMarketingHelper::cleanStr($item[$key]);
                            if (!empty($mb) && strlen($mb)>=10) {
                                $final_output[$i][$value] = $mb;
                            } else {
                                $final_output[$i][$value]  = "";
                            }
                        } elseif ($value == 'tags') {
                            $item[$key] = explode(',', $item[$key]);
                        } else {
                            $final_output[$i][$value] = $item[$key];
                        }
                    }
                    if ($valid['status'] == 'empty') {
                        $unset_value[$i] = 1;
                    }
                }
                $i++;
            }
            if (count($unset_value) > 0) {
                foreach ($unset_value as $key => $value) {
                    unset($final_output[$key]);
                }
            }
        }
        $contactObject = new ContactController;
        foreach ($final_output as $item) {
            $status = $contactObject->postAddContact($list, $item, true);
            if ($status['status'] == 'exists') {
                $existsrecords++;
            }
            if ($status['status'] == 'ok') {
                $savedrecords++;
            }
            if ($status['status'] == 'fail') {
                $notsavedRecords++;
            }
            if ($status===false) {
                $notsavedRecords++;
            }
        }
        return response()->json(array('status' => 'ok', 'message' => 'Records imported', 'existsrecords' => $existsrecords, 'savedrecords' => $savedrecords, 'nosavedrecords' => $notsavedRecords, 'invalid_record' => $unset_value));
    }

    public function checkvalid($value, $mappedCol, $dbcolumnname)
    {
        if ($dbcolumnname == 'email') {
            return array('status' => 'ok');
        }
        if ($dbcolumnname == 'first_name') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'last_name') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'mobile_number') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'gender') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'birth_date') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'address') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'city') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'state') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'country') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'zip_code') {
            return array('status' => 'ok');
        }

        if ($dbcolumnname == 'tags') {
            return array('status' => 'ok');
        }
        if ($dbcolumnname == 'source') {
            return array('status' => 'ok');
        }
        if ($dbcolumnname == 'notes') {
            return array('status' => 'ok');
        }

        return array('status' => 'fail');
    }

    public function getFindEmailList()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('s');
        $sources = EmailMarketingHelper::findNewsLetterLists($search, $company_id);
        $out = [];
        foreach ($sources as $value) {
            $out[] = array('id' => $value['id'], 'title' => $value['name']);
        }
        return $out;
    }

    public function postCampaign($campaign_id = null)
    {
        // sendmail,inprogress,scheduled,draft
        $input = Input::get();
        $user = Auth::user();
        $company_id = $user->company_id;
        $campignId = EmailMarketingHelper::CreateCampaign($input, $company_id, $campaign_id);
        if ($campignId==false) {
            return response()->error(['status' => 'fail','message'=>'Unable to Create Campaign, Please Refresh page and try again !!', 'campign_id' =>'0']);
        }

        /* Save Campign NewsLetter Lists */

        if (isset($input['campign_newsletter_lists'])) {
            EmailMarketingHelper::updateNewsLetterCampign($company_id, $campignId, $input['campign_newsletter_lists']);
        }

        if (isset($input['save_type']) && $input['save_type'] == 'sendmail') {
            $status = EmailMarketingHelper::sendCampaignMail($input, $company_id, $campignId, $user);
        } elseif (isset($input['save_type']) && $input['save_type'] == 'inprogress' && isset($input['campign_newsletter_lists'])) {
            $input['status'] = 2; //In progress
            if ($campignId != null) {
                $status = EmailMarketingHelper::updatedCampaignLog($company_id, $campignId, $input['campign_newsletter_lists'], 2);
                EmailMarketingHelper::updateCampaignStatus($campignId, 4);
                if ($status == false) {
                    return response()->success(['status' => 'false', 'message' => "Api key not set, unable to send mail", 'campign_id' => $campignId]);
                }
            }
        } elseif (isset($input['save_type']) && $input['save_type'] == 'scheduled' && isset($input['campign_newsletter_lists'])) {
            $input['status'] = 3; //scheduled
            //EmailMarketingHelper::updatedCampaignLog($company_id,$campignId,$input['campign_newsletter_lists'],3);
        }

        return response()->success(['status' => 'success', 'campign_id' => $campignId]);
    }

    public function postCampaigns()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $allCampaigns = EmailMarketingHelper::FetchAllCampaigns($company_id);
        return Datatables::of($allCampaigns)->make(true);
    }

    public function getShowCampaign($campaignId = null)
    {
		$user = Auth::user();
        $company_id = $user->company_id;
        if ($campaignId == null) {
            return response()->error('Campaign id is required.');
        }
        $campaign = EmailMarketingHelper::getCampign($campaignId);
		if(!is_array($campaign) || $campaign['company_id']!=$company_id ){
			return response()->error('Invalid Campaign ID');
		}
        $campaign_newsletter_lists = EmailMarketingHelper::getCompignEmailList($campaignId);
        $campaign['campign_newsletter_lists'] = $campaign_newsletter_lists;
        return response()->success(compact('campaign'));
    }

    public function deleteCampaign($campaign_id = null)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        if ($company_id && $campaign_id) {
            EmailMarketingHelper::deleteCampaign($company_id, $campaign_id);
            return response()->success('Campaign has been deleted successfully');
        }
        return response()->success('Something went wrong !!');
    }

    public static function campaignMailJob()
    {
        EmailMarketingHelper::fetchAllCampaignsForMail();
    }

    public function getCampaignDetails($campaign_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = EmailMarketingHelper::campaign_detail_statics($campaign_id);
		if(!isset($data['campaign']['company_id']) || $data['campaign']['company_id']!=$company_id ){
			return response()->error('Invalid Campaign ID');
		}
        return response()->success($data);
    }

    public function postCampaignContactList($list_id)
    {
        $data = Input::get();
        if (!isset($data['camp_id']) && empty($data['camp_id'])) {
            return response()->error('Campaign id not found !!');
        }
        $camp_id = $data['camp_id'];

        $user = Auth::user();
        $company_id = $user->company_id;
        $data = EmailMarketingHelper::campaign_detail_lists($company_id, $list_id, $camp_id);
        return Datatables::of($data)->make(true);
    }

    public function postDeleteListUser()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $req = Input::all();
        if (empty($req) && empty($req['contact_id']) && empty($req['list_id'])) {
            return response()->error('Invalid requested parameter !!');
        }

        $data = EmailMarketingHelper::DeleteUserFromList($company_id, $req['list_id'], $req['contact_id']);
        if ($data) {
            return response()->success('Contact has been removed from list successfully');
        }
        return response()->error('Something went wrong with delete contact!!');
    }

    public function getCampaignsStat($campaign_id = null)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        if (!empty($campaign_id)) {
            $response = EmailMarketingHelper::AllCampaignStatById($company_id, $campaign_id);
        } else {
            $response = EmailMarketingHelper::AllCampaignStatByCompanyId($company_id);
        }

        return response()->success($response);
    }

    public function deleteList($list_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        EmailMarketingHelper::deleteList($list_id, $company_id);
        return response()->success('List Deleted Successfully');
    }

    public function postEditList($list_id)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $data = Input::get();
        //dd($data);
        EmailMarketingHelper::updateList($company_id, $list_id, $data);
    }

    public function postSendMail()
    {
        EmailMarketingHelper::fetchAllCampaignsForMail();
    }

    public function SendGridWebHookResponse()
    {
        $webhook_data = Input::get();
        EmailMarketingHelper::updateEmailLog($webhook_data);
    }

    public function getBeefreeCredentials()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $beeObject = new BeeFreeHelper('a8644d77-f36f-4681-ad90-5974cc4e1ff5', 'db7DjpQHoIziuWqTSy3OEGYTAhcVQjwWh20AFgM78nFa38Z5KR6');
        $result = $beeObject->getCredentials();
        return response()->success($result);
    }

    public function postTemplate($template_id = null)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        EmailMarketingHelper::SaveTemplate($company_id, $input_data, $template_id);
        return response()->success('Template saved successfully');
    }

    public function getTemplates($type = 1)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $templates = EmailMarketingHelper::listTemplates($company_id, $type);
        return response()->success($templates);
    }

    public function postSubscribed(Request $request, $subscription_id)
    {
        $api_key = $request->header('api_key');
        if (empty($api_key)) {
            $api_key = EmailMarketingHelper::getCompanyApiKey($subscription_id);
        }
        if (!empty($api_key)) {
            $company_id = CompanyHelper::getCompanyID($api_key);
            if ($company_id) {
                $input_data = Input::get();
                $response = EmailMarketingHelper::WebUserSubscription($input_data, $company_id, $api_key, $subscription_id);
                if ($response) {
                    return response()->success('You have subscribed successfully');
                } else {
                    return response()->error('Invalid subscription list');
                }
            }
            return response()->success('Invalid Company !!');
        }
        return response()->success('Api key required !!');
    }

    public function getSubscriptionList(Request $request)
    {
        $api_key = $request->header('api_key');
        if (!empty($api_key)) {
            $company_id = CompanyHelper::getCompanyID($api_key);
            if ($company_id) {
                $lists = EmailMarketingHelper::SubscriptionsList($company_id);
                return response()->success($lists);
            }
            return response()->success('Invalid Company');
        }
    }
    public function templatePreview($template_id)
    {
        $template = EmailMarketingHelper::fetchTemplateById($template_id);
        return view('/preview_template/preview', compact('template'));
    }

    public function postTemplatePreview()
    {
        EmailMarketingHelper::generatePreview('template-preview', 1, 25, 'asdfasdf.png');
    }

    public function postContactSubscribe()
    {
        $input_data = Input::get();
        if ($input_data) {
            //$list could be array or single value
            $user = Auth::user();
            $company_id = $user->company_id;
            $contact_id = $input_data['contact_id'];
            $list = $input_data['list'];
            if (!empty($contact_id) && !empty($list)) {
                EmailMarketingHelper::UserSubscribe($list, $contact_id, $company_id);
                return response()->success('User has been subscribed list');
            }
        }
        return response()->error('Invalid Request !!');
    }

    public function Unsubscribe($uuid)
    {
        EmailMarketingHelper::Unsubscribe($uuid);
        return view('/unsubscribe');
    }

    public function getApiKeyStatus()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $company = \App\CompanySetting::where('company_id', $company_id)->where('name', 'sendgrid_api_key')->first();
        if ($company) {
            $company = $company->toArray();
            if (isset($company['name']) && !empty($company['value'])) {
                return response()->success('Api key avialable');
            }
        }
        return response()->error('No Api key set yet');
    }

    public function postCloneCampaign()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input_data = Input::get();
        if (isset($input_data['campaign_id']) && !empty($input_data)) {
            $camp_id = EmailMarketingHelper::cloneCamp($company_id, $input_data['campaign_id']);
            if ($camp_id) {
                return response()->success(array('message' => 'Campaign has been clone successfully', 'campaign_id' => $camp_id));
            }
            return response()->error(array('message' => 'Something went wrong !!'));
        }
        return response()->error(array('message' => 'Something went wrong !!'));
    }

    public function getCampaignStatics()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $start_date = '';
        $end_date = '';
        $count = 10;
        if (Input::get('start_date') && Input::get('end_date')) {
            $start_date = date('Y-m-d', strtotime(Input::get('start_date')));
            $end_date = date('Y-m-d', strtotime(Input::get('end_date') . ' +1 day')); // Increment one day.
            $count = Input::get('count');
        }

        $listing = EmailMarketingHelper::GetCampaignStatics($company_id, $start_date, $end_date, $count);
        return response()->success($listing);
    }

    public function postSelectedEmailList()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $listarray = Input::get();
        $email_list = \App\EmNewsletterList::select('id', 'name')->where('company_id', $company_id)->whereIn('id', $listarray)->get();
        return response()->success($email_list);
    }

    public function getUnsubscribeContact($baseid)
    {
        $contact =  base64_decode($baseid);
        $parameters = explode('&', $contact);
        if (count($parameters)==2) {
            EmailMarketingHelper::enableDndForContact($parameters);
            return view('/unsubscribe');
        }
        return view('/unsuberror');
    }
}
