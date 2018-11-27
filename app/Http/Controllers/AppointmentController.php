<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\AppointmentsDt;
use App\AppointmentService;
use App\AppointmentStatus;
use App\Classes\ActivityHelper;
use App\Classes\CompanyHelper;
use App\Classes\CompanySettingsHelper;
use App\Classes\ContactHelper;
use App\Classes\LeadHelper;
use App\Classes\NotificationHelper;
use App\Classes\SmsHelper;
use App\Classes\UserHelper;
use App\Company;
use App\Contact;
use App\InsuranceCompany;
use App\User;
use App\UserSetting;
use Auth;
use Datatables;
use dateTime;
use Illuminate\Http\Request;
use Input;
use Mail;

class AppointmentController extends Controller
{
    public function postIndex()
    {
        $user = Auth::user();
        $user_id = $user->id;
        $company_id = $user->company_id;
        $user_role = $user
            ->roles()
            ->select(['slug'])
            ->first()->toArray();
        if ($user_role['slug'] == 'doctor') {
            $appointments = AppointmentsDt::
            select('appointment_status_id', 'provider_name', 'appointment_status', 'provider_user_id', 'contact_name', 'contact_id', 'book_datetime', 'start_datetime', 'mobile_number', 'email', 'is_existing', 'scheduling_method', 'id', 'contact_type', 'phone_country_code')
                ->where('company_id', $company_id)
                ->where('provider_user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $appointments = AppointmentsDt::
            select('appointment_status_id', 'provider_name', 'appointment_status', 'provider_user_id', 'contact_name', 'contact_id', 'book_datetime', 'start_datetime', 'mobile_number', 'email', 'is_existing', 'scheduling_method', 'id', 'contact_type', 'phone_country_code')
                ->where('company_id', $company_id)
                ->orderBy('id', 'desc')
                ->get();
        }
        return Datatables::of($appointments)->make(true);
    }

    public function getAppointmentstatus()
    {
        $appointmentstatus = AppointmentStatus::all();

        return response()->success(compact('appointmentstatus'));
    }

    public function getShow($id)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        $appointment = Appointment::find($id);
        if ($appointment->company_id != $company_id) {
            return response()->error('Wrong Appointment Id');
        }

        $appointment_statuses = AppointmentStatus::all();
        $appointment['appointment_reason'] = $appointment
            ->appointment_reason()
            ->select(['title'])
            ->get();

        $appointment['contacts'] = $appointment
            ->contacts()
            ->get();
        $appointment['provider_user'] = $appointment
            ->appointment_provider()
            ->select(['name'])
            ->get();
        $appointment['appointment_status'] = $appointment
            ->appointment_status()
            ->select(['title'])
            ->get();
        $appointment['all_status'] = $appointment_statuses;
        $contact_id = $appointment['contacts'][0]['id'];
        $appointment['sms_list'] = SmsHelper::get_contact_sms($contact_id);
        return response()->success($appointment);
    }

    public function putUpdatestatus($app_id = null, $app_sts = null)
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        if ($app_id == null) {
            $appointment_id = Input::get('appointment_id');
        } else {
            $appointment_id = $app_id;
        }
        if ($app_sts == null) {
            $appointment_status = Input::get('appointment_status');
        } else {
            $appointment_status = $app_sts;
        }
        $update_data = array('appointment_status_id' => intval($appointment_status));
        $affectedRows = Appointment::where('id', '=', intval($appointment_id))->update($update_data);

        /// sucess mail on status change
        $detailInformation = Appointment::with('contacts', 'appointment_status', 'company')->where('id', '=', intval($appointment_id))->first()->toArray();

        /* *********************** Add Activity *********************** */
        $contact_id = $detailInformation['contacts']['id'];
        $doc_id = $detailInformation['provider_user_id'];
        $activity_type = $detailInformation['appointment_status']['activity_type'];
        ActivityHelper::createActivity($company_id, $activity_type, 'appointment', $appointment_id, $contact_id, $doc_id, $user->id);
        /* *********************** / Add Activity *********************** */

        $lead_changes_status = [2];
        if (in_array(intval($appointment_status), $lead_changes_status)) {
            $lead_details = LeadHelper::getLeadByContact($contact_id, $company_id);
            if ($lead_details !== false) {
                $stage_id = LeadHelper::getStageBySlug($company_id, 'prospects');
                LeadHelper::update_lead($lead_details['id'], $company_id, $stage_id, null, null, null, null, null);
            }
        }
        $user_information = $detailInformation['contacts'];
        $company_information = $detailInformation['company'];
        $new_status = $detailInformation['appointment_status']['title'];
        $replaceArr = array(
            'first_name' => $user_information['first_name'],
            'last_name' => $user_information['last_name'],
            'status' => $new_status,
            'client_name' => $company_information['name'],
            'date' => date('F dS, Y', strtotime($detailInformation['start_datetime'])),
            'time' => date('h:i A', strtotime($detailInformation['start_datetime'])),
            'location' => $company_information['address'],
            'office_phone' => maskPhoneNumber($company_information['phone']),
            'website_link' => $company_information['site_url'],
        );

        $message = NotificationHelper::getNotificationMethod($company_id, 'appointement_status', $detailInformation['appointment_status']['activity_type']);
        $subject = NotificationHelper::getNotificationSubject($company_id, 'appointement_status', $detailInformation['appointment_status']['activity_type']);

        if ($message != false && $subject != false) {
            $message = nl2br($message);
            foreach ($replaceArr as $key => $val) {
                $message = str_replace('{$' . $key . '}', $val, $message);
            }
            foreach ($replaceArr as $key => $val) {
                $subject = str_replace('{$' . $key . '}', $val, $subject);
            }

            $data = array();
            $data['content_data'] = $message;
            $data['new_status'] = $new_status;
            $data['user_information'] = $user_information;
            $data['company_information'] = $company_information;
            $app_from_email = app_from_email();
            $bcc_email = getenv('BCC_EMAIL');
            if ($user_information['dnd'] != "1") {
                $email_content = compact('data');
                $template = 'emails.booking_status_updated';
                \App\Classes\CompanySettingsHelper::sendClientEmailNotifcation($company_id, $user_information['email'], $email_content, $subject, $bcc_email, $template, $app_from_email, $email_type = null);
                \App\Classes\CompanyHelper::recordNotificationLog($appointment_id, 'appointment', $detailInformation['appointment_status']['activity_type'], 'mail', $company_id, $user_information['email'], $message, $subject, $contact_id, $appointment_id);
            }
        }

        /*Send SMS*/

        $sms_message = NotificationHelper::getNotificationMethod($company_id, 'sms', 'APPOINTMENT_APPROVAL_SMS');
        if ($appointment_status == 1 && $sms_message && $user_information['dnd'] != "1") {
            foreach ($replaceArr as $key => $val) {
                $sms_message = str_replace('{$' . $key . '}', $val, $sms_message);
            }
            $sms_id = SmsHelper::sendSms($user_information['mobile_number'], strip_tags($sms_message), $company_id, 'APPOINTMENT_APPROVAL_SMS', $user_information['id']);
        }


        return response()->success('success');
    }

    /** Add New Web Appointment **/
    public function webAppointmentAdd(Request $request)
    {
        $company_id = $request['company_id'];
        $input = $request['data'];
        $this->validate(
            $request,
            [
                'data.first_name' => 'required',
                'data.last_name' => 'required',
                'data.email' => 'required|email',
                'data.mobile_number' => 'required|min:9',
                'data.is_existing' => 'required|int',
                'data.start_datetime' => 'required',
                'data.end_datetime' => 'required',
                'data.provider_user_id' => 'required',
            ]
        );

        /* ********* From utitlity ********* */
        $default_phone_country_code = default_phone_country_code();
        $input_phone_number = $input['mobile_number'];
        $phone_number = $default_phone_country_code . format_phone_number($input_phone_number);

        if (isset($input['phone_country_code'])) {
            $default_phone_country_code = $input['phone_country_code'];
        }

        $provider_id = $input['provider_user_id'];

        /*if Appointment Exists in give slot time */
        $is_exists = Appointment::where('provider_user_id', $provider_id)
            ->where('start_datetime', '<=', $input['start_datetime'])
            ->where('end_datetime', '>=', $input['end_datetime'])
            ->where('company_id', '=', $company_id)
            ->first();
        if (count($is_exists) > 0) {
            return response()->error('Appointment already exists in this slot');
            die();
        }
        /* Appointment Reason */
        $reason_exists = AppointmentService::where('title', $input['appointment_reason'])->count();
        if ($reason_exists > 0) {
            $reason = AppointmentService::where('title', $input['appointment_reason'])->select('id')->first();
            $reason_id = $reason->id;
        } else {
            $reason = new AppointmentService;
            $reason->company_id = $company_id;
            $reason->title = $input['appointment_reason'];
            $reason->save();
            $reason_id = $reason->id;
        }

        /* Save Contact */
        $conatct_exists = Contact::where(['email' => $input['email'], 'company_id' => $company_id])->where('mobile_number', "=", $phone_number)->count();

        if ($conatct_exists > 0) {
            $contactss = Contact::select('id')->where('email', "=", $input['email'])
                ->where('mobile_number', "=", $phone_number)
                ->where('company_id', "=", $company_id)
                ->first();
            $contact_id = $contactss->id;
        } else {
            $dob = '';
            if (isset($input['birth_date'])) {
                $dob = $input['birth_date'];
            }

            $contact = new Contact;
            $contact->company_id = $company_id;
            $contact->first_name = $input['first_name'];
            $contact->last_name = $input['last_name'];
            if (isset($input['gender'])) {
                $contact->gender = $input['gender'];
            }
            $contact->email = $input['email'];
            $user_tower_data = "";
            $user_tower_data = ContactHelper::getTowerDataFromEmail($input['email']);
            if ($user_tower_data) {
                $contact->additional_information = $user_tower_data;
            }
            $contact->mobile_number = $phone_number;
            if (isset($input['city'])) {
                $contact->city = $input['city'];
            }
            $contact->state = $input['state'];
            $contact->notes = $input['notes'];
            $contact->phone_country_code = $default_phone_country_code;
            $contact->birth_date = $dob;
            $contact->is_existing = $input['is_existing'];
            $contact->insurance_Id = $input['insurance_Id'];
            $contact->insurance_group = $input['insurance_group'];
            $contact->insurance_phone = $input['insurance_phone'];
            $contact->created_at = new dateTime();
            $contact->save();
            $contact_id = $contact->id;
        }

        $contact_type = 'new';
        if ($input['is_existing'] == 1) {
            $contact_type = 'existing';
        }

        /* Save Contacts */
        $appointment = new Appointment;
        $appointment->book_datetime = new dateTime();
        $appointment->company_id = $company_id;
        $appointment->start_datetime = $input['start_datetime'];
        $appointment->end_datetime = $input['end_datetime'];
        $appointment->provider_user_id = $input['provider_user_id'];
        $appointment->appointment_service_id = $reason_id;
        $appointment->contact_id = $contact_id;
        $appointment->scheduling_method = 'web';
        $appointment->contact_type = $contact_type;
        $appointment->created_at = new dateTime();
        $appointment->save();
        $appointment_id = $appointment->id;

        $provider_info = UserHelper::getUserDetails($input['provider_user_id']);
        $provider_name = $provider_info['name'];

        $companyInfo = Company::find($company_id)->toArray();
        $data['company_information'] = $companyInfo;
        $duration = date('Y-m-d H:i', strtotime($input['start_datetime'])) . '-' . date('H:i', strtotime($input['end_datetime']));

        $lead_info = LeadHelper::getLeadByContact($contact_id, $company_id);
        if ($lead_info === false) {
            $stage_id = LeadHelper::getStageBySlug($company_id, 'appointments');
            LeadHelper::add_lead($company_id, $contact_id, $stage_id, null, null, null, null, null, 1, null);
        } else {
            /* Update lead In case of lead stage null */
            if ($lead_info['stage_id'] == null) {
                $stage_id = LeadHelper::getStageBySlug($company_id, 'appointments');
                LeadHelper::update_lead($lead_info['id'], $company_id, $stage_id);
            }
        }

        /* *********************** Add Activity *********************** */

        ActivityHelper::createActivity($company_id, 'NEW_APPOINTMENT_WEB', 'appointment', $appointment_id, $contact_id, $input['provider_user_id'], null);

        /* *********************** / Add Activity *********************** */
        $app_from_email = app_from_email();

        $bcc_email = getenv('BCC_EMAIL');

        //Booking Notification to Admin
        $admin_email = $companyInfo['email'];
        $company_information = $companyInfo;

        $email_subject = 'New Appointment Booked';
        $app_from_email = app_from_email();
        $bcc_email = getenv('BCC_EMAIL');
        CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, compact('company_information', 'input', 'companyInfo', 'duration', 'data'), $email_subject, $bcc_email, 'emails.admin_booking_notification', $app_from_email);
        \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_subject, $email_subject, $appointment_id, 'appointment', 'NEW_APPOINTMENT_WEB');

        //Booking Notification to User

        $message = NotificationHelper::getNotificationMethod($company_id, 'general_settings', 'ADD_APPOINTMENT');
        $subject = NotificationHelper::getNotificationSubject($company_id, 'general_settings', 'ADD_APPOINTMENT');

        if ($message != false && $subject != false) {
            $message = nl2br($message);
            $message = str_replace('{$first_name}', $input['first_name'], $message);
            $message = str_replace('{$last_name}', $input['last_name'], $message);
            $message = str_replace('{$email}', $input['email'], $message);
            $message = str_replace('{$insurance_provider}', $input['insurance_provider'], $message);
            $message = str_replace('{$duration}', $duration, $message);
            $message = str_replace('{$notes}', $input['notes'], $message);
            $message = str_replace('{$company_name}', $data['company_information']['name'], $message);
            $message = str_replace('{$client_name}', $data['company_information']['name'], $message);
            $message = str_replace('{$company_address}', $data['company_information']['address'], $message);
            $message = str_replace('{$company_address}', $data['company_information']['address'], $message);

            $message = str_replace('{$provider}', $provider_name, $message);

            $message = str_replace('{$date}', date('F dS, Y', strtotime($input['start_datetime'])), $message);

            $message = str_replace('{$time}', date('h:i A', strtotime($input['start_datetime'])), $message);

            $message = str_replace('{$phone}', $input_phone_number, $message);

            $data['content_data'] = $message;

            $bcc_email = getenv('BCC_EMAIL');
            $template = 'emails.user_booking_notification';
            $email_content = compact('input', 'companyInfo', 'duration', 'data');
            \App\Classes\CompanySettingsHelper::sendClientEmailNotifcation($company_id, $input['email'], $email_content, $subject, $bcc_email, $template, $app_from_email, $email_type = null);
            \App\Classes\CompanyHelper::recordNotificationLog($appointment_id, 'appointment', 'ADD_APPOINTMENT', 'mail', $company_id, $input['email'], $message, $subject, $contact_id, $appointment_id);
        }

        return response()->success('success');
    }

    /* Delete Appointment */
    public function deleteAppointment($id)
    {
        $appointment = Appointment::find($id);
        $appointment->delete();
        return response()->success('success');
    }

    public function webAvailableSlots()
    {
        $user_id = Input::get('user_id');
        $date_from = Input::get('date_from');
        $date_to = Input::get('date_to');
        if (Input::get('user_id') && Input::get('date_from') && Input::get('date_to')) {
            $date_from = Input::get('date_from');
            $date_to = Input::get('date_to');
            $user_id = Input::get('user_id');
            $slots_available = $this->getAvailableSolts($date_from, $date_to, $user_id);
            if ($slots_available != false) {
                return response()->success(compact('slots_available'));
            }
            return response()->error('User calendar not found');
        }
        return response()->error('Fields not found');
    }

    public function getAvailableSolts($date_from, $date_to, $user_id)
    {
        $user_settings = UserSetting::where('user_id', $user_id)->select(['default_appointment_length', 'default_working_plan'])->first();
        if (count($user_settings) > 0) {
            $user_settings = $user_settings->toArray();
            $work_plan = $user_settings['default_working_plan'];
            $slot_length = $user_settings['default_appointment_length'];
            $appointments = $this->appointmentsInTime($date_from, $date_to, $user_id);
            $slots_available = $this->getFreeSlots($slot_length, $work_plan, $appointments, $date_from, $date_to);

            return $slots_available;
        } else {
            return false;
        }
    }

    public function getProviderSlots()
    {
        $user_id = Input::get('user_id');
        $date_from = Input::get('date_from');
        $date_to = Input::get('date_to');
        if (Input::get('user_id') && Input::get('date_from') && Input::get('date_to')) {
            $date_from = Input::get('date_from');
            $date_to = Input::get('date_to');
            $user_id = Input::get('user_id');
            $slots_available = $this->getAvailableSolts($date_from, $date_to, $user_id);
            if ($slots_available != false) {
                $slots_available = GroupingSlots($slots_available);
                return response()->success(compact('slots_available'));
            }
            return response()->error('User calendar not found');
        }
        return response()->error('Fields not found');
    }

    public function appointmentsInTime($time_start, $time_end, $user_id)
    {
        $app_start_time = date('Y-m-d 00:00:00', strtotime($time_start));
        $app_end_time = date('Y-m-d 23:59:59', strtotime($time_end));

        $appointentments = Appointment::where('provider_user_id', $user_id)
            ->whereBetween('start_datetime', [$app_start_time, $app_end_time])
            ->select(['start_datetime', 'end_datetime', 'available_status'])
            ->get()->toArray();
        return $appointentments;
    }

    public function getFreeSlots($app_length, $working_plan, $appointments, $date_from, $date_to)
    {
        /* Num Of Weeks Between Dates*/
        $dates_between = getDatesFromRange($date_from, $date_to, $format = 'Y-m-d');
        $all_slots = array();
        $slots_exclude_breaks = array();
        $work_plan = $working_plan;
        $free_slots = [];
        foreach ($dates_between as $date) {
            $week_num = date("W", strtotime($date));
            $year = date("Y", strtotime($date));
            $app_length = $app_length;
            $apps_day = array();
            $week_day = date('l', strtotime($date));
            $dayStartEndTime = getStartEndTime($work_plan, $week_day);

            if ($dayStartEndTime != false) {
                $start_time = $dayStartEndTime['start'];
                $end_time = $dayStartEndTime['end'];

                $slots = getTimeSlots($start_time, $end_time, $app_length, $date);
                $all_slots = array_merge($all_slots, $slots);
            }

            if ($dayStartEndTime == false) {
                $day_appointments = array();

                foreach ($appointments as $key => $app_os) {
                    if ($app_os['available_status'] == 2 && $date == date('Y-m-d', strtotime($app_os['start_datetime']))) {
                        $apps_day[] = $app_os;
                    }
                }

                foreach ($apps_day as $key => $app_d) {
                    $app_start_time = date('H:i', strtotime($app_d['start_datetime']));
                    $app_end_time = date('H:i', strtotime($app_d['end_datetime']));
                    $slots = getTimeSlots($app_start_time, $app_end_time, $app_length, $date);
                    $all_slots = array_merge($all_slots, $slots);
                }
            }
        }

        if (!empty($all_slots)) {
            foreach ($all_slots as $key => $slot) {
                $slot_start = $slot['start_time'];
                $slot_end = $slot['end_time'];
                $week_day_slot = date('l', strtotime($slot_start));
                $breaks = getBreaksDay($work_plan, $week_day_slot);
                $isBusy = isSlotUnavailableBreakSetSD($breaks, $appointments, $slot_start, $slot_end);
                if ($isBusy != true) {
                    $free_slots[] = $slot;
                }
            }
        }

        return $free_slots;
    }

    public function insuranceCompanies()
    {
        $InsuranceCompanieslist = array();
        $InsuranceCompanieslist = InsuranceCompany::select('id', 'insurance_company_name')->get()->toArray();
        return response()->success(compact('InsuranceCompanieslist'));
    }

    public function getCompanyProviders()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $company_providers = CompanyHelper::getAllDoctors($company_id);
        return response()->success(compact('company_providers'));
    }

    public function postAddModalAppointment()
    {
        $user = Auth::user();
        $company_id = $user->company_id;
        $input = Input::get();
        $rechedule = false;
        if (isset($input['provider_id']) && isset($input['start_time']) && isset($input['end_time']) && isset($input['contact_id']) && isset($input['scheduling_method']) && isset($input['appointment_reason'])) {
            if (isset($input['contact_type']) && $input['contact_type'] == true && isset($input['appointment_id'])) {
                $rechedule = true;
                $app_id = $input['appointment_id'];
                $appointment = Appointment::find($app_id);
                if (count($appointment) < 1 || (isset($appointment->company_id) && $appointment->company_id != $company_id)) {
                    return response()->error(['message' => 'please enter valid appointment id']);
                }
            }

            $reason_exists = AppointmentService::where('title', $input['appointment_reason'])->count();
            if ($reason_exists > 0) {
                $reason = AppointmentService::where('title', $input['appointment_reason'])->select('id')->first();
                $reason_id = $reason->id;
            } else {
                $reason = new AppointmentService;
                $reason->company_id = $company_id;
                $reason->title = $input['appointment_reason'];
                $reason->save();
                $reason_id = $reason->id;
            }

            $app_start_time = date('Y-m-d H:i:s', strtotime($input['start_time']));
            $app_end_time = date('Y-m-d H:i:s', strtotime($input['end_time']));

            /* if Appointment is rescheduled */
            if (!$rechedule) {
                $appointment = new Appointment;
            }
            $appointment->book_datetime = new dateTime();
            $appointment->company_id = $company_id;
            $appointment->start_datetime = $app_start_time;
            $appointment->end_datetime = $app_end_time;
            $appointment->provider_user_id = $input['provider_id'];
            $appointment->appointment_service_id = $reason_id;
            $appointment->contact_id = $input['contact_id'];
            $appointment->scheduling_method = $input['scheduling_method'];
            $appointment->contact_type = $input['contact_type'];
            $appointment->created_at = new dateTime();
            $appointment->save();
            $appointment_id = $appointment->id;
            if ($rechedule) {
                return $this->putUpdatestatus($app_id, 3);
            }


            $companyInfo = Company::find($company_id)->toArray();
            $data['company_information'] = $companyInfo;
            $duration = date('Y-m-d H:i', strtotime($input['start_time'])) . '-' . date('H:i', strtotime($input['end_time']));
            $lead_info = LeadHelper::getLeadByContact($input['contact_id'], $company_id);
            if ($lead_info === false) {
                $stage_id = LeadHelper::getStageBySlug($company_id, 'appointments');
                LeadHelper::add_lead($company_id, $input['contact_id'], $stage_id, null, null, null, null, null, null, null);
            } else {
                /* Update lead In case of lead stage Prospects */
                $prospects_stage_id = LeadHelper::getStageBySlug($company_id, 'prospects');
                if ($lead_info['stage_id'] == $prospects_stage_id) {
                    $stage_id = LeadHelper::getStageBySlug($company_id, 'appointments');
                    LeadHelper::update_lead($lead_info['id'], $company_id, $stage_id);
                }
            }

            /* *********************** Add Activity *********************** */

            ActivityHelper::createActivity($company_id, 'NEW_APPOINTMENT_EG', 'appointment', $appointment_id, $input['contact_id'], $input['provider_id'], null);

            /* *********************** / Add Activity *********************** */

            $app_from_email = app_from_email();

            $provider_info = UserHelper::getUserDetails($input['provider_id']);
            $provider_name = $provider_info['name'];

            $bcc_email = getenv('BCC_EMAIL');
            $contactInfo = ContactHelper::getContactInfo($input['contact_id']);
            $input = $contactInfo;
            //Booking Notification to Admin
            $admin_email = $companyInfo['email'];
            $company_information = $companyInfo;
            $email_subject = 'New Appointment Booked';
            CompanySettingsHelper::sendCompanyEmailNotifcation($company_id, compact('company_information', 'input', 'companyInfo', 'duration', 'data'), $email_subject, $bcc_email, 'emails.admin_booking_notification', $app_from_email);
            \App\Classes\CompanySettingsHelper::sendCompanyEmailNotifcationLogs($company_id, $email_subject, $email_subject, $appointment_id, 'appointment', 'NEW_APPOINTMENT_EG');
            $app_from_email = app_from_email();
            //Booking Notification to User

            $message = NotificationHelper::getNotificationMethod($company_id, 'general_settings', 'ADD_APPOINTMENT');
            $subject = NotificationHelper::getNotificationSubject($company_id, 'general_settings', 'ADD_APPOINTMENT');

            if ($message != false && $subject != false) {
                $contactInfo = $contactInfo->toArray();
                $message = nl2br($message);
                $message = str_replace('{$first_name}', $contactInfo['first_name'], $message);
                $message = str_replace('{$last_name}', $contactInfo['last_name'], $message);
                $message = str_replace('{$email}', $contactInfo['email'], $message);
                $message = str_replace('{$phone}', $contactInfo['mobile_number'], $message);
                $message = str_replace('{$insurance_provider}', $contactInfo['insurance_provider'], $message);
                $message = str_replace('{$duration}', $duration, $message);
                $message = str_replace('{$notes}', $input['notes'], $message);
                $message = str_replace('{$company_name}', $data['company_information']['name'], $message);
                $message = str_replace('{$client_name}', $data['company_information']['name'], $message);
                $message = str_replace('{$company_address}', $data['company_information']['address'], $message);
                $message = str_replace('{$company_address}', $data['company_information']['address'], $message);
                $message = str_replace('{$localtion}', $data['company_information']['address'], $message);
                $message = str_replace('{$office_phone}', $data['company_information']['phone'], $message);
                $message = str_replace('{$website_link}', $data['company_information']['site_url'], $message);
                $message = str_replace('{$provider}', $provider_name, $message);
                $message = str_replace('{$date}', date('F dS, Y', strtotime($app_start_time)), $message);
                $message = str_replace('{$time}', date('h:i A', strtotime($app_start_time)), $message);
                $message = str_replace('{$phone}', $contactInfo['mobile_number'], $message);
                $data['content_data'] = $message;
                $input['email'] = $contactInfo['email'];
                $bcc_email = getenv('BCC_EMAIL');
                if ($contactInfo['dnd'] != '1') {
                    $template = 'emails.user_booking_notification';
                    $email_content = compact('input', 'companyInfo', 'duration', 'data');
                    \App\Classes\CompanySettingsHelper::sendClientEmailNotifcation($company_id, $input['email'], $email_content, $subject, $bcc_email, $template, $app_from_email, $email_type = null);
                    /* Add email Log*/
                    \App\Classes\CompanyHelper::recordNotificationLog($appointment_id, 'appointment', 'ADD_APPOINTMENT', 'mail', $company_id, $input['email'], $message, $subject, $contactInfo['id'], $appointment_id);
                }
            }

            /*Appointment status Change to Approve*/
            if (!$rechedule) {
                return $this->putUpdatestatus($appointment_id, 1);
            }
            return response()->success('Appointment Booked Successfully');
        }
    }
}
