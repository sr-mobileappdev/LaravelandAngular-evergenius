<?php

namespace App\Http\Controllers;

use App\Classes\ActivityHelper;
use App\Classes\AppointmentsHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\CronHelper;
use App\Classes\LeadHelper;
use App\Classes\ReviewHelper;
use App\Classes\UserHelper;
use Auth;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Input;

class LeadsController extends Controller
{
    public function postCreateNewLead(Request $request)
    {
        $f_name = '';
        $l_name = '';
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
        $is_contact_updated = false;
        if (isset($input['company_id'])) {
            $company_id = $input['company_id'];
        }
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $c_user_id = $user->id;
        }

        $validate = lead_requruiref_fieds($input);
        /* Validate Required filters */
        if ($validate !== true) {
            return response()->error('key ' . $validate . ' Not Found');
        }

        /* Filter email id */
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return response()->error('Enter valid email.');
        }

        if (isset($input['first_name'])) {
            $f_name = $input['first_name'];
        }
        if (isset($input['last_name'])) {
            $l_name = $input['last_name'];
        }
        if (isset($input['gender'])) {
            $gender = $input['gender'];
        }
        if (isset($input['email'])) {
            $email = $input['email'];
        }
        if (isset($input['phone'])) {
            $phone = $input['phone'];
        }
        if (isset($input['address'])) {
            $address = $input['address'];
        }
        if (isset($input['city'])) {
            $city = $input['city'];
        }
        if (isset($input['state'])) {
            $state = $input['state'];
        }
        if (isset($input['country'])) {
            $country = $input['country'];
        }
        if (isset($input['zip_code'])) {
            $zip_code = $input['zip_code'];
        }
        if (isset($input['source_id'])) {
            $source_id = $input['source_id'];
        }
        if (isset($input['source'])) {
            $source_id = ContactHelper::addCustonTerm($input['source'], 'source', $company_id);
        }

        if (isset($input['dnd'])) {
            $dnd = $input['dnd'];
        }

        if (isset($input['service_id'])) {
            $service_id = $input['service_id'];
        }

        if (isset($input['service'])) {
            $service_id = LeadHelper::getServiceByName($input['service'], $company_id);
        }

        if (isset($input['assignee_id'])) {
            $user_id = $input['assignee_id'];
        }
        if (isset($input['ltv'])) {
            $ltv = $input['ltv'];
        }

        $stage = LeadHelper::getStageBySlug($company_id, 'prospects');
        if (isset($input['stage_id'])) {
            $stage = $input['stage_id'];
        }

        if (isset($input['country_code'])) {
            $country_code = $input['country_code'];
        }

        if (isset($input['contact_existing'])) {
            $contact_existing = $input['contact_existing'];
        }
        $existContact = ContactHelper::isContactExistsByPhone($company_id, $phone);
        if (!$existContact) {
            $contact_id = ContactHelper::storeContact($company_id, $f_name, $l_name, $email, $phone, $gender, $address, $city, $state, $country, $zip_code, $source_id, $dnd, $country_code, $contact_existing);
        } else {
            $contact_id = $existContact;
            $is_contact_updated = true;
            ContactHelper::updateContactById($contact_id, $input);
        }
        /* Add Notes */
        if (isset($input['notes'])) {
            ContactHelper::attachContactNote($contact_id, $input['notes'], null);
            $notes = $input['notes'];
        }

        /* /Add Notes */
        if (!LeadHelper::getLeadInfoByContact($contact_id) != false) {
            $lead_id = LeadHelper::add_lead($company_id, $contact_id, $stage, $service_id, $source_id, $user_id, $ltv, 1, $created_by = null, $notes);
        } else {
            $lead_id = LeadHelper::getLeadInfoByContact($contact_id);
        }
        


        /* Update tags*/
        if (isset($input['tags'])) {
            $tags = $input['tags'];
            ContactHelper::updateContactTags($tags, $contact_id, $company_id);
        }

        ContactHelper::updateConatctSource($company_id, $contact_id, $source_id);

        
        
        /* Update action where manual lead added*/
        if (isset($input['action_take'])) {
            LeadHelper::updateActionTaken($lead_id);
        }

        if ($is_contact_updated) {
            LeadHelper::notificationOnLead($contact_id, $lead_id, $notes, $source_id);
        }

        $name = $f_name . ' ' . $l_name;

        $notes = '';

        $action_date = date('Y-m-d H:i', time());
        /* Add Action Task */

        /*LeadHelper::add_task($lead_id, 'Follow up client', '', 6, $action_date ,  '30 min', null, $contact_id , 'high');*/

        return response()->success('success');
    }

    public function getLeadServices()
    {
        $input = Input::get();
        if (isset($input['company_id'])) {
            $company_id = $input['company_id'];
        }
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $services = LeadHelper::getServices($company_id);
        return response()->success(compact('services'));
    }

    public function getStages()
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $stages = LeadHelper::getAllStages($company_id);
        return response()->success(compact('stages'));
    }

    public function getSources()
    {
        $input = Input::get();
        if (isset($input['company_id'])) {
            $company_id = $input['company_id'];
        }
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $sources = LeadHelper::CompanySources($company_id);
        return response()->success(compact('sources'));
    }

    public function getLeadAssignees()
    {
        $input = Input::get();
        if (isset($input['company_id'])) {
            $company_id = $input['company_id'];
        }
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $lead_assignees = UserHelper::getLeadAssignes($company_id);
        return response()->success(compact('lead_assignees'));
    }

    public function getLeadsGroup()
    {
        $leads = [];
        $company_id = 0;
        $Input = Input::get();
        $assinee = null;
        $stage = null;
        $status = null;
        $service = null;
        $source = null;
        $app_time = null;
        $start_date = null;
        $end_date = null;
        
        if (isset($Input['assinee'])) {
            $assinee = $Input['assinee'];
        }
        if (isset($Input['stage'])) {
            $stage = $Input['stage'];
        }
        if (isset($Input['status'])) {
            $status = $Input['status'];
        }
        if (isset($Input['service'])) {
            $service = $Input['service'];
        }
        if (isset($Input['source'])) {
            $source = $Input['source'];
        }
     
        if (isset($Input['start_date']) && isset($Input['end_date'])) {
            $start_date = $Input['start_date'];
            $end_date = $Input['end_date'];
        }

      
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $user_role = $user->roles->toArray();
           
            if ($user_role[0]['slug'] != 'admin.user' && $user->id!=1) {
                $assinee = $user->id;
            }
        }

        $stages = LeadHelper::getAllStages($company_id);
        if ($stage == null) {
            foreach ($stages as $key => $stage) {
                if ($stage['slug']=='appointments') {
                    $app_time = true;
                }
                $leads[$stage['id']] = LeadHelper::getLeadGoupStage($company_id, $stage['id'], $assinee, $status, $service, $source, $start_date, $end_date, $app_time);
            }
        } else {
            $leads[$stage] = LeadHelper::getLeadGoupStage($company_id, $stage, $assinee, $status, $service, $source, $start_date, $end_date);
        }
        //print_r($leads[6]); die;
        return response()->success(compact('leads'));
    }

    public function getShow($lead_id = null)
    {
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        if (empty($lead_id) || $lead_id == null) {
            return response()->error('Lead Id is missing');
        }
        $lead_info = LeadHelper::getLead($lead_id, $company_id);
        if ($lead_info != false) {
            return response()->success($lead_info);
        }
        return response()->error('Enter Valid lead Id');
    }

    public function postUpdateLead($lead_id = null)
    {
        $assinee = null;
        $stage = null;
        $status = null;
        $service = null;
        $source = null;
        $ltv = null;
        $Input = Input::get();
        $acivity_captured = 0;

        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $c_user_id = $user->id;
        }

        $lead_info = LeadHelper::getLead($lead_id, $company_id);
        if ($lead_info != false && $lead_info['action_taken'] != 1) {
            LeadHelper::updateActionTaken($lead_info['id']);
        }
        if (isset($Input['assinee'])) {
            $assinee = $Input['assinee'];
            ActivityHelper::createActivity($company_id, 'LEAD_ASSIGNE_UPDATED', 'lead', $lead_id, $lead_info['contact_id'], $c_user_id, $c_user_id);
            $acivity_captured = 1;
        }
        if (isset($Input['stage'])) {
            $stage = $Input['stage'];
            $lead_slug = LeadHelper::getStageSlugByID($company_id, $stage);
            $activity_type = 'LEAD_STAGE_' . strtoupper($lead_slug);
            ActivityHelper::createActivity($company_id, $activity_type, 'lead', $lead_id, $lead_info['contact_id'], $c_user_id, $c_user_id);
            $acivity_captured = 1;
        }
        if (isset($Input['status'])) {
            $status = $Input['status'];
        }
        if (isset($Input['service'])) {
            $service = $Input['service'];
        }
        if (isset($Input['source'])) {
            $source = $Input['source'];
        }
        if (isset($Input['ltv'])) {
            $ltv = $Input['ltv'];
        }

        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        if (empty($lead_id) || $lead_id == null) {
            return response()->error('Lead Id is missing');
        }

        if ($lead_info == false) {
            return response()->error('Enter Valid lead Id');
        }

        if (isset($Input['tags'])) {
            ContactHelper::updateContactTags($Input['tags'], intval($lead_info['contact_id']), $company_id);
        }

        $update = LeadHelper::update_lead($lead_id, $company_id, $stage, $assinee, $status, $service, $source, $ltv);
        if ($update) {
            if ($acivity_captured != 1) {
                ActivityHelper::createActivity($company_id, 'LEAD_UPDATE', 'lead', $lead_id, $lead_info['contact_id'], $c_user_id, $c_user_id);
            }

            return response()->success(['status' => 'success']);
        }
    }

    public function postAddTask($lead_id = null)
    {
        if (empty($lead_id) || $lead_id == null) {
            return response()->error('Lead Id is missing');
        }
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $c_user_id = $user->id;
        }

        $lead_info = LeadHelper::getLead($lead_id, $company_id);
        $num_tasks = LeadHelper::num_tasks_lead($lead_id);

        if ($lead_info == false) {
            return response()->error('Enter Valid lead Id');
        }
        $Input = Input::get();
        $valid_error = task_requruiref_fieds($Input);

        if ($valid_error !== true) {
            return response()->error('key ' . $valid_error . ' not found');
        }
        if (isset($Input['assignee'])) {
            $c_user_id = $Input['assignee'];
        }
        if (isset($Input['user_id'])) {
            $c_user_id = $Input['user_id'];
        }
        if ($num_tasks < 1) {
            /* Update activity Time */
            LeadHelper::updateActionTaken($lead_id);

            /* Update Assignee if not exists */
            if ($lead_info['user_id'] == null && (isset($Input['user_id']) || isset($Input['assignee']))) {
                LeadHelper::update_lead($lead_id, $company_id, null, $c_user_id);
            }
        }

        $task_id = LeadHelper::add_task($lead_id, $Input['title'], $Input['description'], $Input['type'], $Input['action_date'], $Input['duration'], $c_user_id, $lead_info['contact_id'], $Input['priority']);

        ActivityHelper::createActivity($company_id, 'TASK_ADDED', 'task', $task_id, $lead_info['contact_id'], $c_user_id, $c_user_id);

        return response()->success(['status' => 'success']);
    }

    public function postUpdateTask($task_id = null)
    {
        $Input = Input::get();
        $title = null;
        $description = null;
        $action_date = null;
        $duration = null;
        $priority = null;
        $type = null;
        $assignee = null;
        $is_open = null;
        $updateRecentActivity = 0;
        $task_info = LeadHelper::getTask($task_id);
        $object_type = 'task';

        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $c_user_id = $user->id;
        }

        if (isset($Input['title'])) {
            $title = $Input['title'];
        }
        if (isset($Input['description'])) {
            $description = $Input['description'];
        }
        if (isset($Input['duration'])) {
            $duration = $Input['duration'];
        }
        if (isset($Input['priority'])) {
            $priority = $Input['priority'];
        }
        if (isset($Input['type'])) {
            $type = $Input['type'];
        }
        if (isset($Input['assignee'])) {
            $assignee = $Input['assignee'];
        }
        if (isset($Input['assignee'])) {
            $assignee = $Input['assignee'];
        }
        if (isset($Input['is_open'])) {
            /* Update Action taken time */
            $is_open = $Input['is_open'];
            /*if($is_open==0 && $task_info['title'] && $task_info['type_id']==6){
            LeadHelper::updateActionTaken($task_info['lead_id']);
            } */
            if ($is_open == 0) {
                ActivityHelper::createActivity($company_id, 'TASK_COMPLETE', $object_type, $task_id, $task_info['contact_id'], $c_user_id, $c_user_id);
            } else {
                ActivityHelper::createActivity($company_id, 'TASK_REOPEN', $object_type, $task_id, $task_info['contact_id'], $c_user_id, $c_user_id);
            }
        }

        if (empty($task_id) || $task_id == null) {
            return response()->error('Task Id is missing');
        }

        $task_exists = LeadHelper::is_task_exist($task_id);
        if ($task_exists !== true) {
            return response()->error('taks not exists');
        }

        $update = LeadHelper::update_task($task_id, $title, $description, $type, $action_date, $duration, $assignee, $priority, $is_open);
        if ($update) {
            $task = LeadHelper::getTask($task_id);
            return response()->success(['status' => 'success', 'task' => $task]);
        }
    }

    public function deleteTask($task_id = null)
    {
        if (empty($task_id) || $task_id == null) {
            return response()->error('Task Id is missing');
        }
        $task_exists = LeadHelper::is_task_exist($task_id);
        if ($task_exists !== true) {
            return response()->error('taks not exists');
        }
        $deleted = LeadHelper::task_delete($task_id);
        if ($deleted) {
            return response()->success(['status' => 'success']);
        }
    }

    public function getContactTasks($contact_id = null)
    {
        if (empty($contact_id) || $contact_id == null) {
            return response()->error('Contact ID is missing');
        }
        $tasks = LeadHelper::ContactTasks($contact_id);
        return response()->success(compact('tasks'));
    }

    public function getLeadTasks($lead_id = null)
    {
        if (empty($lead_id) || $lead_id == null) {
            return response()->error('Contact ID is missing');
        }
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
            $c_user_id = $user->id;
        }
        $lead_info = LeadHelper::getLead($lead_id, $company_id);
        if ($lead_info == false) {
            return response()->error('Enter Valid lead Id');
        }
        $tasks = LeadHelper::LeadTasks($lead_id);
        return response()->success(compact('tasks'));
    }

    public function getTaskTypes()
    {
        $task_types = LeadHelper::getTaskTypes();
        return response()->success(compact('task_types'));
    }

    public function getFindTags()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $search = Input::get('s');
        $sources = ContactHelper::findTermByName($search, $company_id, 'tag');
        $out = [];
        foreach ($sources as $key => $value) {
            $out[] = array('text' => $value['term_value']);
        }
        return $out;
    }

    public static function getFunnelWidget()
    {
        $user = Auth::user();
        $user_role = $user->roles->toArray()[0]['slug'];
        $company_id = $user->company_id;
        $stages = LeadHelper::getAllStages($company_id);
        $out = [];
        $close_amount = 0;
        $agent_id = null;
        $input_data = input::all();
        $start_date = date('Y-m-d', time());
        $end_date = date('Y-m-d', time());

        if (isset($input_data['start_date']) && isset($input_data['end_date'])) {
            $start_date = $input_data['start_date'];
            $end_date = $input_data['end_date'];
        }
        if ($user_role != 'admin.user') {
            $agent_id = $user->id;
        }

        foreach ($stages as $key => $stage) {
            $count_lead = 0;
            $total_ltv = 0;
            $leads_statics = LeadHelper::getLeadStageStatics($stage['id'], $company_id, $agent_id, $start_date, $end_date);
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
        $avg_lead_response_time = LeadHelper::getAvgLeadResponseTime($company_id, $agent_id, false, $start_date, $end_date, true);
        return response()->success(compact('leads_statics', 'close_amount', 'avg_lead_response_time'));
    }

    public static function getMyRecentTasks()
    {
        $user = Auth::user();
        $user_role = $user->roles->toArray()[0]['slug'];
        $company_id = $user->company_id;
        $user_id = $user->id;
        $input = Input::get();
        $due = false;
        if (isset($input['due'])) {
            $due = true;
        }
        $tasks = LeadHelper::userTasks($user_id, $due);
        return response()->success(compact('tasks'));
    }

    public function getMyRecentLeads()
    {
        $user = Auth::user();
        $user_role = $user->roles->toArray()[0]['slug'];
        $company_id = $user->company_id;
        $user_id = $user->id;
        $leads = LeadHelper::getUserRecentLeads($company_id, $user_id);
        return response()->success(compact('leads'));
    }

    public function getLeadsNotAction()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $action_pending_leads = LeadHelper::getLeadsWithAction($company_id, 0, 10);
        return response()->success(compact('action_pending_leads'));
    }

    public static function getEmailActionNotTaken()
    {
        $date_time = new dateTime();
        $current_time = $date_time->format('H:i');
        $cron_id = CronHelper::createCronRecord('email_action_taken');
        $compnies = CompanyHelper::getAllCompanies();
        foreach ($compnies as $key => $compny) {
            $compny_id = $compny['id'];
            $time_ago_mins = 250;
            if ($time_ago_mins !== false) {
                $leads_to_email = LeadHelper::getActionPendingLeads($compny_id, $time_ago_mins);
                if (count($leads_to_email) > 0) {
                    LeadHelper::getActionPendingEmails($leads_to_email, $compny_id);
                }
            }
        }
        CronHelper::udateCronEndTime($cron_id);
        return response()->success('success');
    }

    public function dailyWorkSummaryMail()
    {
        $allCompany = CompanyHelper::getAllCompanies();
        $array = array();
        $count = 0;
        $companies_send_mail = [];
        //$allCompany = ['1'];
        /* Get Companies match with activity time emails */
        foreach ($allCompany as $company_s) {
            $company_details = CompanyHelper::getCompanyDetais($company_s['id']);
            if ($company_details['is_active'] == 1) {
                $tz = CompanySettingsHelper::getSetting($company_s['id'], 'timezone');
                $o_time = new DateTime;
                $c_time = $o_time->format('H:i');
                if ($tz != '' && $tz != false) {
                    $c_time = Carbon::createFromTimestamp(strtotime($c_time))
                        ->timezone($tz);
                    $c_time = $c_time->format('H:i');
                }
                /*get App time when daily email fire */
                $activity_email_time = \App\Classes\AppOptionsHelper::getOptionValue('daily_activity_email_time');
                if ($activity_email_time == $c_time) {
                    $companies_send_mail[] = $company_s;
                }
            }
        }
        foreach ($companies_send_mail as $company) {
            $url = getenv('APP_URL'). '/email_work_summary/' . $company['id'];
            $data_h = \Curl::to($url)
                ->withResponseHeaders()
                ->returnResponseObject()
                ->get();
            //print_r($data_h);
            $cmpSet = \App\CompanySetting::where('company_id', $company['id'])->where('name', 'daily_performance_report')->first();
            
            if (isset($cmpSet) && $cmpSet->value == 1 && $data_h->status == 200) {
                $image_name = uniqid() . '.png';
                $publicpath = public_path();
                $directoryPath = $publicpath . "/email_daily/" . $company['id'] . "/";

                if (!file_exists($publicpath . "/email_daily/")) {
                    mkdir($publicpath . "/email_daily/", 0777);
                }

                $image_path = $publicpath . "/email_daily/" . $company['id'] . "/" . $image_name;
                if (!file_exists($directoryPath)) {
                    mkdir($directoryPath, 0777);
                }
                $generate = true;
                if ($generate) {
                    $conv = new \Anam\PhantomMagick\Converter();
                    //$conv->setBinary(Path::binaryPath());
                    $conv->setBinary('/var/www/vhosts/evergenius.com/app.evergenius.com/vendor/anam/phantomjs-linux-x86-binary/bin/phantomjs');
                    $conv->width(600);
                    $conv->source(getenv('APP_URL') . '/email_work_summary/' . $company['id'])->toPng()->save($image_path);
                    $data = array(
                        'image_path' => getenv('APP_URL') . '/email_daily/' . $company['id'] . '/' . $image_name,
                        'company_name' => $company['name'],
                    );
                    $bcc_email = ['bob@evergenius.com', 'wali@evergenius.com'];
                    $app_from_email = app_from_email();
                   
                    CompanySettingsHelper::sendCompanyEmailNotifcation($company['id'], $data, 'Daily Activity Summary Report', $bcc_email, '/emails/daily_email_content', $app_from_email);
                    \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company['id'], $data['image_path'], 'Daily Activity Summary Report', $company['id'], 'daily_activity_report', 'DAILY_ACTIVITY_REPORT');
                    $count++;
                }
            }
        }
        echo "Companies Sent Count: " . $count;
        exit();
    }

    public function generateDailyWorkEmailView($id)
    {
        try {
            $company_id = $id;
            $time_cl_now = new DateTime();
            //$time_cl_now->modify('-1 day');
            $time_utc_now = $time_cl_now->format('Y-m-d');
            $company_time_zone = CompanySettingsHelper::getSetting($company_id, 'timezone');

            if ($company_time_zone == false) {
                $company_time_zone = 'UTC';
            }

            $start_date = CompanyHelper::convertCompanyTime($time_utc_now, $company_id, 'Y-m-d');
            $end_date = $start_date;
            $avtd_value = LeadHelper::getAvgLeadResponseTime($company_id, null, true);
            if (!empty($avtd_value)) {
                $string = "";
                $string .= !empty($avtd_value['H']) ? $avtd_value['H'] . "Hr " : "";
                $string .= !empty($avtd_value['M']) ? $avtd_value['M'] . "Min" : "";
                $avtd_value = $string;
            }
            $leads = LeadHelper::getCountLeadsByDate($start_date, $end_date, $company_id, $company_time_zone);

            $stages = LeadHelper::fetchStagesTasks($start_date, $end_date, $company_id);
            $call_sms = LeadHelper::callAndSmsCountByDate($company_id, $start_date, $end_date);
            $appointments_count = AppointmentsHelper::getCountAppointmentBytime($start_date, $end_date, $company_id);
            $appointments_sources = AppointmentsHelper::getCountAptmntBySoucetime($start_date, $end_date, $company_id);
            $funnel_data = LeadHelper::GetFunnelData($company_id, null);
            $company_details = CompanyHelper::getCompanyDetais($company_id);
            $show_funnel_data = false;

            if (isset($funnel_data['leads_statics'])) {
                foreach ($funnel_data['leads_statics'] as $static) {
                    if ($static['count_lead'] > 0) {
                        $show_funnel_data = true;
                    }
                }
            }
            $date_today = $start_date;
            $date = new DateTime('7 days ago');
            $date_7_days_ago = $date->format('Y-m-d');
            $date_today = CompanyHelper::convertCompanyTime($time_utc_now, $company_id, 'D, M d, Y');
            $reviews = ReviewHelper::getCompanyAllReviews($company_id, 3, null, $date_today, $end_date);
            $analytics = \APP\Http\Controllers\GoogleanalyticsContoller::getGoogleAnalyticsData($company_id, $date_7_days_ago, $date_today);

            return view('/emails/email_report_template', compact('date_today', 'company_details', 'reviews', 'analytics', 'funnel_data', 'leads', 'stages', 'appointments_count', 'appointments_sources', 'call_sms', 'avtd_value', 'show_funnel_data'));
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function checkReportStatus($id)
    {
        $company_id = $id;
        $start_date = date('Y-m-d');
        $end_date = $start_date;
        if (!empty($avtd_value)) {
            $string = "";
            $string .= !empty($avtd_value['H']) ? $avtd_value['H'] . "Hr " : "";
            $string .= !empty($avtd_value['M']) ? $avtd_value['M'] . "Min" : "";
            $avtd_value = $string;
        }
        $leads = LeadHelper::getCountLeadsByDate($start_date, $end_date, $company_id);
        $call_sms = LeadHelper::callAndSmsCountByDate($company_id);
        $appointments_count = AppointmentsHelper::getCountAppointmentBytime($start_date, $end_date, $company_id);
        $appointments_sources = AppointmentsHelper::getCountAptmntBySoucetime($start_date, $end_date, $company_id);
        if ($leads['today_leads'] == 0 && $call_sms['callCount'] == 0 && $call_sms['smsCount'] == 0 && $appointments_count == 0) {
            return false;
        }
        return true;
    }

    public function getLeadStatus()
    {
        $lead_statuses = LeadHelper::getleadstatuses();
        return response()->success(compact('lead_statuses'));
    }
    public static function dailyEmailScheduler()
    {
        $LeadsController = new LeadsController;
        $LeadsController->dailyWorkSummaryMail();
    }

    /** Delete Oppertunity **/
    public function deleteLead($leadId = '')
    {
        if ($leadId != '') {
            $company_id = null;
            if (Auth::user()) {
                $user = Auth::user();
                $company_id = $user->company_id;
            }

            $lead_info = LeadHelper::getLead($leadId, $company_id);
            if ($lead_info != false) {
                LeadHelper::removeLead($leadId);
                return response()->success(['status' => 'success', 'message' => 'lead deleted']);
            }
            return response()->error('Enter Valid lead Id');
        }
        return response()->error('Someting went wrong');
    }
}
