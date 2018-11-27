<?php
namespace App\Classes;

use App\Appointment;
use App\Classes\CompanySettingsHelper;
use DB;

class AppointmentsHelper
{

    public static function getCountAppointmentBytime($start_date, $end_date, $company_id)
    {

        $company_time_zone = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($company_time_zone == false) {
            $company_time_zone = 'UTC';
        }

        $start_date = \App\Classes\LeadHelper::convertToUtc($start_date . ' 00:00:00', $company_time_zone, 'Y-m-d H:i:s');
        $end_date = \App\Classes\LeadHelper::convertToUtc($end_date . ' 23:59:59', $company_time_zone, 'Y-m-d H:i:s');
        $out = array();
        $count = Appointment::whereBetween('created_at', array($start_date, $end_date))
            ->where('company_id', $company_id)
            ->where('available_status', '=', 0)
            ->count();

        return $count;
    }

    public static function getCountAptmntBySoucetime($start_date, $end_date, $company_id)
    {

        $company_time_zone = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($company_time_zone == false) {
            $company_time_zone = 'UTC';
        }
        $start_date = \App\Classes\LeadHelper::convertToUtc($start_date . ' 00:00:00', $company_time_zone, 'Y-m-d H:i:s');
        $end_date = \App\Classes\LeadHelper::convertToUtc($end_date . ' 23:59:59', $company_time_zone, 'Y-m-d H:i:s');

        $out = array();
        $appoinmets_source = Appointment::groupBy('scheduling_method')
            ->select(DB::raw('count(id) as total'), 'scheduling_method')
            ->whereBetween('book_datetime', array($start_date, $end_date))
            ->where('company_id', $company_id)
            ->where('available_status', '=', 0)
            ->get()
            ->toArray();
        return $appoinmets_source;
    }

    public static function getNewAndReturningUsers($start_date, $end_date, $company_id, $type)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();
        $usercount = 0;
        if ($type == "new") {
            $usercount = Appointment::where('company_id', $company_id)
                ->whereBetween('book_datetime', array($start_time, $end_time))
                ->whereIn(
                    'contact_id',
                    function ($query) {
                        $query->select('contact_id')->from('appointments')->join('contacts', 'appointments.contact_id', '=', 'contacts.id')->groupBy('contacts.email')->havingRaw('count(contacts.id) = 1');
                    }
                )->count();
        } else {
            $usercount = Appointment::where('company_id', $company_id)
                ->whereBetween('book_datetime', array($start_time, $end_time))
                ->whereIn(
                    'contact_id',
                    function ($query) {
                        $query->select('contact_id')->from('appointments')->join('contacts', 'appointments.contact_id', '=', 'contacts.id')->groupBy('contacts.email')->havingRaw('count(contacts.id) > 1');
                    }
                )->count();
        }
        return $usercount;
    }
    public static function getWebsiteVsWebProfile($start_date, $end_date, $company_id, $type)
    {

        $usercount = 0;
        if ($type == 'website') {
            $usercount = Appointment::where('scheduling_method', 'web')->where('company_id', $company_id)->count();
        } else {
            $usercount = Appointment::where('scheduling_method', 'phone')->where('company_id', $company_id)->count();
        }
        return $usercount;
    }
}
