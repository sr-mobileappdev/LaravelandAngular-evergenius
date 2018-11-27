<?php
namespace App\Classes;

use Anam\PhantomLinux\Path;
use App\Classes\ActivityHelper;
use App\Classes\CompanyHelper;
use App\Classes\LeadHelper;
use App\Classes\UserHelper;
use App\Company;
use App\CompanySetting;
use App\CompanyTemplate;
use App\Contact;
use App\EmActionFunnelQueue;
use App\EmCampaign;
use App\EmCampaignLog;
use App\EmCampaignNewsletterList;
use App\EmFunnel;
use App\EmFunnelList;
use App\EmListStatus;
use App\EmNewsletterContact;
use App\EmNewsletterContacts;
use App\EmNewsletterList;
use Carbon\Carbon;
use DateTime;
use Ixudra\Curl\Facades\Curl;

class EmailMarketingHelper
{
    public static function getstatuses()
    {
        return EmListStatus::select(['id', 'name'])->get();
    }

    public static function updateContactStatus($id, $status)
    {
        EmNewsletterContacts::where('id', $id)->update(['status_id' => $status]);
        return true;
    }

    public static function getCountContactsByStatus($list_id, $status_id)
    {
        return EmNewsletterContacts::where(['list_id' => $list_id, 'status_id' => $status_id])
            ->count();
    }

    public static function findNewsLetterLists($search_name, $company_id = '')
    {
        $where = array();
        if ($company_id != '') {
            $w = array('company_id', '=', $company_id);
            array_push($where, $w);
        }
        if ($search_name == "") {
            $term = EmNewsletterList::where($where);
        } else {
            $term = EmNewsletterList::where('name', 'like', '%' . $search_name . '%');
            $term->where($where);
        }

        $term = $term->orderBy('created_at', 'desc')->get()->toArray();
        return $term;
    }

    public static function CreateCampaign($input, $company_id, $campaign_id)
    {
        if (empty($input['name']) && empty($input['status']) && empty($input['from_name']) && empty($input['from_email'])) {
            return false;
        }

        if ($campaign_id == null) {
            $campign = new EmCampaign;
        } else {
            $campign = EmCampaign::find($campaign_id);
        }

        $campign->company_id = $company_id;
        if (isset($input['name'])) {
            $campign->name = $input['name'];
        }

        if (isset($input['status'])) {
            $campign->status = $input['status'];
        }

        if (isset($input['from_name'])) {
            $campign->from_name = $input['from_name'];
        }

        if (isset($input['from_email'])) {
            $campign->from_email = '';
        }

        if (isset($input['reply_email'])) {
            $campign->reply_email = '';
        }

        if (isset($input['subject'])) {
            $campign->subject = $input['subject'];
        }

        if (isset($input['body'])) {
            $campign->body = $input['body'];
        }

        if (isset($input['json_body'])) {
            $campign->json_body = $input['json_body'];
        }
        if (isset($input['query_string'])) {
            $campign->query_string = $input['query_string'];
        }

        if (isset($input['sender_id'])) {
            $campign->sender_id = $input['sender_id'];
        }

        if (isset($input['test_email'])) {
            $campign->test_email = $input['test_email'];
        }
        if (isset($input['template_id'])) {
            $campign->template_id = $input['template_id'];
        }
        if (isset($input['schedule_datetime'])) {
            $schedule_time = null;
            if (!empty($input['schedule_datetime'])) {
                $company_timezone = CompanyHelper::getCompanyTimezone($company_id);
                $schedule_time = CompanyHelper::AnyTimeZoneToUTC($input['schedule_datetime'], $company_timezone);
            }
            $campign->schedule_datetime = $schedule_time;
        }

        $campign->created_at = new DateTime();
        $campign->save();
        return $campign->id;
    }

    public static function dumpAllNewsletterListCamign($company_id, $campign_id)
    {
        EmCampaignNewsletterList::where(['campaign_id' => $campign_id])->delete();
        return true;
    }

    public static function updateNewsLetterCampign($company_id, $campign_id, $newsletter_list)
    {
        EmailMarketingHelper::dumpAllNewsletterListCamign($company_id, $campign_id);
        $ins = [];
        foreach ($newsletter_list as $key => $list) {
            $ins[] = array('campaign_id' => $campign_id, 'list_id' => $list['id']);
        }
        EmCampaignNewsletterList::insert($ins);
        return true;
    }

    public static function getCampign($campign_id)
    {
        $campign = EmCampaign::find($campign_id);
        if (count($campign) > 0) {
            return $campign->toArray();
        }
        return true;
    }

    public static function FetchAllCampaigns($company_id)
    {
        $campign = EmCampaign::select('id', 'company_id', 'name', 'status', 'schedule_datetime', 'sent_at', 'created_at', 'updated_at', 'deleted_at')->with('status')->withCount('clicks', 'opened', 'bounced', 'spammed', 'sent')->where('company_id', $company_id)->orderBy('created_at', 'desc')->get();
        return $campign;
    }

    public static function getCompignEmailList($campign_id)
    {
        $list_email_list = EmCampaignNewsletterList::select(['em_campaign_newsletter_lists.list_id as id', 'em_newsletter_lists.name as title'])
            ->join('em_newsletter_lists', 'em_newsletter_lists.id', '=', 'em_campaign_newsletter_lists.list_id')
            ->where('em_campaign_newsletter_lists.campaign_id', $campign_id)
            ->get();
        if (count($list_email_list) > 0) {
            return $list_email_list->toArray();
        }
        return [];
    }

    public static function sendCampaignMail($input, $company_id, $campaign_id, $user)
    {
        $status = false;
        if (isset($input['subject']) && isset($input['test_email'])) {
            $subject = $input['subject'];
            $test_mail = $input['test_email'];
            $from_email = self::fetchCompanyEmail($company_id);
            $from_name = self::fetchCompanyName($company_id, $campaign_id);
            $company = CompanyHelper::getCompanyDetais($company_id);
            if ($company) {
                $message = $input['body'];
                $message = str_replace('{$client_name}', $from_name, $message);
                $message = str_replace('{$location}', $company['address'], $message);
                $message = str_replace('{$office_phone}', $company['phone'], $message);
                $message = str_replace('{$website_link}', $company['site_url'], $message);
                $message = str_replace('{$first_name}', $user->name, $message);
                $message = str_replace('{$last_name}', '', $message);
                $message = str_replace('{$unsubscribe_link}', '<a href="' . getenv('API_URL') . '">Unsubscribe</a>', $message);
                $data['content_data'] = $message;
                $token = self::fetchSendGridApiKey($company_id);
                $status = self::MailViaSendGrid($data, $test_mail, $from_email, $subject, $token, $company_id, $from_name);
            }
        }
        return $status;
    }

    public static function updatedCampaignLog($company_id, $campignId, $lists, $status)
    {
        $i = 0;
        $item_array = array();
        $key_exists = self::ApikeyExists($company_id);
        if ($key_exists) {
            foreach ($lists as $item) {
                $data = EmNewsletterContacts::with('contacts')->has('contacts')->where('company_id', $company_id)->where('list_id', $item['id'])->get();
                if ($data) {
                    $data = $data->toArray();
                    foreach ($data as $ctc) {
                        if (!empty($ctc['contacts']) && !empty(trim($ctc['contacts']['email']))) {
                            $item_array[$i]['company_id'] = $company_id;
                            $item_array[$i]['campaign_id'] = $campignId;
                            $item_array[$i]['list_id'] = $item['id'];
                            $item_array[$i]['contact_id'] = $ctc['contact_id'];
                            $item_array[$i]['email_id'] = $ctc['contacts']['email'];
                            $item_array[$i]['status'] = 1;
                            $item_array[$i]['open_status'] = 0;
                            $item_array[$i]['created_at'] = date('Y-m-d H:i:s');
                            $i++;
                        }
                    }
                }
            }
            EmCampaignLog::insert($item_array);
            return true;
        }
        return false;
    }

    public static function deleteCampaign($company_id, $campaign_id)
    {
        EmCampaign::where('id', $campaign_id)->where('company_id', $company_id)->delete();
        EmCampaignLog::where('campaign_id', $campaign_id)->where('company_id', $company_id)->delete();
        EmCampaignNewsletterList::where('campaign_id', $campaign_id)->delete();
    }

    public static function fetchAllCampaignsForMail()
    {
        $campaigns_data_array = array();
        $datetime = Carbon::now("UTC");
        $datetime = $datetime->format('Y-m-d H:i:s');
        // Fetch Schedulec Campaign and move them to in-progress
        $campaigns_scheduled = EmCampaign::with('elist.contactlist.contacts')->has('elist.contactlist.contacts')->withCount('apikeyexists')->where('status', '2')->where('schedule_datetime', '<=', $datetime)->get();
        $i = 0;
        if ($campaigns_scheduled->count() > 0) {
            $camaigns_data = $campaigns_scheduled->toArray();
            foreach ($camaigns_data as $campaign) { //Fetch Campaigns
                if ($campaign['apikeyexists_count'] > 0) { //If Api exists then add scheduled records to the log;
                    if (!empty($campaign['elist'])) {
                        // INSERT CONATCTS IN LIST TO QUEUE
                        foreach ($campaign['elist'] as $elist) { //Fetch campaign Newsletter lists
                            if (!empty($elist['contactlist'])) {
                                foreach ($elist['contactlist'] as $clist) { //Fetch Newsletter list contacts
                                    if (!empty($clist['contacts'])) {
                                        if ($clist['contacts']) {
                                            if (!empty(trim($clist['contacts']['email']))) {
                                                $campaigns_data_array[$i]['company_id'] = $campaign['company_id'];
                                                $campaigns_data_array[$i]['list_id'] = $clist['list_id'];
                                                $campaigns_data_array[$i]['campaign_id'] = $campaign['id'];
                                                $campaigns_data_array[$i]['contact_id'] = $clist['contacts']['id'];
                                                $campaigns_data_array[$i]['email_id'] = $clist['contacts']['email'];
                                                $campaigns_data_array[$i]['status'] = 1;
                                                $campaigns_data_array[$i]['open_status'] = 0;
                                                $campaigns_data_array[$i]['response_id'] = 0;
                                                $campaigns_data_array[$i]['created_at'] = date('Y-m-d H:i:s');
                                                $i++;
                                            }
                                        }
                                    }
                                }
                            }

                            //UPDATE CAMPAIGN STSTUS TO IN PROGRESS
                            $cmp = EmCampaign::find($campaign['id']);
                            $cmp->status = 4;
                            $status = $cmp->save(); // update campaign status
                        }
                    }
                } //keyexists
            }

            EmCampaignLog::insert($campaigns_data_array); // Update log
        }
        //1-pending, 2-sent, 3-error
        //(1 - draft, 2- scheduled, 3 - Sent, 4 - in-progress)
        /*-------Fetch Pending Records-----------*/

        // PROCESS IN PROGRESS Campaigns and QUEUED EMAILS

        $campaign_list = EmCampaign::with('logs')->where('status', '4')->whereNotNull('body')->where('body', '<>', '')->get();
        $rejected = 0;

        $wi = 0;
        if ($campaign_list) {
            $campaign_list = $campaign_list->toArray();
            foreach ($campaign_list as $camp) {
                // GET SMTP API KEY FOR COMPANY
                $token = self::fetchSendGridApiKey($camp['company_id']);
                if (!empty($token)) {
                    $from_email = self::fetchCompanyEmail($camp['company_id']);

                    if (empty($camp['from_name'])) {
                        $from_name = self::fetchCompanyName($camp['company_id']);
                    } else {
                        $from_name = $camp['from_name'];
                    }

                    if ($camp) {
                        if (!empty($camp['logs'])) {
                            $subject = $camp['subject'];
                            foreach ($camp['logs'] as $item) {
                                $contact = EmNewsletterContacts::with('contact.company')->select('contact_id', 'id', 'uuid')->where('list_id', $item['list_id'])->where('contact_id', $item['contact_id'])->where('status_id', '=', '1')->first();
                                if ($contact) {
                                    $data['content_data'] = "";
                                    $message = $camp['body'];
                                    $user_contact = $contact->toArray();
                                    $message = self::renderTags($message, $user_contact);
                                    $data['content_data'] = $message;
                                    $logid = $item['id'];
                                    $user_email = $item['email_id'];
                                    if (!empty($user_email)) {
                                        $status = 1; // SENT
                                        $company_id = $item['company_id'];
                                        $response_id = self::MailViaSendGrid($data, $user_email, $from_email, $subject, $token, $company_id, $from_name);
                                        if ($response_id) {
                                            $wi++;
                                            EmCampaignLog::where('id', $logid)->update(['status' => '2', 'response_id' => $response_id, 'updated_at' => $datetime]);
                                        } else {
                                            $rejected++;

                                            EmCampaignLog::where('id', $logid)->update(['status' => '3', 'response_id' => $response_id, 'updated_at' => $datetime]);
                                        }
                                    } else {
                                        $rejected++;
                                        EmCampaignLog::where('id', $logid)->update(['status' => '3', 'response_id' => 0, 'updated_at' => $datetime]);
                                    }
                                } else {
                                    $rejected++;
                                    EmCampaignLog::where('id', $item['id'])->update(['status' => '3', 'response_id' => 0, 'updated_at' => $datetime]);
                                }
                            }
                        }

                        $pending_count = EmCampaignLog::where('campaign_id', $camp['id'])
                        ->where('status', 1)
                        ->count();

                        $not_pending_count = EmCampaignLog::where('campaign_id', $camp['id'])
                            ->where('status','<>', 1)
                            ->count();

                        if($pending_count == 0 && $not_pending_count > 0) {
                            EmCampaign::where('id', $camp['id'])->update(['status' => 3, 'updated_at' => $datetime, 'sent_at' => $datetime]);
                        }
                    }
                }
            }
        }

        \Log::info("Email Campaign Scheduler run UTC : " . $datetime);
        \Log::info("Email Campaign Sent : " . $wi);
        \Log::info("Email Rejected/Incorrect : " . $rejected);
        print_r("Email Rejected/Incorrect : " . $rejected);
        echo "<br/>";
        print_r("Total Email Sent: " . $wi);
    }

    public static function getListNameById($list_id)
    {
        $d = EmNewsletterList::select('name', 'id', 'unique_id')->where('id', $list_id)->first()->toArray();
        if ($d) {
            return array('name' => $d['name'], 'id' => $d['id'], 'unique_id' => $d['unique_id']);
        }
        $a = "";
        return $a;
    }

    public static function campaign_detail_statics($campaign_id)
    {
        if (!$campaign_id) {
            return reponse()->error('Campaign Id not found');
        }
        $campaign = EmCampaign::with('elist.detail')->where('id', $campaign_id)->first()->toArray();
        $pending = EmCampaignLog::where('campaign_id', $campaign_id)->where('status', '=', '1')->count();
        $sent = EmCampaignLog::where('campaign_id', $campaign_id)->where('status', '=', '2')->count();
        $error = EmCampaignLog::where('campaign_id', $campaign_id)->where('status', '=', '3')->count();
        if ($campaign) {
            return array('campaign' => $campaign, 'pending' => $pending, 'sent' => $sent, 'error' => $error);
        }
        return array('campaign' => array(), 'pending' => $pending, 'sent' => $sent, 'error' => $error);
    }

    public static function campaign_detail_lists($company_id, $list_id, $campaign_id)
    {
        $contact_list = EmCampaignLog::where('list_id', $list_id)->whereRaw('email_id <> ""')->where('company_id', $company_id)->where('campaign_id', $campaign_id)->get();
        return $contact_list;
    }

    public static function DeleteUserFromList($company_id, $list_id, $contact_id)
    {
        EmNewsletterContacts::where('company_id', $company_id)
            ->where('list_id', $list_id)
            ->where('contact_id', $contact_id)
            ->delete();
        EmCampaignLog::where('company_id', $company_id)->where('contact_id', $contact_id)->delete();
        EmActionFunnelQueue::where('contact_id', $contact_id)->delete();
        return true;
    }

    private static function initCampaignMail($template, $data, $user_email, $from_email, $subject)
    {
        $status = \Mail::send(
            $template,
            compact('data'),
            function ($mail) use ($user_email, $from_email, $subject) {
                $mail->to($user_email)->from($from_email)->subject($subject);
            }
        );
        return $status;
    }

    public static function AllCampaignStatByCompanyId($company_id)
    {
        $total_sent = EmCampaignLog::where('company_id', $company_id)->where('status', '2')->count();
        $total_clicked = EmCampaignLog::where('company_id', $company_id)->where('click_status', '1')->count();
        $total_unsub = EmNewsletterContacts::where('company_id', $company_id)->where('status_id', '2')->count();
        return array('total_sent' => $total_sent, 'total_clicked' => $total_clicked, 'total_unsub' => $total_unsub);
    }

    public static function AllCampaignStatById($company_id, $campaign_id)
    {
        $total_sent =   EmCampaignLog::distinct()->where('company_id', $company_id)->where('campaign_id', $campaign_id)->where('status', '2')->count('contact_id');
        $total_clicked = EmCampaignLog::where('company_id', $company_id)->where('campaign_id', $campaign_id)->where('click_status', '1')->count();
        $total_opened = EmCampaignLog::where('company_id', $company_id)->where('campaign_id', $campaign_id)->where('open_status', '1')->count();
        $total_spamed = EmCampaignLog::where('company_id', $company_id)->where('campaign_id', $campaign_id)->where('spam_status', '1')->count();
        $total_bounced = EmCampaignLog::where('company_id', $company_id)->where('campaign_id', $campaign_id)->where('bounce_status', '1')->count();
        $total_sub = EmNewsletterContacts::where('company_id', $company_id)
            ->whereIn(
                'list_id',
                function ($query) use ($campaign_id) {
                    $query->select('list_id')
                        ->from(with(new EmCampaignNewsletterList)->getTable())
                        ->where('campaign_id', $campaign_id);
                }
            )
            ->where('status_id', '1')
            ->count();

        $total_unsub = EmNewsletterContacts::where('company_id', $company_id)
            ->whereIn(
                'list_id',
                function ($query) use ($campaign_id) {
                    $query->select('list_id')
                        ->from(with(new EmCampaignNewsletterList)->getTable())
                        ->where('campaign_id', $campaign_id);
                }
            )
            ->where('status_id', '2')
            ->count();
        return array('total_sent' => $total_sent, 'total_sub' => $total_sub, 'total_unsub' => $total_unsub, 'total_clicked' => $total_clicked, 'total_opened' => $total_opened, 'total_spamed' => $total_spamed, 'total_bounced' => $total_bounced);
    }

    public static function deleteList($list_id, $company_id)
    {
        EmNewsletterList::where('id', $list_id)->where('company_id', $company_id)->delete();
        EmCampaignNewsletterList::where('list_id', $list_id)->delete();
        EmFunnelList::where('list_id', $list_id)->delete();
    }

    public static function updateList($company_id, $list_id, $data)
    {
        EmNewsletterList::where('company_id', $company_id)->where('id', $list_id)->update(['name' => $data['name']]);
        return true;
    }

    public static function getSentCountByListId($list_id)
    {
        $a = \DB::table('em_campaign_newsletter_lists')
            ->join('em_campaign_log', 'em_campaign_newsletter_lists.campaign_id', '=', 'em_campaign_log.campaign_id')
            ->join('em_newsletter_contacts', 'em_campaign_log.contact_id', '=', 'em_newsletter_contacts.contact_id')
            ->where('em_campaign_newsletter_lists.list_id', $list_id)
            ->count();
        return $a;
    }

    public static function MailViaSendGrid($data, $user_email, $from_email = false, $subject, $token, $company_id, $from_name = "")
    {
        $message_id = false;
        if (empty($from_email)) {
            $from_email = self::fetchCompanyEmail($company_id);
        }
        if (!empty($token)) {
            $data = self::generateEmailContent($data, $user_email, $from_email, $subject, $from_name);
            $response = Curl::to('https://api.sendgrid.com/v3/mail/send')
                ->withData($data)
                ->withHeader('Authorization: Bearer ' . $token)
                ->asJson(true)
                ->withResponseHeaders()
                ->returnResponseObject()
                ->post();
            if (!empty($response) && isset($response->headers) && !empty($response->headers)) {
                $headers = $response->headers;
                if (isset($headers['X-Message-Id'])) {
                    $message_id = $headers['X-Message-Id'];
                }
            }
            return $message_id;
        }
        return $message_id;
    }

    public static function generateEmailContent($data = "", $user_email = "", $from_email = "", $subject = "", $from_name = "")
    {
        return array(
            "personalizations" => [array("to" => [array("email" => $user_email)], "subject" => $subject)],
            "from" => array("email" => $from_email, 'name' => $from_name),
            "content" => [array("type" => "text/html", "value" => $data['content_data'])],
            "tracking_settings" => array("enabled" => true),
        );
    }

    public static function updateEmailLog($webhook_data)
    {
        //\Log::info($webhook_data);
        foreach ($webhook_data as $response) {
            $sg_message_id = $response['sg_message_id'];
            $response_id = current(explode('.', $sg_message_id));
            if ($response['event'] == 'open') {
                $status_arr['open_status'] = 1;
                self::updateEmailStatus($response_id, $status_arr);
            }
            if ($response['event'] == 'click') {
                $status_arr['click_status'] = 1;
                self::updateEmailStatus($response_id, $status_arr);
            }
            if ($response['event'] == 'bounce') {
                $status_arr['bounce_status'] = 1;
                self::updateEmailStatus($response_id, $status_arr);
            }
            if ($response['event'] == 'spamreport') {
                $status_arr['spam_status'] = 1;
                self::updateEmailStatus($response_id, $status_arr);
            }
        }
    }

    public static function updateEmailStatus($response_id, $status_arr)
    {
        $found_in_log = EmCampaignLog::where('response_id', $response_id)->update($status_arr);
        if (!$found_in_log) {
            EmActionFunnelQueue::where('response_id', $response_id)->update($status_arr);
        }
    }
    public static function fetchSendGridApiKey($company_id)
    {
        $company_data = CompanySetting::where('company_id', $company_id)->where('name', 'sendgrid_api_key')->get();
        if ($company_data->count() > 0) {
            $company_data = $company_data->toArray();
            $key = array_search($company_id, array_column($company_data, 'company_id'));
            $token = $company_data[$key]['value'];
            return $token;
        }
        return false;
    }

    /**
     * @param $company_id
     * @return array|false|string
     */
    public static function fetchCompanyEmail($company_id)
    {
        $company = CompanySetting::where('company_id', $company_id)->where('name', 'em_from_email')->select('value')->first();
        if (!empty($company->value)) {
            return $company->value;
        } elseif (empty($company->value)) {
            $company = Company::where('id', $company_id)->select('email')->first();
            if (!empty($company)) {
                return $company->email;
            }
        }
        return getenv('CAMP_MAIL');
    }

    public static function fetchCompanyName($company_id, $campaign_id = false)
    {
        if ($campaign_id != false) {
            $camp = EmCampaign::select('from_name')->where('id', $campaign_id)->first();
            if (isset($camp->from_name)) {
                return $camp->from_name;
            }
        }
        $company = CompanySetting::where('company_id', $company_id)->where('name', 'em_from_name')->select('value')->first();
        if (!empty($company->value)) {
            return $company->value;
        } elseif (empty($company->value)) {
            $company = Company::where('id', $company_id)->select('name')->first();
            if (!empty($company->name)) {
                return $company->name;
            }
        }
        return 'Evergenius';
    }

    public static function SaveTemplate($company_id, $input_data, $template_id)
    {
        if (isset($input_data['json_body']) && isset($input_data['html_body'])) {
            if ($template_id) {
                $company_template = CompanyTemplate::find($template_id);
                $preview_image_name = $company_template->preview_image;
            } else {
                $company_template = new CompanyTemplate;
                $preview_image_name = uniqid() . '.png';
            }
            $company_template->json_body = $input_data['json_body'];
            $company_template->html_body = $input_data['html_body'];
            $company_template->title = $input_data['title'];
            $company_template->category = $input_data['type'] != '' ? $input_data['type'] : 1;
            $company_template->company_id = $company_id;
            $company_template->preview_image = $preview_image_name;
            $status = $company_template->save();
            ##GENERATE PREVIEW##
            if ($company_template->id) {
                self::generatePreview('template-preview', $company_id, $company_template->id, $preview_image_name);
            }

            if ($status) {
                return true;
            }
            return false;
        }
    }

    public static function listTemplates($company_id, $type = 1)
    {
        $templates = CompanyTemplate::where('category', $type)->Where(
            function ($query) use ($company_id) {
                $query->where('company_id', $company_id)
                    ->orWhereNull('company_id');
            }
        )->get();
        return $templates;
    }

    public static function UserSubscribe($list, $contact_id, $company_id)
    {
        $list_array_a = array();
        $list_array_b = array();
        if (is_array($list)) {
            $assign_lists = EmNewsletterContact::where('contact_id', '=', $contact_id)->where('status_id', '=', '1')->get();
            if ($assign_lists->count() > 0) {
                foreach ($assign_lists as $item) {
                    $list_array_a[] = $item['list_id'];
                }
                foreach ($list as $item) {
                    $list_array_b[] = $item['id'];
                }
                $unsubscribe_list = array_diff($list_array_a, $list_array_b);
                $status = EmNewsletterContact::whereIn('list_id', $unsubscribe_list)->where('contact_id', '=', $contact_id)
                    ->update(['status_id' => '2']);
            }
            foreach ($list as $item) {
                self::AddUserToList($company_id, $item['id'], $contact_id);
            }
        } else {
            self::AddUserToList($company_id, $list->id, $contact_id);
        }
    }
    public static function UserSubscribeContact($list, $contact_id, $company_id, $add_to_contact = false)
    {
        if ($add_to_contact == true) {
            if (is_array($list)) {
                foreach ($list as $item) {
                    self::AddUserToList($company_id, $item['id'], $contact_id);
                }
            } else {
                self::AddUserToList($company_id, $list['id'], $contact_id);
            }
        } else {
            if (is_array($list)) {
                foreach ($list as $item) {
                    self::AddUserToList($company_id, $item, $contact_id);
                }
            } else {
                self::AddUserToList($company_id, $list, $contact_id);
            }
        }
    }

    public static function AddUserToList($company_id, $list_id, $contact_id)
    {
        //Add user to subscription list
        $contact_obj = EmNewsletterContact::where('company_id', $company_id)->where('list_id', $list_id)->where('contact_id', $contact_id)->first();

        if (!$contact_obj) {
            //EmNewsletterContact::where('company_id',$company_id)->where('list_id',$list_id)->where('contact_id',$contact_id)->delete();
            if (!empty($list_id)) {
                EmNewsletterContact::insert(array('company_id' => $company_id, 'list_id' => $list_id, 'status_id' => '1', 'contact_id' => $contact_id, 'created_at' => date('Y-m-d H:i:s')));
            }
        } elseif ($contact_obj) {
            if ($contact_obj->status_id == 2) {
                EmNewsletterContact::where('company_id', $company_id)->where('list_id', $list_id)->where('contact_id', '=', $contact_id)->update(['status_id' => '1']);
            }
        }
    }
    public static function SubscriptionsList($company_id)
    {
        $result = EmNewsletterList::select('name', 'unique_id')->where('company_id', $company_id)->get();
        if ($result->count() > 0) {
            $result = $result->toArray();
            return $result;
        }
        return false;
    }
    public static function getListIdbyUniqueId($uniqid)
    {
        $EmNewsletterList = EmNewsletterList::select('id')->where('unique_id', $uniqid)->first();
        if ($EmNewsletterList) {
            return $EmNewsletterList->id;
        }
        return false;
    }

    public static function generatePreview($folder_name, $company_id, $template_id, $image_name)
    {
        $image_array = self::generateTemplatePath($folder_name, $company_id, $image_name);
        $image_path = $image_array['path'];
        $image_name = $image_array['name'];
        if (!empty($image_path) && !empty($image_name)) {
            try {
                $conv = new \Anam\PhantomMagick\Converter();
                if (getenv('GENERATEPREVIEW') == "dev1") {
                    $conv->setBinary(Path::binaryPath());
                } elseif (getenv('GENERATEPREVIEW') == "prod") {
                    $conv->setBinary('/var/www/vhosts/evergenius.com/app.evergenius.com/vendor/anam/phantomjs-linux-x86-binary/bin/phantomjs');
                } else {
                    $conv->setBinary('phantomjs');
                }
                $conv->width(600);
                $data = $conv->source(getenv('API_URL') . '/email-marketing/' . $folder_name . '/' . $template_id)->toPng()->save($image_path);
                return true;
            } catch (\Exception $e) {
                \Log::info('Error' . $e);
                return false;
            }
        }
    }

    private static function generateTemplatePath($folder_name, $company_id, $image_name)
    {
        $publicpath = public_path();
        $directoryPath = $publicpath . "/" . $folder_name . "/" . $company_id . "/";

        if (!file_exists($publicpath . "/" . $folder_name . "/")) {
            mkdir($publicpath . "/" . $folder_name . "/", 0755, true);
            // chmod($publicpath."/".$folder_name, 0755);
        }

        $image_path = $publicpath . "/" . $folder_name . "/" . $company_id . "/" . $image_name;
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true);
            //chmod($publicpath."/".$folder_name, 0755);
        }
        return array('path' => $image_path, 'name' => $image_name);
    }
    public static function fetchTemplateById($template_id)
    {
        $template = CompanyTemplate::where('id', $template_id)->first();
        $template = $template->toArray();
        return $template;
    }
    private static function convertDateFromTimezone($date, $timezone, $timezone_to, $format)
    {
        $date = new \DateTime('NOW', new \DateTimeZone($timezone_to));
        //$date->setTimezone(new \DateTimeZone($timezone_to));
        return $date->format($format);
    }
    public static function getSentCount($list_id)
    {
        $sent_count = EmCampaignLog::where('list_id', $list_id)->where('status', '2')->count();
        return $sent_count;
    }
    public static function Unsubscribe($uuid)
    {
        $status = EmNewsletterContacts::where('uuid', $uuid)->update(['status_id' => 2]);
        return $status;
    }
    public static function getSubscriptionList($id)
    {
        $data = EmNewsletterList::whereIn(
            'id',
            function ($query) use ($id) {
                $query->select('list_id')
                    ->from(with(new EmNewsletterContacts)->getTable())
                    ->where('contact_id', $id)
                    ->where('status_id', 1);
            }
        )->select(
            'name as title',
            'id'
        )
            ->get();
        if ($data->count() > 0) {
            return $data->toArray();
        }
        $data = array();
        return $data;
    }

    public static function cloneCamp($company_id, $campaign_id)
    {
        $campaign = EmCampaign::where('company_id', $company_id)->where('id', $campaign_id)->first();

        if ($campaign) {
            $campaign_arr['name'] = !empty($campaign->name) ? $campaign->name . "-copy" : "Campaign Clone";
            $campaign_arr['status'] = 1;
            $campaign_arr['company_id'] = $company_id;
            $campaign_arr['from_name'] = $campaign->from_name;
            $campaign_arr['from_email'] = $campaign->from_email;
            $campaign_arr['query_string'] = $campaign->query_string;
            $campaign_arr['subject'] = $campaign->subject;
            $campaign_arr['body'] = $campaign->body;
            $campaign_arr['json_body'] = $campaign->json_body;
            $campaign_arr['test_email'] = $campaign->test_email;
            $campaign_arr['schedule_datetime'] = $campaign->schedule_datetime;
            $campaign_arr['created_at'] = date('Y-m-d H:i:s');
            $camp_id = EmCampaign::insertGetId($campaign_arr); //Insert campaign record
            if ($camp_id) {
                $campaign_newsletter_lists = EmCampaignNewsletterList::where('campaign_id', $campaign_id)->get(); //fetch list of campaign
                if ($campaign_newsletter_lists->count() > 0) {
                    $i = 0;
                    $list_array = array();
                    foreach ($campaign_newsletter_lists as $list) {
                        $list_array[$i]['campaign_id'] = $camp_id;
                        $list_array[$i]['list_id'] = $list->list_id;
                        $i++;
                    }
                    EmCampaignNewsletterList::insert($list_array); //copy list to new clone campaign
                }
            }
            return $camp_id;
        }
    }

    public static function GetCampaignStatics($company_id, $start_date, $end_date, $count)
    {
        $camp_array = array();

        if (!empty($start_date) && !empty($end_date)) {
            $app_start_time = date('Y-m-d 00:00:00', strtotime($start_date));
            $app_end_time = date('Y-m-d 23:59:59', strtotime($end_date));
            $timeZone = CompanySettingsHelper::getSetting($company_id, 'timezone');
            if ($timeZone != '' && $timeZone != false) {
                $app_start_time = LeadHelper::convertToUtc($app_start_time, $timeZone, 'Y-m-d H:i:s');
                $app_end_time = LeadHelper::convertToUtc($app_end_time, $timeZone, 'Y-m-d H:i:s');
            }
        } else {
            $app_start_time = date('Y-m-d 00:00:00');
            $app_end_time = date('Y-m-d 23:59:59');
        }

        $campaigns = EmCampaign::select('id', 'company_id', 'name', 'status', 'created_at', 'updated_at', 'deleted_at')
            ->withCount('clicks', 'opened', 'bounced', 'spammed', 'sent')
            ->with('subscribed.contactlist')
            ->whereBetween('created_at', array($app_start_time, $app_end_time))
            ->where('company_id', $company_id)
            ->orderBy('sent_count', 'desc')
            ->take($count)
            ->get();
        if ($campaigns) {
            $campaigns = $campaigns->toArray();
            foreach ($campaigns as $camp) {
                $camp['clickper'] = self::CalculatePercentage($camp['clicks_count'], $camp['sent_count']);
                $camp['openper'] = self::CalculatePercentage($camp['opened_count'], $camp['sent_count']);
                if (!empty($camp['subscribed'])) {
                    foreach ($camp['subscribed'] as $subscribe_list) {
                        $camp['subscribed_count'] = count($subscribe_list['contactlist']);
                    }
                } else {
                    $camp['subscribed_count'] = 0;
                }
                unset($camp['subscribed']);
                $camp_array[] = $camp;
            }
        }

        return $camp_array;
    }

    public static function ApikeyExists($company_id)
    {
        $exists = 0;
        $exists = CompanySetting::where('company_id', $company_id)->where('name', 'sendgrid_api_key')->whereNotNull('value')->where('value', '<>', '')->count();
        return $exists;
    }

    public static function CalculatePercentage($clicks, $sent)
    {
        $perc = 0;
        if ($sent != 0) {
            $perc = ($clicks / $sent) * 100;
            $perc = number_format($perc, 2, '.', '');
        }
        return $perc;
    }

    public static function WebUserSubscription($input_data, $company_id, $api_key, $subscription_id)
    {
        if (isset($input_data['email'])) {
            $first_name = isset($input_data['first_name']) ? $input_data['first_name'] : '';
            $last_name = isset($input_data['last_name']) ? $input_data['last_name'] : '';
            $phone = isset($input_data['phone']) ? $input_data['phone'] : '';
            $contact_id = ContactHelper::storeContact($company_id, $first_name, $last_name, $input_data['email'], $phone, null, null, null, null, null, null, null, null, '');
            if (isset($input_data['message']) && !empty($input_data['message'])) {
                ContactHelper::attachContactNote($contact_id, $input_data['message']);
            }

            $list_id = EmailMarketingHelper::getListIdbyUniqueId($subscription_id);

            if ($list_id) {
                $response = EmailMarketingHelper::UserSubscribeContact($list_id, $contact_id, $company_id);
                $c_user_id = CompanyHelper::getCompanyUserID($api_key);
                $message = NotificationHelper::getNotificationMethod($company_id, 'mail', 'SUBSCRIPTION_MAIL');
                $subject = NotificationHelper::getNotificationSubject($company_id, 'mail', 'SUBSCRIPTION_MAIL');
                $list = EmailMarketingHelper::getListNameById($list_id);
                $list_name = '';

                if (is_array($list)) {
                    $list_name = $list['name'];
                }

                $input_data['list_name'] = $list_name;

                UserHelper::SendUserNotification($input_data, $message, $subject, $company_id);
                ActivityHelper::createActivity($company_id, 'LIST_SUBSCRIBE', 'list_subscribe', $list_id, $contact_id, $c_user_id, $c_user_id);
                return true;
            }
        }
        return false;
    }

    public static function getCompanyApiKey($subscription_id)
    {
        $company = EmNewsletterList::where('unique_id', $subscription_id)->first();
        if ($company) {
            $company = Company::find($company->company_id);
            if (!empty($company->api_key)) {
                return $company->api_key;
            }
            return false;
        }
        return false;
    }

    public static function cleanStr($string)
    {
        $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^0-9]/', '', $string); // Removes special chars.
    }
    public static function getListIdByName($nameList, $company_id)
    {
        $emailList = EmNewsletterList::select('id')->where(['name' => $nameList, 'company_id' => $company_id])->first();
        if (count($emailList) > 0) {
            return $emailList->id;
        }
        return false;
    }

    public static function addContactNewsletter($contact_id, $company_id)
    {
        $newsLetterId = self::getListIdByName('Newsletter', $company_id);
        if ($newsLetterId != false) {
            $response = self::UserSubscribeContact($newsLetterId, $contact_id, $company_id);
        }
        return true;
    }

    public static function renderTags($message, $contact)
    {
        if (isset($contact['contact']['company']['name'])) {
            $message = str_replace('{$client_name}', $contact['contact']['company']['name'], $message);
        } else {
            $message = str_replace('{$client_name}', '', $message);
        }
        if (isset($contact['contact']['company']['address'])) {
            $message = str_replace('{$location}', $contact['contact']['company']['address'], $message);
        } else {
            $message = str_replace('{$location}', '', $message);
        }
        if (isset($contact['contact']['company']['phone'])) {
            $message = str_replace('{$office_phone}', $contact['contact']['company']['phone'], $message);
        } else {
            $message = str_replace('{$office_phone}', '', $message);
        }
        if (isset($contact['contact']['company']['site_url'])) {
            $message = str_replace('{$website_link}', $contact['contact']['company']['site_url'], $message);
        } else {
            $message = str_replace('{$website_link}', '', $message);
        }
        if (isset($contact['contact']['first_name'])) {
            $message = str_replace('{$first_name}', $contact['contact']['first_name'], $message);
        } else {
            $message = str_replace('{$first_name}', '', $message);
        }

        if (isset($contact['contact']['last_name'])) {
            $message = str_replace('{$last_name}', $contact['contact']['last_name'], $message);
        } else {
            $message = str_replace('{$last_name}', '', $message);
        }

        if (isset($contact['uuid'])) {
            $unsubscribe_anchor = "<a href='" . getenv('API_URL') . "/unsubscribe/" . $contact['uuid'] . "'>unsubscribe</a>";
            $message = str_replace('{$unsubscribe_link}', $unsubscribe_anchor, $message);
        } else {
            $message = str_replace('{$unsubscribe_link}', '', $message);
        }
        return $message;
    }

    public static function enableDndForContact($parameters)
    {
        $contact_id = isset($parameters['0']) ? trim($parameters['0']) : '';
        $funnel_id = isset($parameters['1']) ? trim($parameters['1']) : '';
        if (!empty($contact_id) && !empty($funnel_id)) {
            $funnel = EmFunnel::where('id', '=', $funnel_id)->select('company_id')->first();
            if ($funnel) {
                $contact = Contact::where('company_id', '=', $funnel->company_id)->where('id', '=', $contact_id)->update(['dnd' => '1']);
                return true;
            }
        }
        return false;
    }

    public static function updateCampaignStatus($campId, $status)
    {
        EmCampaign::where('id', $campId)->update(['status' => $status]);
        return true;
    }
}
