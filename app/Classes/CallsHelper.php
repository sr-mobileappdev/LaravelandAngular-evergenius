<?php
namespace App\Classes;

use App\CallRecord;
use App\CallRecordNote;
use App\Classes\ActivityHelper;
use App\Classes\CompanySettingsHelper;
use App\Contact;
use Carbon\Carbon;
use DB;
use Twilio\Rest\Client;

class CallsHelper
{
    public static function SaveAllCallsRecords($compnies, $starttimeAfter = false)
    {
        foreach ($compnies as $company) {
            $company_id = $company['id'];
            // Get Twilio infomation
            $twilio_enable = CompanySettingsHelper::getSetting($company_id, 'twilio_enable');
            $twilio_sid = CompanySettingsHelper::getSetting($company_id, 'twilio_sid');
            $twilio_auth_id = CompanySettingsHelper::getSetting($company_id, 'twilio_auth_id');
            $twilio_number = CompanySettingsHelper::getSetting($company_id, 'twilio_number');

            // Is twilio enable and all information is provided
            if ($twilio_enable == 1 && !empty($twilio_sid) && !empty($twilio_auth_id) && !empty($twilio_number)) {
                try {
                    $accountId = $twilio_sid;
                    $token = $twilio_auth_id;
                    $twilio = new Client($accountId, $token);

                    if ($starttimeAfter != false) {
                        $starttimeAfter = date('Y-m-d', strtotime($starttimeAfter));
                        $calls = $twilio->calls->read(
                            array(
                                "starttimeAfter" => $starttimeAfter,
                                "direction" => "inbound",
                            )
                        );
                    } else {
                        $calls = $twilio->calls->read();
                    }
                    CallsHelper::StoreTwilioCalls($calls, $company_id);
                } catch (\Exception $e) {
                    $logFile = 'twilio.log';
                    \Log::useDailyFiles(storage_path() . '/logs/' . $logFile);
                    \Log::emergency("Company ID:" . $company_id . "-" . $e->getMessage());
                }
            }
        }
        return true;
    }

    public static function StoreTwilioCalls($call_records, $company_id)
    {
        $ins_data = array();
        if (count($call_records) > 0 && !empty($call_records)) {
            foreach ($call_records as $key => $record) {
                if ($record->direction == 'inbound') {
                    $contact_id = null;
                    $contact_info = Contact::select('id')
                        ->where('mobile_number', $record->from)
                        ->orWhere('mobile_number', $record->from)
                        ->first();

                    if (count($contact_info) > 0) {
                        $contact_id = $contact_info->id;
                    }

                    $ins_data = array(
                        'company_id' => $company_id,
                        'contact_id' => $contact_id,
                        'call_from' => $record->from,
                        'call_to' => $record->to,
                        'call_start_at' => $record->dateCreated->format('Y-m-d H:i:s'),
                        'call_end_at' => $record->endTime->format('Y-m-d H:i:s'),
                        'call_duration' => $record->duration,
                        'call_status' => $record->status,
                        'call_sid' => $record->sid,
                        'call_uri' => $record->uri,
                        'call_direction' => $record->direction,
                        'account_sid' => $record->accountSid,
                    );
                    // If Record Not Exists Then Add New Record

                    if (!CallsHelper::isCallExists($record->sid)) {
                        $id = DB::table('call_records')->insertGetId($ins_data);

                        /* *********************** Add Activity *********************** */
                        ActivityHelper::createActivity($company_id, 'NEW_CALL', 'call_records', $id, $contact_id, null, null);

                    /* *********************** / Add Activity *********************** */
                    } // Update Existing Record
                    else {
                        CallRecord::where('call_sid', $record->sid)->update($ins_data);
                        /* *********************** Add Activity *********************** */
                        ActivityHelper::createActivity($company_id, 'NEW_CALL', 'call_records', $id, $contact_id, null, null);

                        /* *********************** / Add Activity *********************** */
                    }
                } else {
                    if ($record->direction == 'outbound-dial') {
                        $update_data = [
                            'call_duration' => $record->duration,
                            'call_status' => $record->status,
                        ];
                        CallRecord::where('call_sid', $record->parentCallSid)->update($update_data);
                    }
                }
            }
            return true;
        }
    }

    public static function StoreSingleTwilioCall($ins_data)
    {
        $id = DB::table('call_records')->insertGetId($ins_data);
        return $id;
    }

    public static function isCallExists($call_sid)
    {
        $call_data = CallRecord::select('id')
            ->where('call_sid', $call_sid)
            ->first();
        if (count($call_data) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function getCallsStaticByDate($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();

        /* *************  Get Data From Database ************* */
        $calls = CallRecord::groupBy(DB::raw('date(call_start_at)'))
            ->select(DB::raw('count(id) as total'), DB::raw('date(call_start_at) as date'))
            ->whereBetween('call_start_at', array($start_time, $end_time))
            ->where('company_id', $company_id)
            ->get();

        $dates = getDatesFromRange($start_date, $end_date);
        foreach ($dates as $key => $date) {
            $total_calls = 0;
            foreach ($calls as $key => $call) {
                if ($call['date'] == $date) {
                    $total_calls = $call['total'];
                    break;
                }
            }
            $out[] = array('date' => date('M d', strtotime($date)), 'calls' => $total_calls);
        }
        return $out;
    }

    public static function getCallsStaticByWeek($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();

        /* *************  Get Data From Database ************* */
        $calls = CallRecord::groupBy(DB::raw('Week(call_start_at,3)'))
            ->select(DB::raw('count(id) as total'), DB::raw('Week(call_start_at,3) as week'))
            ->whereBetween('call_start_at', array($start_time, $end_time))
            ->where('company_id', $company_id)
            ->get();
        
        $dates = getWeeksDaysBetweenDays($start_date, $end_date, "Y-m-d");
        foreach ($dates as $key => $date) {
            $week_n = date("W", strtotime($date));
            $date_full = date("d M", strtotime($date));
            $total_calls = 0;
            foreach ($calls as $key => $call) {
                if ($call['week'] == $week_n) {
                    $total_calls = $call['total'];
                    break;
                }
            }
            $out[] = array('date' => $date_full, 'calls' => $total_calls);
        }
        return $out;
    }

    public static function getCallsStaticByMonth($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();

        /* *************  Get Data From Database ************* */
        $calls = CallRecord::groupBy(DB::raw('date(call_start_at)'))
            ->select(DB::raw('count(id) as total'), DB::raw('MONTH(call_start_at) as month'))
            ->whereBetween('call_start_at', array($start_time, $end_time))
            ->where('company_id', $company_id)
            ->get();

        $dates = getMonthsDaysBetweenDays($start_date, $end_date);

        foreach ($dates as $key => $date) {
            $mnth = date("m", strtotime("01-" . $date));
            $mnth_full = date("M", strtotime("01-" . $date));
            $total_calls = 0;
            foreach ($calls as $key => $call) {
                if ($call['month'] == $mnth) {
                    $total_calls = $call['total'];
                    break;
                }
            }
            $out[] = array('date' => $mnth_full, 'calls' => $total_calls);
        }
        return $out;
    }

    public static function getCallsSummaryByDate($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $calls = CallRecord::groupBy('call_status')
            ->select(DB::raw('count(*) as total'), 'call_status')
            ->whereBetween('call_start_at', array($start_time, $end_time))
            ->where('company_id', $company_id)
            ->get()
            ->toArray();

        return $calls;
    }

    public static function getMobileReciveNumber($id)
    {
        $record = CallRecord::select('call_from')->where('id', $id)->first();
        if ($record) {
            $record = $record->toArray();
            if (count($record > 0)) {
                return $record['call_from'];
            } else {
                return false;
            }
        }
    }

    public static function get_contact_calls($contact_id)
    {
        $calls = CallRecord::where('contact_id', $contact_id)
            ->orderBy('id', 'desc')
            ->get();
        return $calls;
    }

    public static function isCallerContactExist($number)
    {
        $contact_info = Contact::where('mobile_number', 'like', '%' . trim($number) . '%')
            ->first();
        if (count($contact_info) > 0) {
            return $contact_info->toArray();
        }
        return false;
    }

    public static function getCallsTotalByDate($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();
        $calls_count = CallRecord::whereBetween('call_start_at', array($start_time, $end_time))->count();
        return $calls_count;
    }

    public static function getCallTime($company_id)
    {
        $tz = CompanySettingsHelper::getSetting($company_id, 'timezone');
        if ($tz != '' && $tz != false) {
            $st = Carbon::createFromTimestamp(time())
                ->timezone($tz)
                ->toDateTimeString();
        } else {
            return date('M d h:i A', time());
        }
        $d = strtotime($st);

        return date('M d h:i A', $d);
    }
    public static function getCallsLeadsByDate($company_id, $start_date, $end_date)
    {
        $start_time = date('Y-m-d 00:00:00', strtotime($start_date));
        $end_time = date('Y-m-d 23:59:59', strtotime($end_date));
        $out = array();
        $calls_count = CallRecord::select(DB::raw('distinct(call_from)'))->whereBetween('call_start_at', array($start_time, $end_time))
            ->where('lead_status', 1)
            ->where('company_id', $company_id)
            ->get();
        return count($calls_count);
    }

    public static function updateLeadStatus($call_id, $status, $company_id)
    {
        CallRecord::where('id', $call_id)
            ->update(['lead_status' => $status]);
        return true;
    }

    public static function updateCallNote($call_id, $note, $update_by = null)
    {
        $calls_count = CallRecordNote::where('call_id', $call_id)->count();
        if ($calls_count > 0) {
            // Update data
            CallRecordNote::where('call_id', $call_id)->update(['note' => $note]);
        }
        // Insert data
        $new_note = new CallRecordNote;
        $new_note->call_id = $call_id;
        $new_note->note = $note;
        $new_note->update_by = $update_by;
        $new_note->save();
        return true;
    }
}
