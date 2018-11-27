<?php
namespace App\Classes;

use App\CallRecord;
use App\Classes\ActivityHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Lead;
use App\ContactsDt;
use App\NotificationLogs;
use App\LeadSource;
use App\LeadStatus;
use App\Service;
use App\SmsRecord;
use App\Stage;
use App\Task;
use App\TaskType;
use App\User;
use Auth;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use DB;

class LeadHelper
{
    public static function add_lead($company_id, $contact_id, $stage_id, $service_id, $source_id, $user_id = null, $ltv = null, $status_id = null, $created_by = null, $notes = null)
    {
        $c_user_id = null;
        if (Auth::user()) {
            $user = Auth::user();
            $c_user_id = $user->id;
        }

        $time = new DateTime();
        $lead = new Lead;

        if ($ltv == null || $ltv == '') {
            $ltv = CompanySettingsHelper::getSetting($company_id, 'ltv_value');
        }

        if ($notes == null) {
            $notes = '';
        }
        if ($status_id == null) {
            $status_id = 1;
        }
        if ($user_id == null) {
            $user_id = self::roundRobinLeadAsignee($company_id);
        }
        $lead->company_id = $company_id;
        $lead->contact_id = $contact_id;
        $lead->stage_id = $stage_id;
        $lead->user_id = $user_id;
        $lead->ltv = $ltv;
        $lead->service_id = $service_id;
        $lead->created_by = $created_by;
        $lead->status_id = $status_id;
        $lead->source_id = $source_id;
        $lead->created_at = $time;
        $lead->save();
        $lead_id = $lead->id;

        /* Get Contact Information */
        $contact_info = ContactHelper::getContactInfo($contact_id);
        if ($contact_info != false) {
            $contact_details = $contact_info->toArray();
            $name = ucwords($contact_details['first_name'] . ' ' . $contact_details['last_name']);
            $phone = str_replace($contact_details['phone_country_code'], "", $contact_details['mobile_number']);
        }

        ActivityHelper::createActivity($company_id, 'ADD_LEAD', 'lead', $lead_id, $contact_id, $user_id, $c_user_id);

        /* Email Notify when create any lead */
        LeadHelper::NotifyAddLeadAdminEmail($name, strtolower($contact_details['email']), maskPhoneNumber($phone), $notes, $company_id, $source_id, $lead_id, $contact_id);

        LeadHelper::NotifyAddLeadAdminSMS($name, strtolower($contact_details['email']), maskPhoneNumber($phone), $notes, $company_id, $source_id, $lead_id, $contact_id);

        return $lead_id;
    }

    public static function notificationOnLead($contactId, $leadId, $notes, $source_id)
    {
        $contact_info = ContactHelper::getContactInfo($contactId);
        if ($contact_info != false) {
            $contact_details = $contact_info->toArray();
            $name = ucwords($contact_details['first_name'] . ' ' . $contact_details['last_name']);
            $phone = str_replace($contact_details['phone_country_code'], "", $contact_details['mobile_number']);
            $company_id = $contact_details['company_id'];
            /* Email Notify when create any lead */
            LeadHelper::NotifyAddLeadAdminEmail($name, strtolower($contact_details['email']), maskPhoneNumber($phone), $notes, $company_id, $source_id, $leadId, $contactId);
            LeadHelper::NotifyAddLeadAdminSMS($name, strtolower($contact_details['email']), maskPhoneNumber($phone), $notes, $company_id, $source_id, $leadId, $contactId);
        }
    }




    public static function NotifyAddLeadAdminEmail($name, $email, $phone, $notes, $company_id, $lead_source, $lead_id = null, $contact_id = null)
    {
        $email_message = NotificationHelper::getNotificationMethod($company_id, 'mail', 'LEAD_ADD');
        $email_subject = NotificationHelper::getNotificationSubject($company_id, 'mail', 'LEAD_ADD');
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_email = $company_information['email'];
        $time_now = date('Y-m-d H:i:s', time());
        $source_name = ContactHelper::getTermsById($lead_source, $company_id, 'source');
        if ($source_name == false) {
            $source_name = '';
        }

        $enable_new_opertunity_email = CompanySettingsHelper::getSetting($company_id, "new_opertunity");

        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $time_now = CompanyHelper::convertCompanyTime($time_now, $company_id, 'M d h:i A');
        $getassignee = ContactsDt::select('assignee_id')->where(['lead_id' => $lead_id])->get();
        $getassigneearray = $getassignee->toArray();
        $assignee_id = $getassigneearray[0]['assignee_id'];
        $user_name = User::select('name')->where(['id' => $assignee_id])->get();
        $userarray = $user_name->toArray();
        $assignee_name = $userarray[0]['name'];
        $leadData = [
            'first_name'=>$name,
            'last_name'=>$name,
            'full_name'=>$name,
            'phone_number'=>$phone,
            'email'=>$email,
            'notes'=>$notes,
            'sign'=>$bob_s,
            'source'=>$source_name,
            'assign'=>$assignee_name,
            'time'=>$time_now];
            $email_message = \App\Classes\MergeTagsHelper::RenderLeadsTags($email_message, $leadData, $company_information);
        if ($email_message != false && $email_subject != false && $enable_new_opertunity_email == '1') {
            $message = nl2br($email_message);
            $app_from_email = app_from_email();
            $data['company_information'] = $company_information;
            $data['company_information']['logo'] = 'img/mail_image_preview.png';
            $data['content_data'] = $email_message;
            $bcc_email = getenv('BCC_EMAIL');
            /*Send Notification to Company Notify Users */
            CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);
            \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_subject, $email_subject, $lead_id, 'lead', 'LEAD_ADD');
        }
    }

    public static function NotifyAddLeadAdminSMS($name, $email, $phone, $notes, $company_id, $lead_source, $lead_id = null, $contact_id = null)
    {
        $sms_message = NotificationHelper::getNotificationMethod($company_id, 'sms', 'RECEIVE_NEW_OPPORTUNITY_SMS');
        $sms_subject = NotificationHelper::getNotificationSubject($company_id, 'sms', 'RECEIVE_NEW_OPPORTUNITY_SMS');

        $company_information = CompanyHelper::getCompanyDetais($company_id);

        $company_email = $company_information['email'];
        $source_name = ContactHelper::getTermsById($lead_source, $company_id, 'source');
        if ($source_name == false) {
            $source_name = '';
        }
        $company_phone = $company_information['phone'];
        $enable_new_opertunity_email = CompanySettingsHelper::getSetting($company_id, "new_opertunity");
        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';
        $time_now = date('Y-m-d H:i:s', time());
        $time_now = CompanyHelper::convertCompanyTime($time_now, $company_id, 'M d h:i A');
        $getassignee = ContactsDt::select('assignee_id')->where(['lead_id' => $lead_id])->get();
        $getassigneearray = $getassignee->toArray();
        $assignee_id = $getassigneearray[0]['assignee_id'];
        $user_name = User::select('name')->where(['id' => $assignee_id])->get();
        $userarray = $user_name->toArray();
        $assignee_name = $userarray[0]['name'];
        $leadData = [
            'first_name'=>$name,
            'last_name'=>$name,
            'full_name'=>$name,
            'phone_number'=>$phone,
            'email'=>$email,
            'notes'=>$notes,
            'sign'=>$bob_s,
            'source'=>$source_name,
            'assign'=>$assignee_name,
            'time'=>$time_now];
        $sms_message = \App\Classes\MergeTagsHelper::RenderLeadsTags($sms_message, $leadData, $company_information);
        $sms_message = strip_tags($sms_message);
        $sms_message = preg_replace("/&nbsp;/", '', $sms_message);
        if ($sms_message != false) {
            CompanySettingsHelper::sendSmsToCompanyNotifyUsers($company_id, $sms_message, $company_phone);
            \App\Classes\CompanySettingsHelper::sendCompanySmsNotifcationLogs($company_id, $sms_message, $company_phone, $lead_id, 'RECEIVE_NEW_OPPORTUNITY_SMS', 'lead', $company_id);
        }
    }

    public static function getServiceTitle($service_id)
    {
        $service = Service::select('name')->find($service_id);
        if (count($service) > 0) {
            return $service->name;
        }
        return false;
    }

    public static function getSourceTitle($service_id, $company_id)
    {
        $service = LeadSource::select('name')->where(['id' => $service_id, 'company_id' => $company_id])->first();
        if (count($service) > 0) {
            return $service->name;
        }
        return false;
    }

    public static function getServiceId($service, $company_id)
    {
        $service = Service::select('id')->where(['name' => $service, 'company_id' => $company_id])->first();
        if (count($service) > 0) {
            return $service->id;
        }
        return false;
    }

    /* Create new service if service not exists */
    public static function getServiceByName($service, $company_id)
    {
        $service_id = LeadHelper::getServiceId($service, $company_id);
        if ($service_id == false) {
            $c_service = new Service;
            $c_service->name = $service;
            $c_service->company_id = $company_id;
            $c_service->save();
            return $c_service->id;
        }
        return $service_id;
    }

    public static function getServices($company_id)
    {
        $services = Service::select(['id', 'name', 'description'])->where('company_id', $company_id)->get();
        if (count($services) > 0) {
            return $services;
        }
        return [];
    }

    public static function getAllStages($company_id)
    {
        $stages_data = Stage::select('id', 'title', 'slug')
            ->where('company_id', $company_id)
            ->get();
        if (count($stages_data) > 0) {
            return $stages_data->toArray();
        }
        return [];
    }

    public static function CompanySources($company_id)
    {
        $sources = LeadSource::select('id', 'name')->where(['company_id' => $company_id])->get();
        if (count($sources) > 0) {
            return $sources->toArray();
        }
        return [];
    }

    public static function getLeadGoupStage($company_id, $stage, $assinee = null, $status = null, $service = null, $source = null, $start_date = null, $end_date = null, $app_time = null)
    {
        $where = array();
        if ($assinee != null) {
            $w = array('user_id', '=', $assinee);
            array_push($where, $w);
        }
        if ($status != null) {
            $w = array('status_id', '=', $status);
            array_push($where, $w);
        }
        if ($source != null) {
            $w = array('source_id', '=', $source);
            array_push($where, $w);
        }
        if ($service != null) {
            $w = array('service_id', '=', $service);
            array_push($where, $w);
        }
        
        if ($start_date!=null && $end_date!=null) {
            $company_time_zone = CompanySettingsHelper::getSetting($company_id, 'timezone');
            if ($company_time_zone!==false) {
                $start_time = CompanyHelper::AnyTimeZoneToUTC($start_date. '00:00:00', $company_time_zone);
                $end_date = CompanyHelper::AnyTimeZoneToUTC($end_date. '23:59:59', $company_time_zone);
            }
            $d_s = array('created_at', '>=', $start_time);
            $d_e = array('created_at', '<=', $end_date);
            array_push($where, $d_s, $d_e);
        }
        
        $leads = Lead::with(['assignee', 'contact'=> function ($query) use ($company_id) {
            $query->where('company_id', '=', $company_id);
        },'appointment'=>function ($query) use ($company_id) {
            $query->orderBy('start_datetime', 'desc');
        }])
            ->withCount('tasks')
            ->where('company_id', $company_id)
            ->where('stage_id', $stage)
            ->where($where);
        if ($app_time==null) {
                $leads = $leads->orderBy('id', 'desc');
        }
            $leads = $leads->get();
            //print_r( $leads); die;
        if ($app_time) {
            $leads = $leads->sortBy('appointment.start_datetime');
        }
        if (count($leads) > 0) {
            return $leads->toArray();
        }
        return [];
    }

    public static function getLead($lead_id, $company_id)
    {
        $lead = Lead::with(['assignee', 'contact', 'source', 'service', 'stage'])
            ->where('id', $lead_id)
            ->where('company_id', $company_id)
            ->first();
        if (count($lead) > 0) {
            return $lead->toArray();
        }
        return false;
    }

    public static function getLeadByContact($contact_id, $company_id)
    {
        $lead = Lead::with(['assignee', 'source', 'service', 'stage'])
            ->where('contact_id', $contact_id)
            ->where('company_id', $company_id)
            ->first();
        if (count($lead) > 0) {
            return $lead->toArray();
        }
        return false;
    }

    public static function update_lead($lead_id, $company_id, $stage = null, $assinee = null, $status = null, $service = null, $source = null, $ltv = null)
    {
        $update_data = array();
        if (Auth::user()) {
            $user = Auth::user();
            $u_id = $user->id;
        }
        $update_data['update_by'] = $u_id;
        $update_data['updated_at'] = new DateTime();

        if ($stage != null) {
            $update_data['stage_id'] = $stage;
        }
        if ($assinee != null) {
            $update_data['user_id'] = $assinee;
        }
        if ($status != null) {
            $update_data['status_id'] = $status;
        }
        if ($service != null) {
            $update_data['service_id'] = $service;
        }
        if ($source != null) {
            $update_data['source_id'] = $source;
        }
        if ($ltv != null) {
            $update_data['ltv'] = $ltv;
        }
        Lead::where('company_id', $company_id)
            ->where('id', $lead_id)
            ->update($update_data);
        return true;
    }

    public static function add_task($lead_id, $title, $description, $type, $action_date, $duration, $user_id, $contact_id, $priority)
    {
        $action_date = date('Y-m-d H:i:s', strtotime($action_date));
        $task = new Task();
        $task->title = $title;
        $task->lead_id = $lead_id;
        $task->description = $description;
        $task->type_id = $type;
        $task->action_date = $action_date;
        $task->duration = $duration;
        $task->contact_id = $contact_id;
        $task->user_id = $user_id;
        $task->priority = $priority;
        $task->open = 1;
        $task->save();
        return $task->id;
    }

    public static function is_task_exist($task_id)
    {
        $is_task = Task::where('id', $task_id)->count();
        if ($is_task > 0) {
            return true;
        }
        return false;
    }

    public static function update_task($task_id, $title = null, $description = null, $type = null, $action_date = null, $duration = null, $user_id = null, $priority = null, $is_open = null)
    {
        $update_data = [];

        if ($title !== null) {
            $update_data['title'] = $title;
        }
        if ($description !== null) {
            $update_data['description'] = $description;
        }
        if ($type !== null) {
            $update_data['type_id'] = $type;
        }
        if ($action_date !== null) {
            $update_data['action_date'] = $action_date;
        }
        if ($action_date !== null) {
            $update_data['action_date'] = $action_date;
        }
        if ($duration !== null) {
            $update_data['duration'] = $duration;
        }
        if ($user_id !== null) {
            $update_data['user_id'] = $user_id;
        }
        if ($priority !== null) {
            $update_data['priority'] = $priority;
        }
        if ($is_open !== null) {
            $update_data['open'] = $is_open;
        }

        Task::where('id', $task_id)
            ->update($update_data);
        return true;
    }

    public static function task_delete($task_id)
    {
        Task::find($task_id)->delete();
        return true;
    }

    public static function ContactTasks($contact_id)
    {
        $tasks = Task::with('type')->where(['contact_id' => $contact_id])
            ->orderBy('id', 'desc')
            ->get();
        if (count($tasks) > 0) {
            return $tasks->toArray();
        }
        return [];
    }

    public static function userTasks($user_id, $due = null)
    {
        $where = [];
        if ($due != null) {
            $where[] = ['open', '=', 1];
        }

        $tasks = Task::with('type', 'contact')->where(['user_id' => $user_id])->where($where)
            ->limit(10)
            ->orderBy('action_date', 'asc')
            ->get();
        if (count($tasks) > 0) {
            return $tasks->toArray();
        }
        return [];
    }

    public static function LeadTasks($lead_id)
    {
        $tasks = Task::with('type')->where(['lead_id' => $lead_id])
            ->orderBy('id', 'desc')
            ->get();
        if (count($tasks) > 0) {
            return $tasks->toArray();
        }
        return [];
    }
    public static function getTask($task_id)
    {
        $tasks = Task::with('type')->where(['id' => $task_id])
            ->first();
        if (count($tasks) > 0) {
            return $tasks->toArray();
        }
        return [];
    }

    public static function getTaskTypes()
    {
        $res = [];
        $task_types = TaskType::select(['id', 'name'])->get();
        if (count($task_types) > 0) {
            $res = $task_types;
        }
        return $res;
    }

    public static function deleteLeadByContact($contact_id)
    {
        Lead::where('contact_id', $contact_id)->delete();
        return true;
    }

    public static function getLeadStageStatics($stage_id, $company_id, $agent_id = null, $start_date = null, $end_date = null)
    {
        $where = array();
        if ($agent_id != null) {
            $w = array('user_id', '=', $agent_id);
            array_push($where, $w);
        }

        if ($start_date != null && $end_date != null) {
            $app_start_time = date('Y-m-d 00:00:00', strtotime($start_date));
            $app_end_time = date('Y-m-d 23:59:59', strtotime($end_date));
            $timeZone = CompanySettingsHelper::getSetting($company_id, 'timezone');
            if ($timeZone != '' && $timeZone != false) {
                $app_start_time = self::convertToUtc($app_start_time, $timeZone, 'Y-m-d H:i:s');
                $app_end_time = self::convertToUtc($app_end_time, $timeZone, 'Y-m-d H:i:s');
            }
            $w_1 = array('leads.created_at', '>=', $app_start_time);
            $w_2 = array('leads.created_at', '<=', $app_end_time);
            array_push($where, $w_1, $w_2);
        }
        $statics = Lead::select(DB::raw('count(*) as total_leads'), DB::raw('SUM(ltv) as total_ltv'))
            ->where('stage_id', $stage_id)
            ->where('status_id', 1)
            ->join('contacts', 'contacts.id', '=', 'leads.contact_id')
            ->where($where)
            ->where('leads.company_id', $company_id)
            ->where('contacts.company_id', $company_id)->first();
        if (count($statics) > 0) {
            return $statics->toArray();
        }
        return false;
    }

    public static function getUserRecentLeads($company_id, $user_id)
    {
        $leads = Lead::with(['assignee', 'contact', 'stage'])
            ->withCount('tasks')
            ->where('company_id', $company_id)
            ->where('user_id', $user_id)
            ->limit(5)
            ->get();
        if (count($leads) > 0) {
            return $leads->toArray();
        }
        return [];
    }

    public static function getStageBySlug($company_id, $slug)
    {
        $stage = Stage::select('id')
            ->where(['slug' => $slug, 'company_id' => $company_id])
            ->first();
        if (count($stage) > 0) {
            return $stage->id;
        }
        return false;
    }

    public static function getStageSlugByID($company_id, $id)
    {
        $stage = Stage::select('slug')
            ->where(['id' => $id, 'company_id' => $company_id])
            ->first();
        if (count($stage) > 0) {
            return $stage->slug;
        }
        return false;
    }

    public static function updateActionTaken($lead_id)
    {
        $time = new DateTime();
        $update_data = ['lead_action_time' => $time, 'action_taken' => 1];
        Lead::where('id', $lead_id)
            ->where('action_taken', 0)
            ->update($update_data);
        return true;
    }

    public static function getLeadInfo($lead_id)
    {
        $lead_info = Lead::where('id', $lead_id)->first();
        if (count($lead_info) > 0) {
            return $lead_info->toArray();
        }
        return false;
    }

    public static function getLeadInfoByContact($contact_id)
    {
        $lead_info = Lead::where('contact_id', $contact_id)->first();
        if (count($lead_info) > 0) {
            return $lead_info->toArray();
        }
        return false;
    }

    public static function getLeadsWithAction($company_id, $action, $limit = 10)
    {
        $leads = Lead::with(['assignee',
        'contact'=> function ($query) use ($company_id) {
                    $query->where('company_id', '=', $company_id);
        },
             'source',
             'service',
             'stage'])
            ->withCount('tasks')
            ->where('company_id', $company_id)
            ->where('action_taken', $action)
            ->where('status_id', 1)
            ->limit($limit)
            ->get();
        if (count($leads) > 0) {
            return $leads->toArray();
        }
        return [];
    }

    public static function getAvgLeadResponseTime($company_id, $user_id = null, $date = false, $start_date = null, $end_date = null, $dashboard = false)
    {
        $where = array();
        if ($user_id != null) {
            $w = array('user_id', '=', $user_id);
            array_push($where, $w);
        }

        $time_cal = Lead::select(DB::raw('AVG(TIME_TO_SEC(TIMEDIFF(`lead_action_time`,`created_at`))) as avg_lead_time'));
        $time_cal->where('company_id', $company_id);
        $time_cal->where('action_taken', 1);
        $time_cal->where(DB::raw('TIME_TO_SEC(TIMEDIFF(`lead_action_time`,`created_at`))'), '>', 60);

        if ($date == true) {
            /*For Daily Email Report*/
            $time_cal->whereDate('created_at', '=', date('Y-m-d'));
        }

        if ($start_date != null && $end_date != null && $dashboard == true) {
            /*For Sale Funnel Widget*/
            $app_start_time = date('Y-m-d 00:00:00', strtotime($start_date));
            $app_end_time = date('Y-m-d 23:59:59', strtotime($end_date));
            $time_cal->whereBetween('created_at', [$app_start_time, $app_end_time]);
        }

        $time_cal = $time_cal->first();
        if (count($time_cal) > 0) {
            $sconds = $time_cal->avg_lead_time;
            $hours = floor($sconds / 3600);
            $minutes = floor(($sconds / 60) % 60);
            return ['H' => $hours, 'M' => $minutes];
        }
        return null;
    }

    public static function getActionPendingLeads($company_id, $time_ago_mins)
    {
        $time_ago = Carbon::now()->subHours(24);
        $leads = Lead::select('leads.*')->with(['assignee', 'contact', 'stage'])
            ->withCount('tasks')
            ->where('leads.company_id', $company_id)
            ->where('leads.created_at', '<=', $time_ago)
            ->where('leads.created_at', '>=', '2018-03-08 00::00:00')
            ->where('leads.action_taken', 0)
            ->leftJoin('notification_logs', 'notification_logs.lead_id', '=', 'leads.id')
            ->whereNull('notification_logs.id')
            ->get();
        if (count($leads) > 0) {
            return $leads->toArray();
        }
        return [];
    }

    public static function getActionPendingEmails($leads, $company_id)
    {
        $email_message = NotificationHelper::getNotificationMethod($company_id, 'mail', 'LEAD_PENDING_ACTION');
        $email_subject = NotificationHelper::getNotificationSubject($company_id, 'mail', 'LEAD_PENDING_ACTION');
        $company_information = CompanyHelper::getCompanyDetais($company_id);
        $company_email = $company_information['email'];
        $bob_s = '<img src="' . url('/') . '/img/bob_sign.png" alt="Bob Signature">';

        if ($email_message !== false && $email_subject !== false) {
            if (strpos($email_message, '{{action_pending_leads}}') !== false || strpos($email_message, '{$action_pending_leads}')!=false) {
                $email_message = str_replace("{{bob_signature}}", $bob_s, $email_message);
                $email_message = str_replace('{$bob_signature}', $bob_s, $email_message);
                $email_table = LeadHelper::replace_lead_blocks($leads, $company_id);
                $email_message = str_replace("{{action_pending_leads}}", $email_table, $email_message);
                $email_message = str_replace('{$action_pending_leads}', $email_table, $email_message);
            }
            if ($email_message != false && $email_subject != false) {
                $message = nl2br($email_message);
                $app_from_email = app_from_email();
                $data['company_information'] = $company_information;
                $data['company_information']['logo'] = 'img/mail_image_preview.png';
                $data['content_data'] = $email_message;
                $bcc_email = getenv('BCC_EMAIL');
                /**Send Email to admin on New Notification**/
                CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, $data, $email_subject, $bcc_email, 'emails.social_post_publish', $app_from_email);

                foreach ($leads as $key => $lead) {
                    LeadHelper::Lead_email_notification_log($lead['id'], 'no_action', $email_message, $email_subject, null, null, $lead['id'], 'lead', null, 'mail');
                }
            }
        }
        return true;
    }

    public static function replace_lead_blocks($leads, $company_id)
    {
        $content = '';
        foreach ($leads as $key => $lead) {
            $content .= '<table width="100%" border="0" cellpadding="2" style="margin-bottom: 24px; border-bottom: 1px solid #ccc; padding-bottom: 25px;">
				<tr>
					<td width="25%" style="font-family:Arial; font-size:15px; color:#333; line-height:20px; font-weight:bold;">Name:</td>
					<td width="75%" style="font-family:Arial; font-size:15px; color:#333; line-height:20px;">' . $lead['contact']['first_name'] . ' ' . $lead['contact']['first_name'] . '</td>
				</tr>
				<tr>
					<td width="25%" style="font-family:Arial; font-size:15px; color:#333; line-height:18px; font-weight:bold;">Email:</td>
					<td width="75%" style="font-family:Arial; font-size:15px; color:#333; line-height:20px;"><a href="#" style="color:#000; text-decoration:none;">' . $lead['contact']['email'] . '</a></td>
				</tr>
				<tr>
					<td width="25%" style="font-family:Arial; font-size:15px; color:#333; line-height:18px; font-weight:bold;">Phone #:</td>
					<td width="75%" style="font-family:Arial; font-size:15px; color:#333; line-height:20px;">' . $lead['contact']['phone'] . '</td>
				</tr>
				<tr>
					<td width="25%" style="font-family:Arial; font-size:15px; color:#333; line-height:18px; font-weight:bold;">Stage:</td>
					<td width="75%" style="font-family:Arial; font-size:15px; color:#333; line-height:20px;">' . $lead['stage']['title'] . '</td>
				</tr>
			</table>';
        }
        return $content;
    }

    public static function Lead_email_notification_log($lead_id, $typ, $body, $subject, $contact_id = null, $appointment_id = null, $obj_id = null, $obj_type = null, $send_to = null, $noti_type = null, $company_id = 0)
    {
        $log = new NotificationLogs();
        $log->lead_id = $lead_id;
        $log->type = $typ;
        $log->object_id = $obj_id;
        $log->object_type = $obj_type;
        $log->company_id = $company_id;
        $log->send_to = $send_to;
        $log->notification_type = $noti_type;
        $log->type = $typ;
        $log->contact_id = $contact_id;
        $log->appointment_id = $appointment_id;
        $log->date_sent = new DateTime();
        $log->body = $body;
        $log->subject = $subject;
        $log->save();
        return $log->id;
    }

    
    public static function getAssigneeReport($company_id, $user_id = null, $startdate, $enddate)
    {
        $arv = DB::select('call proc_agent_report(?,?,?)', array($company_id, $startdate, $enddate));
        //$arv     = AgentReportView::where('company_id',$company_id);
        return collect($arv);
    }

    public static function getCountLeadsByDate($start_date = null, $end_date = null, $company_id, $tz = 'UTC')
    {
        $where_leads = [];
        $start_time = self::convertToUtc($start_date . ' 00:00:00', $tz, 'Y-m-d H:i:s');
        $end_date = self::convertToUtc($end_date . ' 23:59:59', $tz, 'Y-m-d H:i:s');

        $d_s = array('created_at', '>=', $start_time);
        $d_e = array('created_at', '<=', $end_date);
        array_push($where_leads, $d_s, $d_e);
        $today_total_leads = Lead::where('company_id', $company_id)->where($where_leads)->count();
        $total_leads = Lead::where('company_id', $company_id)->where('status_id', 1)->count();
        $stage = Stage::select('id')->where('slug', 'close')->first()->toArray();
        $leads = [];
        $today_closed_leads = Lead::where('company_id', $company_id)->where('stage_id', $stage['id'])->where($where_leads)->count();
        $leadbox = Lead::with('source', 'contact')->where($where_leads)->get()->toArray();
        $source_leads = Lead::select(DB::raw('source_id , count(*) as count_leads'))
            ->where('company_id', $company_id)
            ->where($where_leads)
            ->groupBy('source_id')
            ->orderBy('count_leads', 'desc')
        //->limit(5)
            ->get();
        $sources_leads = [];
        $srcs = [];
        $othr_c = 0;
        $colorArray = array('#F99265', '#228DB7', '#7CDA86', '#B198DC', '#F99265', '#228DB7', '#7CDA86', '#B198DC');
        if (count($source_leads) > 0) {
            $source_leads = $source_leads->toArray();
            $i_s = 1;
            foreach ($source_leads as $key => $value) {
                $srs_title = ContactHelper::getTermsById($value['source_id'], $company_id, 'source');
                if ($i_s < 3 && $value['source_id'] != null && $srs_title != null) {
                    $sources_leads[$value['source_id']] = array('source' => $srs_title, 'count' => $value['count_leads'], 'color' => $colorArray[$i_s]);
                } else {
                    if ($value['source_id'] != null) {
                        if ($srs_title == null) {
                            $srs_title = '';
                        }
                        $othr_c = $othr_c + (int) $value['count_leads'];
                        if (!isset($colorArray[$i_s])) {
                            $colorArray[$i_s] = '#F99265';
                        }
                        $sources_leads['others'] = array('source' => $srs_title, 'count' => $othr_c, 'color' => $colorArray[$i_s]);
                    }
                }
                $i_s++;
            }
        }
        $data = array();
        $leadArray = array();
        $other = 0;

        $leadArray['Other'] = 0;
        $key = 0;
        $count = 0;
        foreach ($leadbox as $leadItem) {
            if ($key <= 3) {
                if (array_key_exists($leadItem['source']['name'], $leadArray)) {
                    $leadArray[$leadItem['source']['name']] = array('count' => ($leadArray[$leadItem['source']['name']]['count'] + 1), 'color' => $colorArray[$key]);
                } else {
                    $leadArray[$leadItem['source']['name']] = array('count' => 1, 'color' => $colorArray[$key]);
                }
            } else {
                $count += 1;
                $leadArray['Other'] = array('count' => $count, 'color' => '#3BCDE6');
            }

            $key++;
        }
        if ($leadArray['Other'] == 0) {
            unset($leadArray['Other']);
        }
        return array('total_leads' => $total_leads, 'today_leads' => $today_total_leads, 'today_closed_leads' => $today_closed_leads, 'leads' => $leadArray, 'sources_leads' => $sources_leads);
    }

    public static function fetchStagesTasks($start_date, $end_date, $company_id)
    {
        $data = \App\Stage::select(
            'stages.title',
            DB::raw("(select count(*) from leads where stage_id = stages.id and date(created_at)='" . date('Y-m-d') . "') as total_count")
        )
            ->where('stages.company_id', $company_id)
            ->orderBy('total_count', 'desc')
            ->get();
        if ($data) {
            $data = $data->toArray();
        }
        return $data;
    }

    public static function rand_color()
    {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    public static function callAndSmsCountByDate($company_id)
    {
        $server_time = new dateTime();
        $server_time = $server_time->format('Y-m-d H:i:s');
        $user_time = $server_time;
        $start_time = date('Y-m-d 00:00:00', strtotime($user_time));
        $end_time = date('Y-m-d 23:59:59', strtotime($user_time));
        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $start_time = Carbon::createFromTimestamp(strtotime($server_time))
                ->timezone($tz)
                ->format('Y-m-d 00:00:00');
            $end_time = Carbon::createFromTimestamp(strtotime($server_time))
                ->timezone($tz)
                ->format('Y-m-d 23:59:59');
        } else {
            $tz = 'UTC';
        }
        $start_time = self::convertToUtc($start_time, $tz);
        $end_time = self::convertToUtc($end_time, $tz);
        $where_call = [];
        $where_sms = [];
        $d_s = array('call_start_at', '>=', $start_time);
        $d_e = array('call_start_at', '<=', $end_time);
        array_push($where_call, $d_s, $d_e);

        $sm_s = array('created_at', '>=', $start_time);
        $sm_e = array('created_at', '<=', $end_time);
        array_push($where_sms, $sm_s, $sm_e);

        $callCount = CallRecord::where('company_id', $company_id)->where($where_call)->count();

        $smsCount = SmsRecord::where('company_id', $company_id)->where($where_sms)->count();
        return array('callCount' => $callCount, 'smsCount' => $smsCount);
    }

    public static function convertToUtc($time, $timezonefrom, $format = null)
    {
        $tzd = 'UTC';
        if ($format == null) {
            $format = 'Y-m-d H:i:s';
        }
        $dt = new DateTime($time, new DateTimeZone($timezonefrom));
        $dt->setTimeZone(new DateTimeZone($tzd));
        return $dt->format($format);
    }

    public static function num_tasks_lead($lead_id)
    {
        $count_tasks = Task::where('lead_id', $lead_id)
            ->count();
        return $count_tasks;
    }

    public static function getleadstatuses()
    {
        $status = LeadStatus::select(['id', 'name'])->get();
        if (count($status) > 0) {
            return $status->toArray();
        }
        return [];
    }

    public static function GetFunnelData($company_id, $agent_id)
    {
        $stages = LeadHelper::getAllStages($company_id);
        $out = [];
        $close_amount = 0;

        foreach ($stages as $key => $stage) {
            $count_lead = 0;
            $total_ltv = 0;
            $app_start_time = date('Y-m-d', time());
            $app_end_time = date('Y-m-d', time());

            $leads_statics = LeadHelper::getLeadStageStatics($stage['id'], $company_id, $agent_id);
            if ($leads_statics !== false) {
                if ($leads_statics['total_leads'] != null) {
                    $count_lead = $leads_statics['total_leads'];
                }
                if ($leads_statics['total_ltv'] != null) {
                    $total_ltv = $leads_statics['total_ltv'];
                }
            }
            $out[] = array(
                'title' => $stage['title'],
                'count_lead' => (int) $count_lead,
                'total_ltv' => (float) $total_ltv,
                'stage_id' => $stage['id'],
            );
        }
        if (!empty($out)) {
            $lenght = count($out);
            $close_amount = $out[$lenght - 1]['total_ltv'];
        }
        $leads_statics = $out;
        $avg_lead_response_time = LeadHelper::getAvgLeadResponseTime($company_id, $agent_id);
        return array('leads_statics' => $leads_statics, 'close_amount' => $close_amount, 'avg_lead_response_time' => $avg_lead_response_time);
        //return response()->success(compact('leads_statics', 'close_amount', 'avg_lead_response_time'));
    }

    public static function removeLead($leadId)
    {
        $leadInfo = self::getLeadInfo($leadId);
        $contact_id = $leadInfo['contact_id'];
        $company_id = $leadInfo['company_id'];

        ActivityHelper::createActivity($company_id, 'DELETE_LEAD', 'lead', $leadId, $contact_id, null, null);
        Lead::find($leadId)->delete();
        Task::where('lead_id', $leadId)->delete();
        return true;
    }

    public static function roundRobinLeadAsignee($company_id)
    {
        $asigneeObj = User::whereHas('roles', function ($q) {
            $q->whereNotIn('role_id', ['5', '6']);
        })->where('company_id', $company_id)->where('send_lead', '=', '1')->orderby('lead_last', 'asc')->first();
        if ($asigneeObj) {
            $asigneeObj->lead_last = date('Y-m-d H:i:s');
            $asigneeObj->save();
            return $asigneeObj->id;
        }
    }

    public static function getNextPreviousContact($lead_id, $stage_id, $type, $company_id)
    {
        $conLead = Lead::select('contact_id')
        ->where('stage_id', $stage_id)
        ->where('company_id', $company_id)
        ->where('status_id', 1)
        ->whereExists(function ($query) {
            $query->select(DB::raw('contacts.id'))
                  ->from('contacts')
                  ->whereRaw('leads.contact_id = contacts.id')
                  ->whereNull('contacts.deleted_at');
        });
        if ($type == 'next') {
            $conLead = $conLead->where('id', '>', $lead_id)
            ->orderBy('id', 'ASC')
            ->limit(1);
        } else {
            $conLead = $conLead->where('id', '<', $lead_id)
            ->orderBy('id', 'DESC')
            ->limit(1);
        }
        $res = $conLead->first();
        //print_r($conLead->toSql()); die;
        if (count($res)>0) {
            return $res->contact_id;
        }
        return false;
    }
}
