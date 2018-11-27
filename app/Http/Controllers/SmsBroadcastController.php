<?php

namespace App\Http\Controllers;

use App\Classes\CompanyHelper;
use App\Classes\SmsBroadcastHelper;
use App\EmNewsletterContacts;
use App\EmNewsletterList;
use App\EmSmsBroadcastLog;
use App\Http\Controllers\ContactController;
use App\User;
use Auth;
use Datatables;
use Excel;
use Illuminate\Http\Request;
use Input;

//use App\Repositories\EmailMarketing\EloquentEmailMarketing;

class SmsBroadcastController extends Controller
{
    public function postListing()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $allCampaigns = SmsBroadcastHelper::FetchAllSmsBroadcasts($company_id);
        return Datatables::of($allCampaigns)->make(true);
    }

    public function postCreate($broadcast_id = null)
    {
        // sendmail,inprogress,scheduled,draft
        $input = Input::get();
        //print_r($input); die;
        $user = Auth::user();
        $company_id = $user->company_id;

        $broadcastId = SmsBroadcastHelper::createSmsBroadcast($input, $company_id, $broadcast_id);

        if ($broadcastId==false) {
            return response()->error(['status' => 'fail','message'=>'Unable to create sms broadcast, Please refresh page and try again !!', 'broadcast_id' =>'0']);
        }

        if (isset($input['broadcast_lists'])) {
            $broadcast_lists = $input['broadcast_lists'];
            SmsBroadcastHelper::updateNewsLetterBroadcast($company_id, $broadcastId, $input['broadcast_lists']);
        }

        if (isset($input['save_type']) && $input['save_type'] == 'sendsms') {
            $status = SmsBroadcastHelper::sendBroadcastMessage($input, $company_id, $broadcastId);
        } elseif (isset($input['save_type']) && $input['save_type'] == 'inprogress' && isset($input['broadcast_lists'])) {
            $input['status'] = 2; //In progress
            if ($broadcastId != null) {
                $status = SmsBroadcastHelper::sendSmsCampaign($company_id, $broadcastId, 1);
                if ($status == false) {
                    return response()->success(['status' => 'false', 'message' => "Twillo account issue, unable to send message", 'broadcast_id' => $broadcastId]);
                }
            }
        } elseif (isset($input['save_type']) && $input['save_type'] == 'scheduled' && isset($input['broadcast_newsletter_lists'])) {
            $input['status'] = 3; //scheduled
        }

        return response()->success(['status' => 'success', 'broadcast_id' => $broadcastId]);
    }

    public function getShow($broadcast_id=null)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        if ($broadcast_id) {
            $sms_broadcast = SmsBroadcastHelper::getBroadcastById($broadcast_id);
            $sms_broadcast['broadcast_lists'] = SmsBroadcastHelper::getCampignListNewsletterLists($broadcast_id);
            return response()->json(['status' => 'success','message'=>'sms broadcast found !!', 'data'=>$sms_broadcast]);
        }
        return response()->json(['status' => 'fail','message'=>'No such sms broadcast found !!']);
    }

    public function ConfigureCron()
    {
        /*compare time with current time if any campign is scheduled then enter in log table*/
        SmsBroadcastHelper::processScheduleCampigns();
        
        /*Send SMS to Queue*/
        SmsBroadcastHelper::processQueueSms();
        
        /*
        //IT will check all of campign where status is inprgress
         //if count of pending sms is 0
        //then mark as completed
        */
        SmsBroadcastHelper::checkAndSetCampignStatus();
    }

    public function deleteCampign($campignId=null)
    {
        /* Check is Campign exists */
        if ($campignId!=null && SmsBroadcastHelper::isCampignExists($campignId)) {
            SmsBroadcastHelper::removeCampign($campignId);
            return response()->success(['status'=>'success']);
        }
        return response()->error('please enter valid campign id');
    }

    public static function postCampignShow($campignId=null)
    {
        $where = [];
        $input_data = Input::get();
        if (isset($input_data['customFilter']['newsletter_list_id'])) {
            $list_w = array('list_id', '=', $input_data['customFilter']['newsletter_list_id']);
            array_push($where, $list_w);
        }
        if ($campignId!=null && SmsBroadcastHelper::isCampignExists($campignId)) {
            $list_records = EmSmsBroadcastLog::select(['contacts.first_name',
            'contacts.last_name',
            'contacts.mobile_number as phone',
            'contacts.phone_country_code',
            'em_sms_broadcast_logs.status',
            'em_sms_broadcast_logs.sent_at',
            'em_sms_broadcast_logs.sent_at',
            'em_newsletter_lists.name as newsletter_list'
            ])
            ->join('contacts', 'em_sms_broadcast_logs.contact_id', '=', 'contacts.id')
            ->join('em_newsletter_lists', 'em_sms_broadcast_logs.list_id', '=', 'em_newsletter_lists.id')
            ->where('em_sms_broadcast_logs.sms_broadcast_id', $campignId)
            ->where($where)
            ->get();
            return Datatables::of($list_records)->make(true);
        }
        return response()->error('please enter valid campign id');
    }

    public function getCampignStat($campignId=null){
        $count = [];
        if ($campignId!=null && SmsBroadcastHelper::isCampignExists($campignId)) {
            $campign_details = SmsBroadcastHelper::campignDetails($campignId);
            $count['total'] = SmsBroadcastHelper::getCountSmsBroadcastLog($campignId);
            $count['sent'] = SmsBroadcastHelper::getCountSmsBroadcastLog($campignId, 2);
            $count['pending'] = SmsBroadcastHelper::getCountSmsBroadcastLog($campignId, 1);
            $count['failed'] = SmsBroadcastHelper::getCountSmsBroadcastLog($campignId, 3);
            $count['inprogress'] = SmsBroadcastHelper::getCountSmsBroadcastLog($campignId, 4);
            return response()->success(compact(['campign_details','count'])); 
        }
            return response()->error('please enter valid campign id');
    }

    public function getCampignNewsletterLists($campignId = null){
        if ($campignId!=null && SmsBroadcastHelper::isCampignExists($campignId)) {
            $campign_newsletters = SmsBroadcastHelper::campignNewsletterList($campignId);
            return response()->success(compact(['campign_newsletters'])); 
            
        }
        return response()->error('please enter valid campign id');
    }

    public function getCloneSmsBroadcast($campignId=null){
        //$campignId
        if($campignId==null){
            return response()->error(['campign ID is required']);
        }
        $sms_broadcast = SmsBroadcastHelper::getBroadcastById($campignId);
        if($sms_broadcast){
            unset($sms_broadcast['id']);
            $sms_broadcast['title'] = $sms_broadcast['title'].'-Copy';
            $sms_broadcast['status'] = 1;
            $sms_broadcast['sent_at'] = null;
            $user = Auth::user();
            $company_id = $user->company_id;
            $broadcastId = SmsBroadcastHelper::createSmsBroadcast($sms_broadcast, $company_id, null);
            $broadcast_lists = SmsBroadcastHelper::getCampignListNewsletterLists($campignId);
            // if(count($broadcast_lists)>0) {
            //     $broadcast_lists =array_column($broadcast_lists, 'id');
            // }
            SmsBroadcastHelper::updateNewsLetterBroadcast($company_id, $broadcastId, $broadcast_lists);
            return response()->success(['status' => 'success', 'broadcast_id' => $broadcastId]);
        }

        //$sms_broadcast['broadcast_lists'] = SmsBroadcastHelper::getCampignListNewsletterLists($broadcast_id);
    }




}
