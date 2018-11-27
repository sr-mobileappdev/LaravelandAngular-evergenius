<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\User;
use App\UserSetting;
use Auth;
use DateTime;
use Input;

class CelendarController extends Controller
{
    public $aray_validate = array('first_name', 'last_name', 'gender', 'email', 'birth_date', 'mobile_number', 'city');
    public $outut_data;

    public function getDefaultCelendar($id)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }
        $user_info = User::find($id);
        if ($user_info->company_id != $company_id) {
            return response()->error('Wrong user Id');
        }
        $user_id = $id;
        $user_settings = UserSetting::where('user_id', $id)->first();
        if (count($user_settings) > 0) {
            $default_appointment_length = $user_settings->default_appointment_length;
            $default_working_plan = $user_settings->default_working_plan;
            if ($default_working_plan != '') {
                $working_plan = json_decode($default_working_plan);
            } else {
                $working_plan = '';
            }
        } else {
            $default_appointment_length = '';
            $default_working_plan = $this->blankCalender();
        }

        return response()->success(compact('default_appointment_length', 'working_plan', 'user_id', 'user_info'));
    }

    public function blankCalender()
    {
        $cale = array(
            'monday_open' => false,
            'tuesday_open' => false,
            'wednesday_open' => false,
            'thursday_open' => false,
            'friday_open' => false,
            'saturday_open' => false,
            'sunday_open' => false,
            'monday' => array('breaks' => array()),
            'tuesday' => array('breaks' => array()),
            'wednesday' => array('breaks' => array()),
            'thursday' => array('breaks' => array()),
            'friday' => array('breaks' => array()),
            'saturday' => array('breaks' => array()),
            'sunday' => array('breaks' => array()),
        );
        return $cale;
    }

    public function putDefaultCelendar()
    {
        $input_data = input::all();
        $id = $input_data['data']['user_id'];
        $user_settings = UserSetting::where('user_id', $id)->first();
        if (count($user_settings) > 0) {
            $id_user_settings = $user_settings->id;
            $update_data = array(
                'default_working_plan' => json_encode($input_data['data']['working_plan']),
                'default_appointment_length' => $input_data['data']['default_appointment_length'],
            );
            $affectedRows = UserSetting::where('id', '=', intval($id_user_settings))->update($update_data);
            return response()->success($input_data);
        } else {
            $default_working_plan = $this->blankCalender();
            $user_setting = new UserSetting;
            $user_setting->user_id = $id;
            $user_setting->default_working_plan = json_encode($default_working_plan);
            $user_setting->default_appointment_length = $input_data['data']['default_appointment_length'];
            $user_setting->save();
            $id_user_settings = $user_setting->id;
            $update_data = array(
                'default_working_plan' => json_encode($input_data['data']['working_plan']),
                'default_appointment_length' => $input_data['data']['default_appointment_length'],
            );

            $affectedRows = UserSetting::where('id', '=', intval($id_user_settings))->update($update_data);
            return response()->success($input_data);
        }
        return response()->failed('Somthing Went Wrong');
    }

    public function getUserCelendar($id)
    {
        $company_id = 0;
        if (Auth::user()) {
            $user = Auth::user();
            $company_id = $user->company_id;
        }

        $user_info = User::find($id);
        if ($user_info->company_id != $company_id) {
            return response()->error('Wrong user Id');
        }

        $user_id = $id;
        $date_start = Input::get('start_time');
        $date_end = Input::get('end_time');
        $user_settings = UserSetting::where('user_id', $id)->first();

        if (count($user_settings) > 0) {
            $default_appointment_length = $user_settings->default_appointment_length;
            $default_working_plan = $user_settings->default_working_plan;
            if ($default_working_plan != '') {
                $working_plan = json_decode($default_working_plan);
            } else {
                $working_plan = '';
            }
        } else {
            $default_appointment_length = '';
            $default_working_plan = $this->blankCalender();
        }

        $appointments_booked = $this->getAppointmentBooked($user_id, $date_start, $date_end);

        return response()->success(
            compact(
            'default_appointment_length',
            'working_plan',
            'user_id',
            'appointments_booked',
            'user_info'
            )
        );
    }

    public function getAppointmentBooked($user_id, $date_start, $date_end)
    {
        $searchtimeStart = date('Y-m-d 00:00:00', $date_start);
        $searchtimeEnd = date('Y-m-d 23:59:59', $date_end);
        $appointments_booked = Appointment::with(
            'contacts',
            'appointment_reason',
            'appointment_status'
        )->whereBetween('start_datetime', [$searchtimeStart, $searchtimeEnd])
            ->where('provider_user_id', $user_id)
            ->get()->toArray();
        return $appointments_booked;
    }

    public function postSetUnvailable()
    {
        $time_from = Input::get('time_from');
        $time_to = Input::get('time_to');
        $timestamp = Input::get('timestamp');
        $user_id = Input::get('user_id');
        $status = Input::get('status');
        if (Input::get('days')) {
            $days = Input::get('days');
            foreach ($days as $key => $day) {
                if ($day) {
                    $days_num = array(
                        'Sunday' => 0,
                        'Monday' => 1,
                        'Tuesday' => 2,
                        'Wednesday' => 3,
                        'Thursday' => 4,
                        'Friday' => 5,
                        'Saturday' => 6
                    );
                    $day_unavail = $days_num[ucfirst($key)];
                    $date_unavail = $this->week_from_sunday($timestamp, $day_unavail);
                    $app_start = $date_unavail . " " . $time_from . ":00";
                    $app_end = $date_unavail . " " . $time_to . ":00";

                    if ($status == 'unavailabe') {
                        $this->setStatusUnAvailable($app_start, $app_end, $user_id);
                    } elseif ($status == 'available') {
                        $this->setStatusAvailable($app_start, $app_end, $user_id);
                    }
                }
            }
            return response()->success('success');
        } else {
            return response()->error('Days Not Found');
        }
    }

    public function week_from_sunday($timestamp, $day_num)
    {
        $date = date('d-m-Y', $timestamp);
        // Assuming $date is in format DD-MM-YYYY
        list($day, $month, $year) = explode("-", $date);
        // Get the weekday of the given date
        $wkday = date('l', mktime('0', '0', '0', $month, $day, $year));
        switch ($wkday) {
            case 'Sunday':
                $numDaysToMon = 0;
                break;
            case 'Monday':
                $numDaysToMon = 1;
                break;
            case 'Tuesday':
                $numDaysToMon = 2;
                break;
            case 'Wednesday':
                $numDaysToMon = 3;
                break;
            case 'Thursday':
                $numDaysToMon = 4;
                break;
            case 'Friday':
                $numDaysToMon = 5;
                break;
            case 'Saturday':
                $numDaysToMon = 6;
                break;
        }
        // Timestamp of the monday for that week
        $monday = mktime('0', '0', '0', $month, $day - $numDaysToMon, $year);
        $seconds_in_a_day = 86400;
        // Get date for 7 days from Monday (inclusive)
        for ($i = 0; $i < 7; $i++) {
            $dates[$i] = date('Y-m-d', $monday + ($seconds_in_a_day * $i));
        }
        return $dates[$day_num];
    }

    public function setStatusUnAvailable($unavail_start, $unavail_end, $user_id)
    {
        $week_schedule = UserSetting::where('user_id', $user_id)->first()->toArray();
        $app = Appointment::with('week_scheduler')
            ->where('provider_user_id', $user_id)
            ->where('available_status', '=', 2)
            ->where(
                function ($query) use ($unavail_start, $unavail_end) {
                    $query->where(
                        function ($q) use ($unavail_start, $unavail_end) {
                            $q->where('start_datetime', '>=', $unavail_start)
                                ->where('end_datetime', '<=', $unavail_end)
                                ->where('end_datetime', '>', $unavail_start);
                        }
                    )
                        ->orWhere(
                            function ($qu) use ($unavail_start, $unavail_end) {
                                $qu->where('start_datetime', '>=', $unavail_start)
                                    ->where('end_datetime', '<=', $unavail_end);
                            }
                        )
                        ->orWhere(
                            function ($que) use ($unavail_start, $unavail_end) {
                                $que->where('start_datetime', '<=', $unavail_start)
                                    ->where('end_datetime', '<=', $unavail_end)
                                    ->where('end_datetime', '>', $unavail_start);
                            }
                        )
                        ->orWhere(
                            function ($quer) use ($unavail_start, $unavail_end) {
                                $quer->where('start_datetime', '<=', $unavail_start)
                                    ->where('end_datetime', '>=', $unavail_end);
                            }
                        )
                        ->orWhere(
                            function ($quer) use ($unavail_start, $unavail_end) {
                                $quer->where('start_datetime', '<=', $unavail_end)
                                    ->where('end_datetime', '>=', $unavail_end)
                                    ->where('start_datetime', '>=', $unavail_start);
                            }
                        );
                }
            )
            ->get()->toArray();

        $date = date('Y-m-d', strtotime($unavail_start));
        $star_slot = date('H:i', strtotime($unavail_start));
        $end_slot = date('H:i', strtotime($unavail_end));

        $slots = getTimeSlots($star_slot, $end_slot, $week_schedule['default_appointment_length'], $date);
        $ins_array = array();
        $ids_to_delete = array();
        if (count($app) > 0) {
            foreach ($app as $key => $availites) {
                $ins_array = array();
                $ids_to_delete[] = $availites['id'];
                $un_date = date('Y-m-d', strtotime($availites['start_datetime']));
                $un_star_slot = date('H:i', strtotime($availites['start_datetime']));
                $un_end_slot = date('H:i', strtotime($availites['end_datetime']));

                if ($end_slot > $un_end_slot) {
                    $un_end_slot = $end_slot;
                }

                if ($star_slot <= $un_star_slot) {
                    $un_star_slot = $star_slot;
                }

                $un_slots = getTimeSlots(
                    $un_star_slot,
                    $un_end_slot,
                    $week_schedule['default_appointment_length'],
                    $un_date
                );
                foreach ($un_slots as $key => $un_slot) {
                    if (!(($un_slot['start_time'] <= $unavail_start && $un_slot['end_time'] > $unavail_start) || ($un_slot['end_time'] <= $unavail_end && $un_slot['end_time'] > $unavail_start) || ($unavail_start < $un_slot['start_time'] && $unavail_end > $un_slot['start_time'] && $unavail_end < $un_slot['end_time']))) {
                        $ins_array[] = array(
                            'start_datetime' => $un_slot['start_time'],
                            'end_datetime' => $un_slot['end_time'],
                            'available_status' => 2,
                            'company_id' => $availites['company_id'],
                            'provider_user_id' => $user_id,
                            'book_datetime' => new dateTime(),
                        );
                    } else {
                        $user = Auth::user();
                        $company_id = $user->company_id;
                        $ins_array[] = array(
                            'start_datetime' => $un_slot['start_time'],
                            'end_datetime' => $un_slot['end_time'],
                            'available_status' => 1,
                            'company_id' => $company_id,
                            'provider_user_id' => $user_id,
                            'book_datetime' => new dateTime(),
                        );
                    }
                }
            }
        } else {
            $user = Auth::user();
            $company_id = $user->company_id;
            $ins_array[] = array(
                'start_datetime' => $unavail_start,
                'end_datetime' => $unavail_end,
                'available_status' => 1,
                'company_id' => $company_id,
                'provider_user_id' => $user_id,
                'book_datetime' => new dateTime(),
            );
        }
        Appointment::whereIn('id', $ids_to_delete)->delete();
        Appointment::insert($ins_array);
    }

    /**** Function for set avalailable */
    public function setStatusAvailable($avail_start, $avail_end, $user_id)
    {
        $week_schedule = UserSetting::where('user_id', $user_id)->first()->toArray();
        $app = Appointment::with('week_scheduler')
            ->where('available_status', '=', 1)
            ->where('provider_user_id', $user_id)
            ->where(
                function ($query) use ($avail_start, $avail_end) {
                    $query->where(
                        function ($q) use ($avail_start, $avail_end) {
                            $q->where('start_datetime', '>=', $avail_start)
                                ->where('end_datetime', '<=', $avail_end)
                                ->where('end_datetime', '>', $avail_start);
                        }
                    )
                        ->orWhere(
                            function ($qu) use ($avail_start, $avail_end) {
                                $qu->where('start_datetime', '>=', $avail_start)
                                    ->where('end_datetime', '<=', $avail_end);
                            }
                        )
                        ->orWhere(
                            function ($que) use ($avail_start, $avail_end) {
                                $que->where('start_datetime', '<=', $avail_start)
                                    ->where('end_datetime', '<=', $avail_end)
                                    ->where('end_datetime', '>', $avail_start);
                            }
                        )
                        ->orWhere(
                            function ($quer) use ($avail_start, $avail_end) {
                                $quer->where('start_datetime', '<=', $avail_start)
                                    ->where('end_datetime', '>=', $avail_end);
                            }
                        )
                        ->orWhere(
                            function ($quer) use ($avail_start, $avail_end) {
                                $quer->where('start_datetime', '<=', $avail_end)
                                    ->where('end_datetime', '>=', $avail_end)
                                    ->where('start_datetime', '>=', $avail_start);
                            }
                        );
                }
            )
            ->get()->toArray();

        $star_slot = date('H:i', strtotime($avail_start));
        $end_slot = date('H:i', strtotime($avail_end));
        $ins_array = array();
        $ids_to_delete = array();
        if (count($app) > 0) {
            foreach ($app as $key => $unavailites) {
                $ins_array = array();

                $ids_to_delete[] = $unavailites['id'];
                $un_date = date('Y-m-d', strtotime($unavailites['start_datetime']));
                $un_star_slot = date('H:i', strtotime($unavailites['start_datetime']));
                $un_end_slot = date('H:i', strtotime($unavailites['end_datetime']));

                if ($end_slot > $un_end_slot) {
                    $un_end_slot = $end_slot;
                }

                if ($star_slot < $un_star_slot) {
                    $un_star_slot = $star_slot;
                }

                $un_slots = getTimeSlots(
                    $un_star_slot,
                    $un_end_slot,
                    $week_schedule['default_appointment_length'],
                    $un_date
                );

                foreach ($un_slots as $key => $un_slot) {
                    if (!(($un_slot['start_time'] <= $avail_start && $un_slot['end_time'] > $avail_start) || ($un_slot['end_time'] <= $avail_end && $un_slot['end_time'] > $avail_start) || ($avail_start < $un_slot['start_time'] && $avail_end > $un_slot['start_time'] && $avail_end < $un_slot['end_time']))) {
                        $ins_array[] = array(
                            'start_datetime' => $un_slot['start_time'],
                            'end_datetime' => $un_slot['end_time'],
                            'available_status' => 1,
                            'company_id' => $unavailites['company_id'],
                            'provider_user_id' => $user_id,
                            'book_datetime' => new dateTime(),
                        );
                    } else {
                        $user = Auth::user();
                        $company_id = $user->company_id;
                        $ins_array[] = array(
                            'start_datetime' => $un_slot['start_time'],
                            'end_datetime' => $un_slot['end_time'],
                            'available_status' => 2,
                            'company_id' => $company_id,
                            'provider_user_id' => $user_id,
                            'book_datetime' => new dateTime(),
                        );
                    }
                }
            }
        } else {
            $user = Auth::user();
            $company_id = $user->company_id;
            $ins_array[] = array(
                'start_datetime' => $avail_start,
                'end_datetime' => $avail_end,
                'available_status' => 2,
                'company_id' => $company_id,
                'provider_user_id' => $user_id,
                'book_datetime' => new dateTime(),
            );
        }

        Appointment::whereIn('id', $ids_to_delete)->delete();
        Appointment::insert($ins_array);
    }

    public function postUnsetUnvailable()
    {
        $start_timestamp = Input::get('start_timestamp');
        $end_timestamp = Input::get('end_timestamp');
        $user_id = Input::get('user_id');
        $start_app = date('Y-m-d 00:00:00', $start_timestamp);
        $end_app = date('Y-m-d 23:59:59', $end_timestamp);
        Appointment::where('provider_user_id', $user_id)
            ->where('start_datetime', '>=', $start_app)
            ->where('end_datetime', '<=', $end_app)
            ->whereIn('available_status', [1, 2])
            ->delete();
        return response()->success('deleted');
    }

    public function postSetCurrentDefault()
    {
        $start_timestamp = Input::get('start_timestamp');
        $end_timestamp = Input::get('end_timestamp');
        $user_id = Input::get('user_id');
        $startWeekDay = date('Y-m-d 00:00:00', $start_timestamp);
        $endWeekDay = date('Y-m-d 23:59:59', $end_timestamp);
        $UnavailbilitiesWeek = Appointment::select(array('start_datetime', 'end_datetime'))
            ->where('start_datetime', '>=', $startWeekDay)
            ->where('end_datetime', '<=', $endWeekDay)
            ->where('provider_user_id', '=', $user_id)
            ->where('available_status', '=', 1)
            ->get()
            ->toArray();
        if (count($UnavailbilitiesWeek) > 0) {
        }
        $this->AvailableSlots($user_id, $endWeekDay, $startWeekDay);
        return response()->success('Current week Schedule set as default');
    }

    public function AvailableSlots($user_id, $date_to, $date_from)
    {
        $user_settings = UserSetting::where('user_id', $user_id)->select([
            'default_appointment_length',
            'default_working_plan'
        ])->first()->toArray();

        if (count($user_settings) > 0) {
            $work_plan = $user_settings['default_working_plan'];
            $slot_length = $user_settings['default_appointment_length'];
            $appointments = $this->appointmentsInTime($date_from, $date_to, $user_id);
            $slots_available = $this->getFreeSlots(
                $slot_length,
                $work_plan,
                $appointments,
                $date_from,
                $date_to,
                $user_id
            );
        } else {
            return false;
        }
    }

    public function appointmentsInTime($time_start, $time_end, $user_id)
    {
        $app_start_time = date('Y-m-d 00:00:00', strtotime($time_start));
        $app_end_time = date('Y-m-d 23:59:59', strtotime($time_end));
        $appointentments = Appointment::where('provider_user_id', $user_id)
            ->where('available_status', 0)
            ->whereBetween('start_datetime', [$app_start_time, $app_end_time])
            ->select(['start_datetime', 'end_datetime', 'available_status'])
            ->get()->toArray();
        return $appointentments;
    }

    public function getFreeSlots($app_length, $working_plan, $appointments, $date_from, $date_to, $user_id)
    {
        /* Num Of Weeks Between Dates*/
        $D_start = date('Y-m-d', strtotime($date_from));
        $D_end = date('Y-m-d', strtotime($date_to));
        $dates_between = getDatesFromRange($D_start, $D_end,  'Y-m-d');
        $all_slots = array();
        $work_plan = $working_plan;
        $availabilites = $this->AvailableInTime($date_from, $date_to, $user_id);
        $dayStartEndTime = getWeekStartEndTime($work_plan, $availabilites);
        $unavailabilites = $this->UnAvailableInTime($date_from, $date_to, $user_id);
        $Schedule_week_day = array();
        $avail_slots_week = array();
        $breaks_slots_week = array();
        $working_plan_array = array();
        $last_break_end = '';
        // Days Loop Start
        foreach ($dates_between as $date) {
            $app_length = $app_length;
            $week_day = date('l', strtotime($date));
            $day_avail_slots = array();
            if ($dayStartEndTime != false) {
                $start_time = $dayStartEndTime['start'];
                $end_time = $dayStartEndTime['end'];
                $slots = getTimeSlots($start_time, $end_time, $app_length, $date);
            }
            //if Day Open
            $day_timeings = getStartEndTime($work_plan, $week_day);
            $day_breaks = array();
            /*Check Slot time is greater than start time*/
            $day_start = $day_timeings['start'];
            $day_end = $day_timeings['end'];
            foreach ($slots as $key => $day_slot) {
                $slot_start = $day_slot['start_time'];
                $slot_end = $day_slot['end_time'];
                /* Day Eual to Week Day */
                if (date('l', strtotime($slot_start)) == $week_day) {
                    $isAvailbilites = isSlotAvailable($availabilites, $slot_start, $slot_end);
                    if ($isAvailbilites != true) {
                        //If slot fall in Breks or unavailable

                        $breaks = getBreaksDay($work_plan, $week_day, $availabilites);

                        $isBusy = isSlotUnavailableBreakSetSD($breaks, $unavailabilites, $slot_start, $slot_end);
                        if ($slot_end <= date(
                            "Y-m-d $day_start:00",
                                strtotime($date)
                        ) || $slot_start >= date("Y-m-d $day_end:00", strtotime($date))) {
                            $isBusy = true;
                        }
                        if ($isBusy) {
                            array_push(
                                $day_breaks,
                                array(
                                    'start' => date('H:i', strtotime($slot_start)),
                                    'end' => date('H:i', strtotime($slot_end)),
                                )
                            );
                            $last_break_end = $slot_end;
                        } else {
                            array_push($day_avail_slots, $day_slot);
                        }
                    } else {
                        array_push($day_avail_slots, $day_slot);
                    }
                }
            }

            $Schedule_week_day[strtolower($week_day)] = array(
                'start' => $day_start,
                'end' => $day_end,
                'breaks' => $day_breaks,
            );

            $Schedule_week_day[strtolower($week_day) . "_open"] = true;
            $avail_slots_week[$week_day] = $day_avail_slots;
            $breaks_slots_week[$week_day] = $day_breaks;
        }

        /* Final Loop */
        foreach ($avail_slots_week as $key => $avail_s) {
            $day = $key;
            $day_open = strtolower($day . "_open");
            if (!empty($avail_s)) {
                $min_max_time = get_min_time_slots($avail_s);
                $open = true;
                $last_brak_end = "";
                $last_brak_start = "";
                $new_slot = true;
                $day_break = [];

                $dbreaks = breaksToslots($breaks_slots_week[$day]);
                $working_plan_array[strtolower($day)] = array(
                    'start' => $min_max_time['start'],
                    'end' => $min_max_time['end'],
                    'breaks' => $dbreaks,
                );
            } else {
                $open = false;
            }
            $working_plan_array[$day_open] = $open;
        }

        $update_week_plan = json_encode($working_plan_array);
        $affectedRows = UserSetting::where('user_id', '=', intval($user_id))
            ->update(array('default_working_plan' => $update_week_plan));

        return true;
    }

    public function AvailableInTime($time_start, $time_end, $user_id)
    {
        $app_start_time = date('Y-m-d 00:00:00', strtotime($time_start));
        $app_end_time = date('Y-m-d 23:59:59', strtotime($time_end));
        $appointentments = Appointment::where('provider_user_id', $user_id)
            ->where('available_status', 2)
            ->whereBetween('start_datetime', [$app_start_time, $app_end_time])
            ->select(['start_datetime', 'end_datetime', 'available_status'])
            ->get()->toArray();
        return $appointentments;
    }

    public function UnAvailableInTime($time_start, $time_end, $user_id)
    {
        $app_start_time = date('Y-m-d 00:00:00', strtotime($time_start));
        $app_end_time = date('Y-m-d 23:59:59', strtotime($time_end));
        $appointentments = Appointment::where('provider_user_id', $user_id)
            ->where('available_status', 1)
            ->whereBetween('start_datetime', [$app_start_time, $app_end_time])
            ->select(['start_datetime', 'end_datetime', 'available_status'])
            ->get()->toArray();
        return $appointentments;
    }

    public function setWeekAsDeafult($unavailblities, $user_id)
    {
        foreach ($unavailblities as $unavailabe) {
            $wDay = date('l', strtotime($unavailabe['start_datetime']));
            $startTime = date('H:i', strtotime($unavailabe['start_datetime']));
            $endTime = date('H:i', strtotime($unavailabe['end_datetime']));
            $this->addBreakWeekDay(strtolower($wDay), $startTime, $endTime, $user_id);
        }
    }

    public function addBreakWeekDay($day, $time_from, $time_to, $user_id)
    {
        $working_plan_settings = UserSetting::where(
            'user_id',
            $user_id
        )->select('default_working_plan')->first()->toArray();
        $working_plan = $working_plan_settings['default_working_plan'];
        $working_plan_array = json_decode($working_plan, true);
        $day_open = $day . "_open";
        if (array_key_exists($day_open, $working_plan_array)) {
            /* Breaks Inforamtion*/
            $break = array('start' => $time_from, 'end' => $time_to);
            if ($working_plan_array[$day_open] == true) {
                array_push($working_plan_array[$day]['breaks'], $break);
            }
        }
        $json_working_plan = json_encode($working_plan_array);
        $update_data = array('default_working_plan' => $json_working_plan);
        UserSetting::where('user_id', '=', intval($user_id))->update($update_data);
        return true;
    }

    public function postSetDefaultWeekschedule()
    {
        $time_from = Input::get('time_from');
        $time_to = Input::get('time_to');
        //$timestamp = Input::get('timestamp');
        $user_id = Input::get('user_id');
        $app_legnth = Input::get('app_length');
        $days_arr = array();
        if (Input::get('days')) {
            $days = Input::get('days');
            foreach ($days as $key => $day) {
                if ($day) {
                    $days_arr[] = $key;
                }
            }
            if (count($days_arr) > 0) {
                $this->addWeekPlanNewUser($days_arr, $time_from, $time_to, $user_id, $app_legnth);
                return response()->success('week Schedule added');
            } else {
                return response()->error('Days Not Found');
            }
        } else {
            return response()->error('Days Not Found');
        }
    }

    public function addWeekPlanNewUser($week, $time_from, $time_to, $user_id, $app_legnth)
    {
        $ins_days = array();
        foreach ($week as $day) {
            $day_open = $day . "_open";
            $arr_day_add = array(
                $day_open => true,
                $day => array('start' => $time_from, 'end' => $time_to, 'breaks' => array())
            );
            $ins_days = array_merge($ins_days, $arr_day_add);
        }
        $user_setting = new UserSetting;
        $user_setting->user_id = $user_id;
        $user_setting->default_working_plan = json_encode($ins_days);
        $user_setting->default_appointment_length = $app_legnth;
        $user_setting->save();
        return true;
    }

    public function postAddMultipleBreaks()
    {
        $time_from = Input::get('time_from');
        $time_to = Input::get('time_to');
        $user_id = Input::get('user_id');
        if (Input::get('days')) {
            $days = Input::get('days');
            foreach ($days as $key => $day) {
                if ($day) {
                    $days_arr[] = $key;
                }
            }
            if (count($days_arr) > 0) {
                $this->addBreakWeek($days_arr, $time_from, $time_to, $user_id);
                return response()->success('week Schedule added');
            } else {
                return response()->error('Days Not Found');
            }
        } else {
            return response()->error('Days Not Found');
        }
    }

    public function addBreakWeek($week, $time_from, $time_to, $user_id)
    {
        $ins_days = array();
        $working_plan_settings = UserSetting::where(
            'user_id',
            $user_id
        )->select('default_working_plan')->first()->toArray();
        $working_plan = $working_plan_settings['default_working_plan'];
        $working_plan_array = json_decode($working_plan, true);
        foreach ($week as $key => $day) {
            $day_open = $day . "_open";
            if (array_key_exists($day_open, $working_plan_array)) {
                /* Breaks Inforamtion*/
                $break = array('start' => $time_from, 'end' => $time_to);
                if ($working_plan_array[$day_open] == true) {
                    if (array_key_exists('breaks', $working_plan_array[$day])) {
                        array_push($working_plan_array[$day]['breaks'], $break);
                    } else {
                        $working_plan_array[$day]['breaks'] = array();
                        array_push($working_plan_array[$day]['breaks'], $break);
                    }
                }
            }
        }
        $json_working_plan = json_encode($working_plan_array);
        $update_data = array('default_working_plan' => $json_working_plan);
        $affectedRows = UserSetting::where('user_id', '=', intval($user_id))->update($update_data);
        return true;
    }
}
