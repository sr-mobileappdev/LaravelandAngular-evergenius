<?php

namespace App\Classes;

use App\CompanyTemplate;
use App\ContactTag;
use App\EmActionFunnelQueue;
use App\EmFunnel;
use App\EmFunnelAction;
use App\EmFunnelActionRule;
use App\EmFunnelList;
use App\EmNewsletterContacts;

class FunnelHelper
{
    public static function setFunnel($data, $company_id, $status)
    {
        $emfunnel = new EmFunnel;
        $emfunnel->name = $data['name'];

        $emfunnel->company_id = $company_id;
        $emfunnel->status = $status;
        $response = $emfunnel->save();
        if ($response) {
            if (isset($data['list']) && !empty($data['list'])) {
                $funnel_id = $emfunnel->id;
                $list_array = $data['list'];
                self::setFunnelList($list_array, $funnel_id);
            }
        }
        return $emfunnel->id;
    }

    public static function setFunnelList($data, $funnel_id)
    {
        if ($data) {
            EmFunnelList::where('funnel_id', $funnel_id)->delete();
            foreach ($data as $item) {
                EmFunnelList::insert(['funnel_id' => $funnel_id, 'list_id' => $item['id']]);
            }
        } else {
            EmFunnelList::where('funnel_id', $funnel_id)->delete();
        }
    }

    public static function fetchFunnels($company_id)
    {
        $emf = EmFunnel::withCount('stepcount')->where('company_id', $company_id)->orderBy('created_at', 'desc')->get();
        return $emf;
    }

    public static function getFunnel($company_id, $funnel_id)
    {
        $data = EmFunnel::with('f_list.listdetail')->where('company_id', $company_id)->where('id',
            $funnel_id)->orderBy('created_at', 'desc')->get();
        if ($data) {
            $data = $data->toArray();
            return $data;
        }
    }

    public static function delFunnel($company_id, $funnel_id)
    {
        $exist = EmFunnel::where('id', $funnel_id)->count();
        if ($exist > 0) {
            EmFunnel::where('company_id', $company_id)->where('id', $funnel_id)->delete();
            //$stat_two = EmFunnelList::where('funnel_id', $funnel_id)->delete();
            //$stat_three = EmFunnelAction::where('funnel_id', $funnel_id)->delete();
            //$stat_four  = EmFunnelActionRule::where('funnel_id', $funnel_id)->delete();
            return true;
        }
        return false;
    }

    public static function updateFunnel($data, $company_id, $status)
    {
        $funnel_id = $data['funnel_id'];
        $emfunnel = EmFunnel::findOrFail($funnel_id);
        $emfunnel->name = $data['name'];
        $emfunnel->company_id = $company_id;
        $emfunnel->status = $status;
        $response = $emfunnel->save();
        if ($response) {
            $funnel_id = $emfunnel->id;
            if (isset($data['list'])) {
                $list_array = $data['list'];
                self::setFunnelList($list_array, $funnel_id);
            }
        }
        return true;
    }

    public static function setActionStep($data, $company_id, $status)
    {
        $emfa = new EmFunnelAction;
        $emfa->name = $data['step_name'];
        $emfa->action_type = $data['action_type'];
        $emfa->trigger_type = $data['trigger_type'];
        $emfa->trigger_value = $data['trigger_value'];
        $emfa->funnel_id = $data['funnel_id'];
        $emfa->status = $status;
        $s = $emfa->save();
        if ($s) {
            return $emfa->id;
        }
        return false;
    }

    public static function getFunnelActionSteps($funnel_id)
    {
        $emfa = EmFunnelAction::with('rules.sourcename', 'tag', 'emlist')->withCount('sent', 'opened',
            'clicked')->where('funnel_id', $funnel_id)->orderBy('trigger_type', 'desc')->orderBy('trigger_value',
            'asc')->get();

        if ($emfa) {
            $emfa = $emfa->toArray();
            return $emfa;
        }
    }

    public static function updateActionSteps($data, $step_id, $company_id)
    {
        $emfa = EmFunnelAction::find($step_id);
        if ($emfa) {
            if (isset($data['step_name'])) {
                $emfa->name = $data['step_name'];
            }
            if (isset($data['action_type'])) {
                $emfa->action_type = $data['action_type'];
            }
            if (isset($data['trigger_type'])) {
                $emfa->trigger_type = $data['trigger_type'];
            }
            if (isset($data['trigger_value'])) {
                $emfa->trigger_value = $data['trigger_value'];
            }
            if (isset($data['funnel_id'])) {
                $emfa->funnel_id = $data['funnel_id'];
            }
            if (isset($data['status'])) {
                $emfa->status = $data['status'];
            }
            if (isset($data['email_subject'])) {
                $emfa->email_subject = $data['email_subject'];
            }
            if (isset($data['email_body'])) {
                $emfa->email_body = $data['email_body'];
            }
            if (isset($data['json_body'])) {
                $emfa->json_body = $data['json_body'];
            }
            if (isset($data['sms_text'])) {
                $emfa->sms_text = $data['sms_text'];
            }
            if (isset($data['do_action'])) {
                $emfa->do_action = $data['do_action'];
            }

            if (isset($data['list'])) {
                $list = current($data['list']);
                if ($list) {
                    $emfa->list_id = $list['id'];
                }
            }
            if (isset($data['tag'])) {
                $tag = current($data['tag']);
                if ($tag) {
                    $tag_id = ContactHelper::addCustonTerm($tag['text'], 'tag', $company_id);
                    if ($tag_id != false) {
                        $emfa->tag_id = $tag_id;
                    }
                }
            }
            $stat = $emfa->save(); //save action step rule
            if ($stat) {
                return true;
            }
            return false;
        }
    }

    public static function findFunnelStepCompanyID($step_id)
    {
        $funnel = EmFunnelAction::with('funnel')->where('id', $step_id)->first();
        if ($funnel) {
            return array('company_id' => $funnel->funnel->company_id, 'funnel_id' => $funnel->funnel->id);
        }
        return 0;
    }

    public static function deleteStep($step_id, $funnel_id)
    {
        $res = EmFunnelAction::where('id', $step_id)->where('funnel_id', $funnel_id)->delete();
        return $res;
    }

    public static function setStepRule($data, $company_id)
    {
        //time_schedule = 1=after/2-before
        if ($data) {
            $insert_arr = array();
            $funnel_id = $data['funnel_id'];
            $step_id = $data['step_id'];
            $action_rule_id = isset($data['action_rule_id']) ? $data['action_rule_id'] : false;
            $i = 0;
            foreach ($data['data'] as $item) {
                $insert_arr[$i]['funnel_id'] = $funnel_id;
                $insert_arr[$i]['action_id'] = $step_id;
                $insert_arr[$i]['rule_type'] = $item['rule_type'];

                /**IF RULE TYPE  IS APPOINTMENT**/
                if ($item['rule_type'] == 'appointment') {
                    $insert_arr[$i]['appointment_status'] = $item['appointment_status'];
                }

                $insert_arr[$i]['trigger_time_date'] = $item['trigger_time_date'];
                $insert_arr[$i]['trigger_time_unit'] = $item['trigger_time_unit'];
                $insert_arr[$i]['time_schedule'] = $item['time_schedule'];

                /**IF RULE TYPE  IS OPPORTUNITY**/
                if ($item['rule_type'] == 'opportunity') {
                    if (isset($item['opportunity_status'])) {
                        $insert_arr[$i]['opportunity_status'] = $item['opportunity_status'];
                    }
                    if (isset($item['opportunity_stage_id'])) {
                        $insert_arr[$i]['opportunity_stage_id'] = $item['opportunity_stage_id'];
                    }
                    if (isset($item['opportunity_service_id'])) {
                        $insert_arr[$i]['opportunity_service_id'] = $item['opportunity_service_id'];
                    }
                    if (isset($item['opportunity_source_id'])) {
                        $insert_arr[$i]['opportunity_source_id'] = self::getTermId($item['opportunity_source_id'],
                            $company_id);
                    }
                }

                $insert_arr[$i]['created_at'] = date('Y-m-d H:i:s');
                $insert_arr[$i]['updated_at'] = date('Y-m-d H:i:s');
                $i++;
            }
            if ($action_rule_id != false) {
                $stat = EmFunnelActionRule::where('id', $action_rule_id)->update(current($insert_arr));
            } else {
                $stat = EmFunnelActionRule::insert($insert_arr);
            }

            if ($stat) {
                return true;
            }
        }
        return false;
    }

    private static function getTermId($search_name, $company_id)
    {
        $arr = '';
        if (empty($search_name)) {
            return $arr;
        }

        $source_id = ContactHelper::isCustonTermExists($search_name, 'source', $company_id);
        if ($source_id != false) {
            return $source_id;
        }

        return $arr;
    }

    public static function deleteActionRule($rule_id, $company_id)
    {
        $found = EmFunnelActionRule::with('company')->where('id', $rule_id)->first();
        if ($found) {
            $found = $found->toArray();
            if (isset($found['company']) && $found['company']['company_id'] == $company_id) {
                EmFunnelActionRule::where('id', $rule_id)->delete();
                return true;
            }
        }
        return false;
    }

    public static function funnelCron()
    {
        $aptcontact = '';
        $optcontact = '';
        $queue_contact_arr = array();
        $live_funnels = EmFunnel::with('apikeyexists', 'company', 'f_list')->where('status',
            1)->whereNull('deleted_at')->get();
        //dd($live_funnels);

        foreach ($live_funnels as $funnel) {
            $funnelArr = $funnel->toArray();

            if (!empty($funnelArr['apikeyexists'])) {

                $em_funnel_actions = EmFunnelAction::where('funnel_id', $funnel->id)->where('status',
                    '1')->get(); //fetch live actions
                foreach ($em_funnel_actions as $action) {
                    $em_funnel_action_rules = EmFunnelActionRule::with('aptstatus')->where('action_id',
                        $action->id)->get();

                    if ($em_funnel_action_rules->count() > 0) {
                        //If action has rule
                        foreach ($em_funnel_action_rules as $rule) {
                            //GET CONTACTS ON BASIS OF APPOINTMENT RULE
                            if ($rule->rule_type == 'appointment') {

                                $rule_array = $rule->toArray();
                                $apt_status = !empty($rule_array['aptstatus']) ? $rule_array['aptstatus']['title'] : 'Approved';

                                /**fetch contacts from  appointments values**/


                                if ($action->action_type == 3) { //DO-ACTION EVENT
                                    $contacts = self::getAptContact($rule->appointment_status, $rule->trigger_time_date,
                                        $rule->trigger_time_unit, $rule->time_schedule, $funnel->company_id,
                                        $rule->action_id);
                                    $contacts = self::renderContactsForDoAction($contacts, false,
                                        true); //Render the Contacts
                                    self::doActionOnContacts($contacts, $action, $funnel->company_id, $funnel->id,
                                        false, true);
                                } else {
                                    $contacts = self::getAptContact($rule->appointment_status, $rule->trigger_time_date,
                                        $rule->trigger_time_unit, $rule->time_schedule, $funnel->company_id,
                                        $rule->action_id, $funnelArr['f_list']);
                                    $aptcontact = self::processContacts($funnel->id, $action, $contacts, true,
                                        $apt_status, false);
                                }

                                $queue_contact_arr = array();
                                if (!empty($aptcontact)) {
                                    $queue_contact_arr = $aptcontact;
                                }
                            }

                            //GET CONTACTS ON BASIS OF OPPORTUNITY RULE
                            if ($rule->rule_type == 'opportunity') {
                                $rule_array = $rule->toArray();


                                if ($action->action_type == 3) { //DO-ACTION EVENT
                                    $contacts = self::GetOpportunityContacts($rule['opportunity_source_id'],
                                        $rule['opportunity_service_id'], $rule['opportunity_stage_id'],
                                        $rule['opportunity_status'], $rule['trigger_time_date'],
                                        $rule['trigger_time_unit'], $rule['time_schedule'], $funnel->company_id,
                                        $rule['action_id']);
                                    $contacts = self::renderContactsForDoAction($contacts, true,
                                        false); //Render the Contacts
                                    self::doActionOnContacts($contacts, $action, $funnel->company_id, $funnel->id, true,
                                        false);
                                } else {
                                    $contacts = self::GetOpportunityContacts($rule['opportunity_source_id'],
                                        $rule['opportunity_service_id'], $rule['opportunity_stage_id'],
                                        $rule['opportunity_status'], $rule['trigger_time_date'],
                                        $rule['trigger_time_unit'], $rule['time_schedule'], $funnel->company_id,
                                        $rule['action_id'], $funnelArr['f_list']);
                                    $optcontact = self::processContacts($funnel->id, $action, $contacts, true, '',
                                        true);
                                }

                                $queue_contact_arr = array();
                                if (!empty($optcontact)) {
                                    $queue_contact_arr = $optcontact;
                                }
                            }
                        }
                    } else {
                        $funnel_list = EmFunnelList::with('listd')->where('funnel_id',
                            $funnel->id)->whereNull('deleted_at')->get();
                        $queue_contact_arr = array();
                        if ($funnel_list->count() > 0) {

                            $funnel_list = $funnel_list->toArray();
                            $queue_contact_arr = self::processList($action, $funnel->id, $funnel_list,
                                $funnel->company_id, $funnel->company);

                            if (!empty($queue_contact_arr) && $action->action_type == 3) {
                                self::doActionOnContacts($queue_contact_arr, $action, $funnel->company_id, $funnel->id);
                            }
                        } else {

                            $queue_contact_arr = self::processCompanyContacts($action, $funnel->id, $funnel->company_id,
                                $funnel->company);

                        }
                    }
                    if (!empty($queue_contact_arr) && current($queue_contact_arr) != false && $action->action_type != 3) {

                        self::CopyRecorsToFunnelQueue($queue_contact_arr);
                    }
                } //END ACTIONS FOR LOOP
            }
        }

    }

    private static function getAptContact(
        $appointment_status,
        $trigger_time_date,
        $trigger_time_unit,
        $time_schedule,
        $company_id,
        $action_id,
        $funnel_list = null
    ) {
        $timezone_obj = \App\CompanySetting::select('name', 'value')->where('company_id', $company_id)->where('name',
            'timezone')->first();
        $date = date('Y-m-d H:i');
        $timezone = 'UTC';
        $timezone_to = $timezone_obj->value;

        $appointments = array();

        if ($time_schedule) {
            $appointments = \App\Appointment::with('contacts.assignlist', 'doctor', 'company')->select('id',
                'contact_id', 'start_datetime', 'end_datetime', 'provider_user_id',
                'company_id')->where('appointment_status_id', $appointment_status);
            //$unit = $trigger_time_unit == 2 ? 'hour' : 'day';
            $unit = self::getUnitType($trigger_time_unit);

            if ($unit == 'hour') {
                $format = 'Y-m-d H:i:s';
                $datetime = self::convertDateFromTimezone($date, $timezone, $timezone_to, $format);
                if ($time_schedule == 1) { //After
                    $utc_datetime = strtotime('-' . $trigger_time_date . 'hour', strtotime($datetime));
                    $to_datetime = date('Y-m-d H:i:s',
                        strtotime('-' . $trigger_time_date . 'hour', strtotime($datetime)));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "10 minutes", strtotime($to_datetime)));
                    $appointments->whereBetween('end_datetime', array($from_datetime, $to_datetime));
                } else { //Before
                    $utc_datetime = strtotime('+' . $trigger_time_date . 'hour', strtotime($datetime));
                    $to_datetime = date('Y-m-d H:i:s',
                        strtotime('+' . $trigger_time_date . 'hour', strtotime($datetime)));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "10 minutes", strtotime($to_datetime)));
                    $appointments->whereBetween('start_datetime', array($from_datetime, $to_datetime));
                }
            } elseif ($unit == 'minute') {
                $format = 'Y-m-d H:i:s';
                $datetime = self::convertDateFromTimezone($date, $timezone, $timezone_to, $format);
                if ($time_schedule == 1) { //After
                    $utc_datetime = strtotime('-' . $trigger_time_date . 'minute', strtotime($datetime));
                    $to_datetime = date('Y-m-d H:i:s',
                        strtotime('-' . $trigger_time_date . 'minute', strtotime($datetime)));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "5 minutes", strtotime($to_datetime)));
                    $appointments->whereBetween('end_datetime', array($from_datetime, $to_datetime));
                } else { //Before
                    $utc_datetime = strtotime('+' . $trigger_time_date . 'minute', strtotime($datetime));
                    $to_datetime = date('Y-m-d H:i:s',
                        strtotime('+' . $trigger_time_date . 'minute', strtotime($datetime)));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "5 minutes", strtotime($to_datetime)));
                    $appointments->whereBetween('start_datetime', array($from_datetime, $to_datetime));
                }
            } else {
                $format = 'Y-m-d';
                $datetime = self::convertDateFromTimezone($date, $timezone, $timezone_to, $format);
                if ($time_schedule == 1) { //After
                    $appointments->whereRaw(\DB::raw('DATE_FORMAT(end_datetime,"%Y-%m-%d") = DATE_FORMAT(DATE_SUB("' . $datetime . '", INTERVAL ' . $trigger_time_date . ' ' . $unit . '),"%Y-%m-%d")'));
                } else { //Before
                    $appointments->whereRaw(\DB::raw('DATE_FORMAT(start_datetime,"%Y-%m-%d") = DATE_FORMAT(DATE_ADD("' . $datetime . '", INTERVAL ' . $trigger_time_date . ' ' . $unit . '),"%Y-%m-%d")'));
                }
            }

            $appointments->where('company_id', $company_id);
            $appointments->whereHas('contacts', function ($query) {
                $query->whereNull('dnd')->orWhere('dnd', '0');
            });

            if ($funnel_list != null && !empty($funnel_list)) {
                $funnel_list_array = array();
                foreach ($funnel_list as $list_item) {
                    $funnel_list_array[] = $list_item['list_id'];
                }

                $appointments->whereHas('contacts.assignlist', function ($query) use ($funnel_list_array) {
                    $query->whereIn('list_id', $funnel_list_array);
                });
            }

            $appointments->whereNotIn('appointments.contact_id', function ($query) use ($action_id) {
                $query->select('em_action_funnel_queue.contact_id')
                    ->from('em_action_funnel_queue')
                    ->join('appointments as apt', 'em_action_funnel_queue.appointment_id', '=', 'apt.id')
                    ->whereRaw("em_action_funnel_queue.appointment_id = appointments.id")
                    ->whereRaw("em_action_funnel_queue.contact_id = appointments.contact_id")
                    ->where('em_action_funnel_queue.action_id', $action_id);
            });

            $appointments = $appointments->get();
            if ($appointments->count() > 0) {
                return $appointments->toArray();
            }

            $appointments = array();
            return $appointments;
        }
    }

    private static function getUnitType($trigger_type)
    {
        if ($trigger_type == 3) {
            return 'minute';
        } elseif ($trigger_type == 2) {
            return 'hour';
        } else {
            return 'day';
        }
    }

    private static function convertDateFromTimezone($date, $timezone, $timezone_to, $format)
    {
        $date = new \DateTime('NOW', new \DateTimeZone($timezone_to));
        return $date->format($format);
    }

    private static function renderContactsForDoAction($contacts, $opportunity = false, $appointment = false)
    {
        $funnel_queue_object = array();
        if (count($contacts) > 0) {
            $i = 0;
            foreach ($contacts as $contact) {
                $funnel_queue_object[$i]['contact_id'] = $contact['contact_id'];
                if ($opportunity) {
                    $funnel_queue_object[$i]['opportunity'] = $contact['id'];
                }
                if ($appointment) {
                    $funnel_queue_object[$i]['appointment_id'] = $contact['id'];
                }
                $i++;
            }
        }
        return $funnel_queue_object;
    }

    private static function doActionOnContacts(
        $contacts,
        $action,
        $company_id,
        $funnel_id,
        $opportunity = false,
        $appointment = false
    ) {
        if ($action->action_type == 3) {
            $do_action = $action->do_action;
            $tag_id = $action->tag_id;
            $list_id = $action->list_id;
            if ($do_action == 'addtolist') {
                self::AddContactsToList($contacts, $list_id, $company_id, $funnel_id, $action->id, $opportunity,
                    $appointment);
            } elseif ($do_action == 'removefromlist') {
                self::RemoveContactsFromList($contacts, $list_id, $company_id, $funnel_id, $action->id, $opportunity,
                    $appointment);
            } elseif ($do_action == 'addtag') {
                self::AddTagToContacts($contacts, $tag_id, $company_id, $funnel_id, $action->id, $opportunity,
                    $appointment);
            } elseif ($do_action == 'removetag') {
                self::RemoveTagFromContacts($contacts, $tag_id, $company_id, $funnel_id, $action->id, $opportunity,
                    $appointment);
            }
        }
    }

    private static function AddContactsToList(
        $contacts,
        $list_id,
        $company_id,
        $funnel_id,
        $action_id,
        $opportunity = false,
        $appointment = false
    ) {
        $contacts_array = array();
        $records = array();
        $i = 0;
        foreach ($contacts as $contact) {
            if (isset($contact['contact_id']) && !empty($list_id)) {
                $exists = EmNewsletterContacts::where('contact_id', $contact['contact_id'])->where('list_id',
                    $list_id)->count();
                if ($exists == 0) {
                    $contacts_array = array(
                        'company_id' => $company_id,
                        'contact_id' => $contact['contact_id'],
                        'list_id' => $list_id,
                        'status_id' => '1',
                        'created_at' => date('Y-m-d H:i:s')
                    );
                    EmNewsletterContacts::insert($contacts_array);
                    $records[$i] = array(
                        'action_type' => '3',
                        'contact_id' => $contact['contact_id'],
                        'action_id' => $action_id,
                        'funnel_id' => $funnel_id,
                        'status' => 2,
                        'created_at' => date('Y-m-d H:i:s')
                    );
                    if ($opportunity == true) {
                        $records[$i]['opportunity_id'] = $contact['opportunity'];
                    }
                    if ($appointment == true) {
                        $records[$i]['appointment_id'] = $contact['appointment'];
                    }
                    $i++;
                }
            }
        }
        if (count($records) > 0) {
            EmActionFunnelQueue::insert($records);
        }
    }

    private static function RemoveContactsFromList(
        $contacts,
        $list_id,
        $company_id,
        $funnel_id,
        $action_id,
        $opportunity = false,
        $appointment = false
    ) {
        $contacts_array = array();
        $records = array();
        $i = 0;
        foreach ($contacts as $contact) {
            if (isset($contact['contact_id']) && !empty($list_id)) {
                $contact_in_list = EmNewsletterContacts::where('contact_id',
                    $contact['contact_id'])->where('company_id', $company_id)->where('list_id', $list_id)->first();
                if ($contact_in_list) {
                    $contacts_array[$i] = $contact_in_list->id;
                    $records[] = array(
                        'action_type' => '3',
                        'contact_id' => $contact['contact_id'],
                        'action_id' => $action_id,
                        'funnel_id' => $funnel_id,
                        'status' => 2
                    );
                    if ($opportunity == true) {
                        $records[$i]['opportunity_id'] = $contact['opportunity'];
                    }
                    if ($appointment == true) {
                        $records[$i]['appointment_id'] = $contact['appointment'];
                    }
                    $i++;
                }
            }
        }
        if (count($contacts_array) > 0) {
            EmNewsletterContacts::whereIn('id', $contacts_array)->delete();
            EmActionFunnelQueue::insert($records);
        }
    }

    private static function AddTagToContacts(
        $contacts,
        $tag_id,
        $company_id,
        $funnel_id,
        $action_id,
        $opportunity = false,
        $appointment = false
    ) {
        $contacts_array = array();
        $records = array();
        $i = 0;

        foreach ($contacts as $contact) {
            if (isset($contact['contact_id']) && !empty($tag_id)) {
                $exists = ContactTag::where('contact_id', '=', $contact['contact_id'])->where('tag_id', '=',
                    $tag_id)->count();
                if ($exists == 0) {
                    $contacts_array = array('contact_id' => $contact['contact_id'], 'tag_id' => $tag_id);
                    ContactTag::insert($contacts_array);

                    $records[$i] = array(
                        'action_type' => '3',
                        'contact_id' => $contact['contact_id'],
                        'action_id' => $action_id,
                        'funnel_id' => $funnel_id,
                        'status' => 2
                    );
                    if ($opportunity == true) {
                        $records[$i]['opportunity_id'] = $contact['opportunity'];
                    }
                    if ($appointment == true) {
                        $records[$i]['appointment_id'] = $contact['appointment'];
                    }
                    $i++;
                }
            }
        }
        if (count($records) > 0) {
            EmActionFunnelQueue::insert($records);
        }
    }

    private static function RemoveTagFromContacts(
        $contacts,
        $tag_id,
        $company_id,
        $funnel_id,
        $action_id,
        $opportunity = false,
        $appointment = false
    ) {
        $contacts_array = array();
        $records = array();
        $i = 0;
        foreach ($contacts as $contact) {
            if (isset($contact['contact_id']) && !empty($tag_id)) {
                $exists = ContactTag::where('contact_id', $contact['contact_id'])->where('tag_id', $tag_id)->count();
                if ($exists == 0) {
                    $contact_tag = ContactTag::where('contact_id', $contact['contact_id'])->where('tag_id',
                        $tag_id)->first();
                    $contacts_array[] = $contact_tag->id;
                    $records[$i] = array(
                        'action_type' => '3',
                        'contact_id' => $contact['contact_id'],
                        'action_id' => $action_id,
                        'funnel_id' => $funnel_id,
                        'status' => 2
                    );
                    if ($opportunity == true) {
                        $records[$i]['opportunity_id'] = $contact['opportunity'];
                    }
                    if ($appointment == true) {
                        $records[$i]['appointment_id'] = $contact['appointment'];
                    }
                    $i++;
                }
            }
        }
        if (count($contacts_array) > 0) {
            ContactTag::whereIn('id', $contacts_array)->delete();
            EmActionFunnelQueue::insert($records);
        }
    }

    private static function processContacts(
        $funnel_id,
        $action,
        $contacts,
        $is_appointment = true,
        $apt_status = '',
        $is_opp = false,
        $company = null
    ) {
        $funnel_queue_object = array();
        $i = 0;
        foreach ($contacts as $item) {
            if ($action->action_type == 1 && empty($action->email_body)) {
                continue;
            } //if email body empty skip record for action type email
            if ($action->action_type == 2 && empty($action->sms_text) && empty($item['mobile_number'])) {
                continue;
            } //if sms body empty skip record for action type sms

            $funnel_queue_object[$i]['action_id'] = $action->id;
            $funnel_queue_object[$i]['funnel_id'] = $funnel_id;
            $funnel_queue_object[$i]['contact_id'] = $item['contact_id'];
            if (isset($item['list_id']) && !empty(isset($item['list_id']))) {
                $funnel_queue_object[$i]['list_id'] = $item['list_id'];
            }

            $message = '';
            if (!empty($action->sms_text)) {
                $message = $action->sms_text;
            } else {
                $message = $action->email_body;
            }

            if (isset($item['contacts']) && isset($item['contacts']['first_name'])) {
                $message = str_replace('{$first_name}', $item['contacts']['first_name'], $message);
            } else {
                $message = str_replace('{$first_name}', '', $message);
            }

            if (isset($item['contacts']) && isset($item['contacts']['last_name'])) {
                $message = str_replace('{$last_name}', $item['contacts']['last_name'], $message);
            } else {
                $message = str_replace('{$last_name}', '', $message);
            }

            if (!empty($company)) {
                $message = str_replace('{$client_name}', $company->name, $message);
                $message = str_replace('{$location}', $company->address, $message);
                $phone = $company->phone_country_code . $company->phone;
                $message = str_replace('{$office_phone}', $phone, $message);
                $message = str_replace('{$website_link}', $company->site_url, $message);
            } elseif (!$is_appointment && empty($company)) {
                $message = str_replace('{$client_name}', '', $message);
                $message = str_replace('{$location}', '', $message);
                $message = str_replace('{$office_phone}', '', $message);
                $message = str_replace('{$website_link}', '', $message);
            }

            if ($is_appointment) {
                $message = str_replace('{$status}', $apt_status, $message);
                if (isset($item['start_datetime'])) {
                    $sch_datatime = date('F dS, Y \a\t h:i A', strtotime($item['start_datetime']));
                    $message = str_replace('{$datetime}', $sch_datatime, $message);
                }
                if (isset($item['company'])) {
                    $message = str_replace('{$client_name}', $item['company']['name'], $message);
                    $message = str_replace('{$location}', $item['company']['address'], $message);
                    $message = str_replace('{$office_phone}', $item['company']['phone'], $message);
                    $message = str_replace('{$website_link}', $item['company']['site_url'], $message);
                }
                if ($is_opp == true) {
                    $funnel_queue_object[$i]['opportunity_id'] = $item['id'];
                } else {
                    $funnel_queue_object[$i]['appointment_id'] = $item['id'];
                }
            }
            if (isset($item['uuid']) && !empty($item['uuid'])) {
                $unsubscribe_anchor = "<a href='" . getenv('API_URL') . "/unsubscribe/" . $item['uuid'] . "'>unsubscribe</a>";
                $message = str_replace('{$unsubscribe_link}', $unsubscribe_anchor, $message);
            } else {
                $base_encoded_contact_id = base64_encode($item['contact_id'] . '&' . $funnel_id);
                $unsubscribe_anchor = "<a href='" . getenv('API_URL') . "/unsubscribe/contact/" . $base_encoded_contact_id . "'>unsubscribe</a>";
                $message = str_replace('{$unsubscribe_link}', $unsubscribe_anchor, $message);
            }


            $funnel_queue_object[$i]['action_type'] = $action->action_type; //  (1-Email,2-Txt,3-Action)

            if (!empty($action->email_body)) {
                $funnel_queue_object[$i]['email_body'] = $message;
            } else {
                $funnel_queue_object[$i]['email_body'] = '';
            }

            if (!empty($action->sms_text)) {
                $funnel_queue_object[$i]['sms'] = $message;
            } else {
                $funnel_queue_object[$i]['sms'] = '';
            }

            $funnel_queue_object[$i]['subject'] = $action->email_subject;
            $funnel_queue_object[$i]['status'] = 1; //status pending in queue
            $funnel_queue_object[$i]['open_status'] = 0;
            $funnel_queue_object[$i]['created_at'] = date('Y-m-d H:i:s');
            $i++;
        }
        return $funnel_queue_object;
    }

    /**FUNCTION TO GET CONTACT FORM OPPORTUNITY**/

    private static function GetOpportunityContacts(
        $opportunity_source_id,
        $opportunity_service_id,
        $opportunity_stage_id,
        $opportunity_status,
        $trigger_time_date,
        $trigger_time_unit,
        $time_schedule,
        $company_id,
        $action_id,
        $funnel_list = null
    ) {
        $timezone_obj = \App\CompanySetting::select('name', 'value')->where('company_id', $company_id)->where('name',
            'timezone')->first();
        $date = date('Y-m-d H:i');
        $timezone = 'UTC';
        $timezone_to = $timezone_obj->value;
        $appointments = array();

        $leadObject = \App\Lead::with('contacts.assignlist', 'company');
        if (!empty($opportunity_stage_id)) {
            $leadObject->where('stage_id', $opportunity_stage_id);
        }
        if (!empty($opportunity_source_id)) {
            $leadObject->where('source_id', $opportunity_source_id);
        }
        if (!empty($opportunity_service_id)) {
            $leadObject->where('service_id', $opportunity_service_id);
        }
        if (!empty($opportunity_status)) {
            $leadObject->where('status_id', $opportunity_status);
        }
        $unit = self::getUnitType($trigger_time_unit);

        //$unit = $trigger_time_unit == 2 ? 'hour' : 'day';

        /**Scheduled query datetime function **/
        $leadObject = self::scheduleQueryObject($unit, $leadObject, $timezone, $timezone_to, $trigger_time_date, $date,
            $time_schedule);

        $leadObject->where('company_id', $company_id);

        $leadObject->whereHas('contacts', function ($query) {
            $query->whereNull('dnd')->orWhere('dnd', '0');
        });
        if ($funnel_list != null && !empty($funnel_list)) {
            $funnel_list_array = array();
            foreach ($funnel_list as $list_item) {
                $funnel_list_array[] = $list_item['list_id'];
            }

            $leadObject->whereHas('contacts.assignlist', function ($query) use ($funnel_list_array) {
                $query->whereIn('list_id', $funnel_list_array);
            });
        }
        $leadObject->whereNotIn('leads.contact_id', function ($query) use ($action_id) {
            $query->select('em_action_funnel_queue.contact_id')
                ->from('em_action_funnel_queue')
                ->join('leads as led', 'em_action_funnel_queue.opportunity_id', '=', 'led.id')
                ->whereRaw("em_action_funnel_queue.opportunity_id = leads.id")
                ->whereRaw("em_action_funnel_queue.contact_id = leads.contact_id")
                ->where('em_action_funnel_queue.action_id', $action_id);
        });

        $leadObject = $leadObject->get();
        if ($leadObject->count() > 0) {
            return $leadObject->toArray();
        }

        $leadObject = array();
        return $leadObject;
    }

    private static function scheduleQueryObject(
        $unit,
        $object,
        $timezone,
        $timezone_to,
        $trigger_time_date,
        $date,
        $time_schedule
    ) {
        if ($unit == 'hour') {
            $to_datetime = date('Y-m-d H:i:s');
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $trigger_time_date . 'hour'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "15 minutes", strtotime($utc_datetime)));
            $object->whereBetween('created_at', array($from_datetime, $utc_datetime));
        } elseif ($unit == 'minute') {
            $to_datetime = date('Y-m-d H:i:s');
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $trigger_time_date . 'min'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "5 minutes", strtotime($utc_datetime)));
            $object->whereBetween('created_at', array($from_datetime, $utc_datetime));
        } else {
            $format = 'Y-m-d';
            $datetime = date($format);
            $object->whereRaw(\DB::raw('DATE_FORMAT(created_at,"%Y-%m-%d") = DATE_FORMAT(DATE_SUB("' . $datetime . '", INTERVAL ' . $trigger_time_date . ' ' . $unit . '),"%Y-%m-%d")'));
        }
        return $object;
    }

    private static function processList($action, $funnel_id, $funnel_list, $company_id, $company)
    {
        $i = 0;
        $funnel_queue_object_final = array();
        $data = array();
        foreach ($funnel_list as $item) {
            $funnel_queue_object = array();
            if (isset($item['listd']) && !empty($item['listd'])) {
                //$unit        = (isset($action) && $action->trigger_type == 2) ? 'hour' : 'day';
                $unit = self::getUnitType($action->trigger_type);

                $contacts = \App\EmNewsletterContacts::with('contacts')->select('contact_id', 'id', 'uuid',
                    'list_id')->where('list_id', $item['list_id']);

                if ($unit == 'hour') {
                    $to_datetime = date('Y-m-d H:i:s');
                    $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'hour'));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "10 minutes", strtotime($utc_datetime)));
                    $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
                } elseif ($unit == 'minute') {
                    $to_datetime = date('Y-m-d H:i:s');
                    $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'minute'));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "5 minutes", strtotime($utc_datetime)));
                    $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
                } else {
                    $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'day'));
                    $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "15 minutes", strtotime($utc_datetime)));
                    $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
                }

                $contacts->whereNotIn('contact_id', function ($query) use ($action) {
                    $query->select('contact_id')->from('em_action_funnel_queue')->where('action_id', $action->id);
                });

                $contacts->where('company_id', '=', $company_id);
                $contacts->where('status_id', '=', '1');
                $contacts = $contacts->get();

                if ($contacts->count() > 0) {
                    $contacts = $contacts->toArray();
                    if ($action->action_type == 3) {
                        foreach ($contacts as $contact) {
                            if (isset($contact['contact_id'])) {
                                $funnel_queue_object[$i]['contact_id'] = $contact['contact_id'];
                                $i++;
                            }
                        }
                    } else {
                        $contacts_array = self::processContacts($funnel_id, $action, $contacts, false, '', false,
                            $company);

                        if ($contacts_array != false) {
                            foreach ($contacts_array as $contact) {
                                $funnel_queue_object[] = $contact;
                            }
                        }
                    }
                }
            }
            if ($funnel_queue_object) {
                $funnel_queue_object_final = array_merge($funnel_queue_object_final, $funnel_queue_object);
            }
        }

        return $funnel_queue_object_final;
    }

    private static function processCompanyContacts($action, $funnel_id, $company_id, $company)
    {

        $funnel_queue_object = array();
        $timezone = 'UTC';
        $unit = self::getUnitType($action->trigger_type);
        $contacts = \App\Contact::with('contacts')->select('id as contact_id')->where('company_id', $company_id);
        if ($unit == 'hour') {
            $to_datetime = date('Y-m-d H:i:s');
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'hour'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "10 minutes", strtotime($utc_datetime)));
            $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
        } elseif ($unit == 'minute') {
            $to_datetime = date('Y-m-d H:i:s');
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'minute'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "5 minutes", strtotime($utc_datetime)));
            $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
        } else {
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'day'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "15 minutes", strtotime($utc_datetime)));
            $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
        }

        $contacts->whereNotIn('id', function ($query) use ($action) {
            $query->select('contact_id')->from('em_action_funnel_queue')->where('action_id', $action->id);
        });

        $contacts = $contacts->get();

        if ($contacts->count() > 0) {
            $contacts = $contacts->toArray();

            if ($action->action_type == 3) {
                foreach ($contacts as $contact) {
                    $funnel_queue_object[] = array('contact_id' => $contact['contact_id']);
                }
                self::doActionOnContacts($funnel_queue_object, $action, $company_id, $funnel_id);
                return array();
            } else {
                $contacts_array = current(self::processContacts($funnel_id, $action, $contacts, false, '', '',
                    $company));
                if ($contacts_array != false) {
                    $funnel_queue_object[] = $contacts_array;
                }
            }
        }
        return $funnel_queue_object;
    }

    private static function CopyRecorsToFunnelQueue($records)
    {
        EmActionFunnelQueue::insert($records);
    }

    public static function getAppointmentStatus()
    {
        $statuses = \App\AppointmentStatus::select('id', 'title')->get();
        if ($statuses->count() > 0) {
            $statuses = $statuses->toArray();
            return $statuses;
        }
        $empty_status = array();
        return $empty_status;
    }

    public static function processDoActionContacts($action, $company_id)
    {
        $i = 0;
        $funnel_queue_object_final = array();
        $data = array();
        $funnel_queue_object = array();

        $timezone = 'UTC';
        $unit = self::getUnitType($action->trigger_type);
        //$unit = (isset($action) && $action->trigger_type == 2) ? 'hour' : 'day';

        $contacts = \App\Contact::select('id as contact_id');

        if ($unit == 'hour') {
            $to_datetime = date('Y-m-d H:i:s');
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'hour'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "10 minutes", strtotime($utc_datetime)));
            $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
        } elseif ($unit == 'minute') {
            $to_datetime = date('Y-m-d H:i:s');
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'minute'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "5 minutes", strtotime($utc_datetime)));
            $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
        } else {
            $utc_datetime = date('Y-m-d H:i:s', strtotime('-' . $action->trigger_value . 'day'));
            $from_datetime = date('Y-m-d H:i:s', strtotime('-' . "15 minutes", strtotime($utc_datetime)));
            $contacts->whereBetween('created_at', array($from_datetime, $utc_datetime));
        }

        $contacts->where('company_id', $company_id);
        $contacts->whereNotIn('id', function ($query) use ($action) {
            $query->select('contact_id')->from('em_action_funnel_queue')->where('action_id', $action->id);
        });

        $contacts = $contacts->get();
        if ($contacts->count() > 0) {
            $contacts = $contacts->toArray();
            foreach ($contacts as $contact) {
                $funnel_queue_object[] = $contact['contact_id'];
            }
            $i++;
        }

        return $funnel_queue_object;
    }

    public static function fetchSentItemsListing($funnel_id, $action_id, $type, $status)
    {
        $em_actionfunnel_queue_array = EmActionFunnelQueue::with('action', 'contactList')->select('action_type',
            'contact_id', 'id', 'funnel_id', 'status', 'action_id', 'created_at', 'open_status',
            'click_status')->where('funnel_id', $funnel_id)->where('action_id', $action_id)->where('action_type',
            $type)->orderBy('created_at', 'desc')->get();
        if ($em_actionfunnel_queue_array->count() > 0) {
            $em_actionfunnel_queue_array = $em_actionfunnel_queue_array->toArray();
        }
        return $em_actionfunnel_queue_array;
    }

    public static function getSentEmailCount($funnel_id, $action_id, $type, $status)
    {
        $sent_email_count = EmActionFunnelQueue::where('funnel_id', $funnel_id)->where('action_id',
            $action_id)->where('action_type', $type)->where('status', $status)->count();
        $open_status = EmActionFunnelQueue::where('open_status', '1')->where('funnel_id',
            $funnel_id)->where('action_id', $action_id)->where('action_type', $type)->count();
        $click_status = EmActionFunnelQueue::where('click_status', '1')->where('funnel_id',
            $funnel_id)->where('action_id', $action_id)->where('action_type', $type)->count();
        return array('sent' => $sent_email_count, 'clicked' => $click_status, 'opened' => $open_status);
    }

    public static function SendActionMailOrMessage()
    {
        //1-Email,2-Txt,3-Action
        //1=pending,2=sent,3=error
        $sentcount = 0;
        $smscount = 0;
        $rejectedsms = 0;
        $notfoundrecords = 0;
        $action_funnel_records = EmActionFunnelQueue::with('contact', 'funnel', 'apikeyexists')
            ->where('status', '1')
            ->take(getenv('EMAILMARKETING_JOB_BATCH_SIZE'))
            ->get();

        if ($action_funnel_records->count() > 0) {
            $action_funnel_records = $action_funnel_records->toArray();
            foreach ($action_funnel_records as $record) {
                if ($record['action_type'] == 1) {
                    $funnel = EmFunnel::where('id', $record['funnel_id'])->first();
                    if (!empty($funnel)) {
                        $company_id = $funnel->company_id;
                        $token = EmailMarketingHelper::fetchSendGridApiKey($company_id);
                        if (!empty($token)) {
                            $from_email = EmailMarketingHelper::fetchCompanyEmail($company_id);
                            $from_name = EmailMarketingHelper::fetchCompanyName($company_id);
                            $data['content_data'] = $record['email_body'];
                            $subject = !empty($record['subject']) ? $record['subject'] : 'No Subject';
                            $user_email = $record['contact']['email'];
                            if (!empty($user_email) && !empty($record['email_body'])) {
                                /**Send email to user**/
                                $response_id = EmailMarketingHelper::MailViaSendGrid($data, $user_email, $from_email,
                                    $subject, $token, $company_id, $from_name);
                                if ($response_id) {
                                    EmActionFunnelQueue::where('id', $record['id'])->update([
                                        'response_id' => $response_id,
                                        'status' => 2,
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                                    $sentcount++;
                                } else {
                                    EmActionFunnelQueue::where('id', $record['id'])->update([
                                        'response_id' => 0,
                                        'status' => 3,
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                                    $sentcount++;
                                }
                            } else {
                                EmActionFunnelQueue::where('id', $record['id'])->update([
                                    'response_id' => 0,
                                    'status' => 3,
                                    'updated_at' => date('Y-m-d H:i:s')
                                ]);
                                $notfoundrecords++;
                            }
                        } else {
                            EmActionFunnelQueue::where('id', $record['id'])->update([
                                'response_id' => 0,
                                'status' => 3,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            $notfoundrecords++;
                        }
                    }
                } elseif ($record['action_type'] == 2) {
                    $user_phone = $record['contact']['mobile_number'];
                    $company_id = $record['funnel']['company_id'];
                    $sms_message = $record['sms'];
                    $contact_id = $record['contact']['id'];
                    $response_id = 0;

                    if (!empty($sms_message) && !empty($user_phone)) {
                        try {
                            SmsHelper::sendSms($user_phone, $sms_message, $company_id, 'funnel_sms_notification',
                                $contact_id);
                            EmActionFunnelQueue::where('id', $record['id'])->update([
                                'response_id' => $response_id,
                                'status' => 2,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            $smscount++;
                        } catch (\Exception $e) {
                            \Log::emergency("Funnel Sms Not receiving:" . $company_id . "-" . $e->getMessage());
                            EmActionFunnelQueue::where('id', $record['id'])->update([
                                'response_id' => $response_id,
                                'status' => 3,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                            $rejectedsms++;
                        }
                    } else {
                        EmActionFunnelQueue::where('id', $record['id'])->update([
                            'response_id' => $response_id,
                            'status' => 3,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        $rejectedsms++;
                    }
                }
            }
        }
        print_r('Total record send: ' . $sentcount);
        print_r('<br/>Total sms send: ' . $smscount);
        print_r('<br/>Not found/reject contact: ' . $notfoundrecords);
        print_r('<br/>reject Sms: ' . $rejectedsms);
        \Log::info("Funnel scheduler run  UTC : " . date('Y-m-d H:i:s'));
        \Log::info("Funnel scheduler email send  : " . $sentcount);
        \Log::info("Funnel scheduler sms send  : " . $smscount);
        \Log::info("Funnel scheduler not found records  : " . $notfoundrecords);
        \Log::info("Funnel scheduler sms reject records  : " . $rejectedsms);
    }

    public static function getFunnelStepById($funnel_id, $step_id, $company_id)
    {
        $exists = EmFunnel::where('company_id', $company_id)->where('id', $funnel_id)->count();
        if ($exists) {
            $funnelstep = EmFunnelAction::where('id', $step_id)->where('funnel_id', $funnel_id)->first();
            if ($funnelstep) {
                return $funnelstep->toArray();
            }
            $empty = array();
            return $empty;
        }
        return false;
    }

    public static function updateCompanyTemplate($company_id, $input_data)
    {
        $template = CompanyTemplate::where('id', '=', $input_data['template_id'])->where('company_id', '=',
            $company_id)->where('category', '=', $input_data['category'])->first();
        if ($template) {
            /* if (empty($template->company_id)) {
                 $templateArray = $template->toArray();
                 unset($templateArray['id']);
                 $templateArray['company_id'] = $company_id;
                 $input_data['template_id'] = CompanyTemplate::insertGetId($templateArray);
             }*/
            $preview_image_name = uniqid() . '.png';
            $company_template = CompanyTemplate::find($input_data['template_id']);
            $company_template->html_body = $input_data['html_body'];
            $company_template->json_body = $input_data['json_body'];
            $company_template->category = $input_data['category'];
            $company_template->preview_image = $preview_image_name;
            $company_template->save();

            if ($company_template->id) {
                EmailMarketingHelper::generatePreview('template-preview', $company_id, $company_template->id,
                    $preview_image_name);
                return true;
            }
            return false;
        }
    }

    public static function DeleteCompanyTemplate($company_id, $input_data)
    {
        $template = CompanyTemplate::where('id', '=', $input_data['template_id'])->where('category',
            $input_data['category'])->where('company_id', '=', $company_id)->first();
        if ($template) {
            $template = CompanyTemplate::where('id', '=', $input_data['template_id'])->where('category',
                $input_data['category'])->where('company_id', '=', $company_id)->delete();
            return true;
        }
        return false;
    }
}
