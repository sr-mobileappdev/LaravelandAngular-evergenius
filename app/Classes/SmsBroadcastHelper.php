<?php
namespace App\Classes;

use App\Classes\CompanyHelper;
use App\Classes\SmsHelper;
use App\Company;
use App\CompanySetting;
use App\EmActionFunnelQueue;
use App\EmListStatus;
use App\EmNewsletterContact;
use App\EmNewsletterContacts;
use App\EmNewsletterList;
use App\EmSmsBroadcast;
use App\EmSmsBroadcastNewsletterList;
use App\EmSmsBroadcastLog;
use Carbon\Carbon;
use DateTime;
use Ixudra\Curl\Facades\Curl;

class SmsBroadcastHelper
{
    public static function FetchAllSmsBroadcasts($company_id)
    {
        $sms_broadcasts = EmSmsBroadcast::select('id', 'company_id', 'title', 'status', 'schedule_datetime', 'sent_at', 'created_at', 'updated_at', 'deleted_at')
        ->where('company_id', $company_id)
        ->orderBy('created_at', 'desc')->get();
        return $sms_broadcasts;
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

    public static function createSmsBroadcast($input, $company_id, $broadcast_id)
    {
        if (empty($input['title']) && empty($input['status']) && empty($input['from_number']) && empty($input['body'])) {
            return false;
        }
        
        if ($broadcast_id == null) {
            $campign = new EmSmsBroadcast;
        } else {
            $campign = EmSmsBroadcast::find($broadcast_id);
        }

        $campign->company_id = $company_id;
        if (isset($input['title'])) {
            $campign->title = trim($input['title']);
        }

        if (isset($input['status'])) {
            $campign->status = $input['status'];
        }

        if (isset($input['from_number'])) {
            $campign->from_number = format_phone_number(trim($input['from_number']));
        }

        if (isset($input['test_num_country_code'])) {
            $campign->test_num_country_code = trim($input['test_num_country_code']);
        }

        if (isset($input['body'])) {
            $campign->body = strip_tags(trim($input['body']));
        }

        if (isset($input['test_phone_number'])) {
            $campign->test_phone_number = format_phone_number(trim($input['test_phone_number']));
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
    
    public static function sendBroadcastMessage($input, $company_id, $broadcast_id)
    {
        $status = false;
        if (isset($input['body']) && isset($input['test_phone_number'])) {
            if (!empty($input['body']) && !empty($input['test_phone_number'])) {
                $contry_code = +1;
                if (isset($input['test_num_country_code'])) {
                    $contry_code  = trim($input['test_num_country_code']);
                }
                SmsHelper::sendSms(
                    trim($contry_code.format_phone_number(
                        $input['test_phone_number']
                    )),
                    $input['body'],
                    $company_id,
                    'sms_broadcast_notification',
                    ''
                );
                $status=true;
            }
        }
        return $status;
    }

    public static function dumpAllNewsletterListBroadcast($company_id, $broadcast_id)
    {
        EmSmsBroadcastNewsletterList::where(['sms_broadcast_id' => $broadcast_id])->delete();
        return true;
    }

    public static function updateNewsLetterBroadcast($company_id, $broadcast_id, $newsletter_list)
    {
        self::dumpAllNewsletterListBroadcast($company_id, $broadcast_id);
        $smsNewsLetterlists = getSmsListsFromInput($newsletter_list);
        $ins = [];
        foreach ($smsNewsLetterlists as $em_list) {
            $ins [] = ['sms_broadcast_id'=>$broadcast_id, 'list_id'=>$em_list];
        }
        EmSmsBroadcastNewsletterList::insert($ins);
        return true;
    }

    public static function getBroadcastById($broadcastId)
    {
        if ($broadcastId) {
            $sms_broadcast = EmSmsBroadcast::find($broadcastId);
            if (count($sms_broadcast)>0) {
                return $sms_broadcast->toArray();
            }
        }
        return false;
    }

    public static function sendSmsCampaign($company_id, $campignId, $status = 1)
    {
        $i = 0;
        $item_array = array();
        $twilio_enable = CompanySettingsHelper::isTwillioSetup($company_id);
        $campignNewsletters = self::getNewsletterListsArray($campignId);
        if ($twilio_enable && count($campignNewsletters)>0) {
            $campignNewsletters = array_flatten($campignNewsletters);
            //foreach ($lists as $item) {
            $data = EmNewsletterContacts::with('contact')->groupBy('contact_id')->where('company_id', $company_id)->whereIn('list_id', $campignNewsletters)->get();
            if ($data) {
                $data = $data->toArray();
                foreach ($data as $ctc) {
                    if (!empty($ctc['contact'])) {
                        $item_array[$i]['company_id'] = $company_id;
                        $item_array[$i]['sms_broadcast_id'] = $campignId;
                        $item_array[$i]['list_id'] = $ctc['list_id'];
                        $item_array[$i]['contact_id'] = $ctc['contact_id'];
                        $item_array[$i]['contact_number'] = $ctc['contact']['mobile_number'];
                        $item_array[$i]['status'] = $status;
                        //  $item_array[$i]['sent_at'] = 0;
                        $item_array[$i]['created_at'] = date('Y-m-d H:i:s');
                        $i++;
                    }
                }
            }
            EmSmsBroadcastLog::insert($item_array);
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

    

    public static function getCampignListNewsletterLists($campignId)
    {
        $out = [];
        $newsLetterLists = EmSmsBroadcastNewsletterList::with('newsletter')
        ->select('list_id')
        ->where('sms_broadcast_id', $campignId)
        ->get();
        if (count($newsLetterLists)>0) {
            $newsLetterLists = $newsLetterLists->toArray();
            foreach ($newsLetterLists as $list) {
                $out[] = ['id'=>$list['list_id'], 'title'=> $list['newsletter']['name']];
            }
        }
        return $out;
    }

    public static function getNewsletterListsArray($campignId)
    {
        $newsLetterLists = EmSmsBroadcastNewsletterList::select('list_id')
        ->where('sms_broadcast_id', $campignId)
        ->get();
        if (count($newsLetterLists)>0) {
            $newsLetterLists = $newsLetterLists->toArray();
            return $newsLetterLists;
        }
        return [];
    }

    public static function processScheduleCampigns()
    {
        $campaigns_data_array = array();
        $datetime = Carbon::now("UTC");
        $datetime = $datetime->format('Y-m-d H:i:s');
        // echo $datetime; die;
        $campaigns_scheduled = EmSmsBroadcast::select(['id','company_id'])->where('status', '2')->where('schedule_datetime', '<=', $datetime)->get();
        if ($campaigns_scheduled) {
            $campaigns_scheduled = $campaigns_scheduled->toarray();
            if (count($campaigns_scheduled)>0) {
                foreach ($campaigns_scheduled as $camp) {
                    self::sendSmsCampaign($camp['company_id'], $camp['id'], 1);
                    self::updateCampignStatus($camp['id'], 4);
                }
            }
        }
    }

    public static function updateCampignStatus($campignId, $status = 4)
    {
        EmSmsBroadcast::where('id', $campignId)
        ->update(['status'=>$status]);
        return true;
    }

    public static function processQueueSms()
    {
        //echo '<pre>';
        $campignContacts = self::getCampignLogContacts(); // Get Campign Log Contacts
        
        $logIds = array_column($campignContacts, 'id');
        /* Contacts */
        foreach ($campignContacts as $campContact) {
            $sendStatus = '';
            //If Campign Exists and Message not exists ignore message and update to failed
            if (isset($campContact['campign']['body'])==false || empty($campContact['campign']['body'])) {
                self::updateLogStatus($campContact['id'], 3);
                continue;
            }

            $message = $campContact['campign']['body'];
            $message = \App\Classes\MergeTagsHelper::RenderSmsBroadCast($message, $campContact['contact'], $campContact['contact']['company']);
            $number = $campContact['contact']['mobile_number'];
            $company_id = $campContact['contact']['company_id'];
            /*Send Sms*/
            $smsSent = SmsHelper::sendSms($number, $message, $company_id, 'sms_broadcast', $campContact['contact_id'], true);
             
            if ($smsSent) {
                $sendStatus = 'Success';
                self::updateLogStatus($campContact['id'], 2); // Update Status Complete to logs
            } else {
                $sendStatus = 'Failed';
                self::updateLogStatus($campContact['id'], 3); // Update Status failed to logs
            }
            echo 'Message send to contact :'.$campContact['contact_id'].' is '.$sendStatus.' <br /> ';
        }
    }
 
    public static function getPendingCampigns()
    {
        $broadcasts = EmSmsBroadcast::select(['id','body','company_id'])
        ->where('status', 4)
        ->orderBy('id', 'desc')
        ->get();
        if (count($broadcasts)>0) {
            return $broadcasts->toArray();
        }
        return [];
    }
    public static function getCampignLogContacts()
    {
        $data = EmSmsBroadcastLog::select(['id','contact_id','sms_broadcast_id'])->with(['contact',
        'contact.company'=>function ($query) {
            $query->select('id', 'name', 'address', 'phone', 'email', 'phone', 'city', 'state', 'site_url');
        },
        'campign'])
            ->where('status', 1)
            ->take(getenv('SMSBROADCAST_JOB_BATCH_SIZE'))
            ->get();
        if (count($data)>0) {
            return $data->toArray();
        }
        return [];
    }
    public static function updateLogStatus($logId, $status = 1)
    {
        $update_data = ['status'=>$status];
        if ($status==2) {
            $update_data = ['status'=>$status,'sent_at'=>new DateTime()];
        }
        EmSmsBroadcastLog::where('id', $logId)->update($update_data);
        return true;
    }
    public static function updateBraodcastLogStatus($broadcastId, $status = 1)
    {
        EmSmsBroadcastLog::where('sms_broadcast_id', $broadcastId)->update(['status'=>$status]);
        return true;
    }
    public static function updateCampign($campignId, $updateData)
    {
        EmSmsBroadcast::where('id', $campignId)
        ->update($updateData);
        return true;
    }
    public static function updateLogStatusArray($lodsIds, $status = 1)
    {
        EmSmsBroadcastLog::whereIn('id', $lodsIds)->update(['status'=>$status]);
        return true;
    }
    
    public static function checkAndSetCampignStatus()
    {
        $campigns = self::getPendingCampigns();
        if (count($campigns)>0) {
            $campignIds = array_column($campigns, 'id');
            foreach ($campignIds as $campignId) {
                $countSmsPending = self::getSumPendingSmsSend($campignId);
                if ($countSmsPending==0) {
                    self::updateCampign($campignId, ['status'=>3, 'sent_at'=>new DateTime()]);
                }
            }
        }
    }

    public static function getSumPendingSmsSend($campignId)
    {
        $countLog = EmSmsBroadcastLog::where('sms_broadcast_id', $campignId)
        ->where('status', 1)
        ->count();
        return $countLog;
    }
    public static function isCampignExists($campignId)
    {
        $campignCount = EmSmsBroadcast::where('id', $campignId)->count();
        if ($campignCount>0) {
            return true;
        }
        return false;
    }

    public static function removeCampign($campignId)
    {
        EmSmsBroadcast::where('id', $campignId)->delete();
        return true;
    }

    public static function getCountSmsBroadcastLog($campignId, $status = null)
    {
        $countLog = EmSmsBroadcastLog::where('sms_broadcast_id', $campignId);
        if ($status!=null) {
            $countLog->where('status', $status);
        }
        $countLog = $countLog->count();
        return $countLog;
    }

    public static function campignDetails($campingId)
    {
        $campign = EmSmsBroadcast::select(['title', 'body', 'status', 'test_num_country_code', 'from_number', 'test_phone_number','sent_at', 'schedule_datetime'])->where('id', $campingId)->first();
        if (count($campign)>0) {
            return $campign->toArray();
        }
        return [];
    }

    public static function campignNewsletterList($campignId)
    {
        $list_records = EmSmsBroadcastLog::select([
            'em_sms_broadcast_logs.list_id',
            'em_newsletter_lists.name as newsletter_list'
            ])
            ->join('em_newsletter_lists', 'em_sms_broadcast_logs.list_id', '=', 'em_newsletter_lists.id')
            ->where('em_sms_broadcast_logs.sms_broadcast_id', $campignId)
            ->groupBy('em_sms_broadcast_logs.list_id')
            ->get();
        if (count($list_records)>0) {
            return $list_records->toArray();
        }
        return [];
    }
}
