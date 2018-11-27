<?php

function datediffInWeeks($date1, $date2)
{
    if ($date1 > $date2) {
        return datediffInWeeks($date2, $date1);
    }

    $first = DateTime::createFromFormat('m/d/Y', $date1);
    $second = DateTime::createFromFormat('m/d/Y', $date2);
    return floor($first->diff($second)->days / 7);
}

function WeekSartDateWeekNum($week_num, $year)
{
    $week_start = new DateTime();
    $week_start->setISODate($year, $week_num);
    return $week_start->format('d-m-Y'); // Monday
}

function week_from_monday($date)
{
    // Assuming $date is in format DD-MM-YYYY
    list($day, $month, $year) = explode("-", $date);
    // Get the weekday of the given date
    $wkday = date('l', mktime('0', '0', '0', $month, $day, $year));
    switch ($wkday) {
        case 'Monday':
            $numDaysToMon = 0;
            break;
        case 'Tuesday':
            $numDaysToMon = 1;
            break;
        case 'Wednesday':
            $numDaysToMon = 2;
            break;
        case 'Thursday':
            $numDaysToMon = 3;
            break;
        case 'Friday':
            $numDaysToMon = 4;
            break;
        case 'Saturday':
            $numDaysToMon = 5;
            break;
        case 'Sunday':
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
    return $dates;
}

function getDatesFromRange($start, $end, $format = 'Y-m-d')
{
    $array = array();
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($end);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

    foreach ($period as $date) {
        $array[] = $date->format($format);
    }
    return $array;
}

function getStartEndTime($work_plan, $day, $availabilites = '')
{
    $work_plan_array = json_decode($work_plan, true);
    $out = '';
    $c_open = strtolower($day . '_open');
    $avail_starts = array();
    $avail_ends = array();
    $avl_Min = '';
    $avl_Max = '';
    foreach ($work_plan_array as $key => $working_days) {
        if (isset($work_plan_array[$c_open]) && $work_plan_array[$c_open] == true) {
            if (ucfirst($key) == ucfirst($day)) {
                $start = $working_days['start'];
                $end = $working_days['end'];
                $out = array(
                    'start' => $start,
                    'end' => $end,
                );
                break;
            }
        }
    }
    if ($out != "") {
        if ($availabilites != '') {
            foreach ($availabilites as $key => $availability) {
                if (date('l', strtotime($availability['start_datetime'])) == $day) {
                    $avail_starts[] = date('H:i', strtotime($availability['start_datetime']));
                    $avail_ends[] = date('H:i', strtotime($availability['end_datetime']));
                }
            }

            if (!empty($avail_starts)) {
                $avl_Min = min($avail_starts);
            }

            if (!empty($avail_ends)) {
                $avl_Max = min($avail_ends);
            }

            if ($avl_Min < $out['start'] && $avl_Min != '') {
                $out['start'] = $avl_Min;
            }

            if ($avl_Max > $out['end'] && $avl_Max != '') {
                $out['end'] = $avl_Max;
            }
        }
        return $out;
    } else {
        return false;
    }
}

function getTimeSlots($starttime, $end_time, $duration, $date)
{
    $array_of_time = array();
    $start_time = strtotime($date . " " . $starttime . ":00"); //change to strtotime
    $end_time = strtotime($date . " " . $end_time . ":00"); //change to strtotime
    $add_mins = $duration * 60;
    while ($start_time < $end_time) { // loop between time
        $end_time_slot = $start_time + $add_mins;
        $array_of_time[] = array(
            'start_time' => date("Y-m-d H:i:s", $start_time),
            'end_time' => date("Y-m-d H:i:s", $end_time_slot),
        );
        $start_time += $add_mins; // to check endtie=me
    }
    return $array_of_time;
}

function getBreaksDay($work_plan, $day)
{
    $breaks = false;
    $work_plan_array = json_decode($work_plan, true);
    $out = '';

    foreach ($work_plan_array as $key => $working_days) {
        if (ucfirst($key) == ucfirst($day)) {
            if (array_key_exists('breaks', $working_days)) {
                $breaks = $working_days['breaks'];
            }
        }
    }
    return $breaks;
}

function isSlotBusy($breaks, $appointments, $slot_start, $slot_end)
{
    $slot_start_app = $slot_start;
    $slot_end_app = $slot_end;

    foreach ($appointments as $appointment) {
        $slot_start = strtotime($slot_start_app);
        $slot_end = strtotime($slot_end_app);
        $app_start = strtotime($appointment['start_datetime']);
        $app_end = strtotime($appointment['end_datetime']);

        if ($appointment['available_status'] == 2) {
            return false;
        }
        //echo "Slot Start:".$slot_start." Slot End:".$slot_end." App start:".$app_start." App end:".$app_end.'<br>';

        if (($slot_start >= $app_start && $slot_start < $app_end) || ($slot_end > $app_start && $slot_end < $app_end)) {
            return true;
        }
    }

    /* Breaks */
    if (count($breaks) > 0 && $breaks != false) {
        foreach ($breaks as $break) {
            $break_day = date('Y-m-d', strtotime($slot_start));
            $break_start = strtotime($break_day . " " . $break['start'] . ":00");
            $break_end = strtotime($break_day . " " . $break['end'] . ":00");
            $slot_start = strtotime($slot_start);
            $slot_end = strtotime($slot_end);

            if (($slot_start >= $break_start && $slot_start < $break_end) || ($slot_end > $break_start && $slot_end < $break_end)) {
                return true;
            }
        }
    }
    return false;
}

function isSlotUnavailableBreak($breaks, $appointments, $slot_start, $slot_end)
{
    $slot_start_app = $slot_start;
    $slot_end_app = $slot_end;
    foreach ($appointments as $app_key => $appointment) {
        $slot_start = strtotime($slot_start_app);
        $slot_end = strtotime($slot_end_app);
        $app_start = strtotime($appointment['start_datetime']);
        $app_end = strtotime($appointment['end_datetime']);
        //echo "Slot Start:".$slot_start." Slot End:".$slot_end." App start:".$app_start." App end:".$app_end.'<br>';
        if (($slot_start >= $app_start && $slot_start < $app_end) || ($slot_end > $app_start && $slot_end < $app_end)) {
            return true;
        }
    }

    /* Breaks */
    //echo count($breaks);
    if (count($breaks) > 0 && $breaks != false) {
        foreach ($breaks as $key => $break) {
            $break_day = date('Y-m-d', strtotime($slot_start));
            $break_start = strtotime($break_day . " " . $break['start'] . ":00");
            $break_end = strtotime($break_day . " " . $break['end'] . ":00");

            $slot_start = strtotime($slot_start);
            $slot_end = strtotime($slot_end);
            if (($slot_start >= $break_start && $slot_start < $break_end) || ($slot_end > $break_start && $slot_end < $break_end)) {
                return true;
            }
        }
    }
    /* / Breaks */
    return false;
}

function isSlotUnavailableBreakSetSD($breaks, $appointments, $slot_start, $slot_end)
{
    $slot_start_app = $slot_start;
    $slot_end_app = $slot_end;
    //print_r($appointments);
    foreach ($appointments as $app_key => $appointment) {
        $slot_start = strtotime($slot_start_app);
        $slot_end = strtotime($slot_end_app);
        $app_start = strtotime($appointment['start_datetime']);
        $app_end = strtotime($appointment['end_datetime']);
        if (($slot_start <= $app_start && $slot_end > $app_start) || ($slot_end <= $app_end && $slot_end > $app_start) || ($app_start < $slot_start && $app_end > $slot_start && $app_end < $slot_end)) {
            return true;
        }
    }

    /* Breaks */
    if ($breaks != false) {
        foreach ($breaks as $key => $break) {
            $break_day = date('Y-m-d', strtotime($slot_start_app));
            $break_start = strtotime($break_day . " " . $break['start'] . ":00");
            $break_end = strtotime($break_day . " " . $break['end'] . ":00");
            $slot_start = strtotime($slot_start_app);
            $slot_end = strtotime($slot_end_app);
            if (($slot_start <= $break_start && $slot_end > $break_start) || ($slot_end <= $break_end && $slot_end > $break_start) || ($break_start < $slot_start && $break_end > $slot_start && $break_end < $slot_end)) {
                return true;
            }
        }
    }
    /* / Breaks */
    return false;
}

function isSlotAvailable($availabilites, $slot_start, $slot_end)
{
    $slot_start_app = $slot_start;
    $slot_end_app = $slot_end;
    foreach ($availabilites as $avail) {
        $slot_start = strtotime($slot_start_app);
        $slot_end = strtotime($slot_end_app);
        $avail_start = strtotime($avail['start_datetime']);
        $avail_end = strtotime($avail['end_datetime']);
        if (($slot_start >= $avail_start && $slot_start < $avail_end) || ($slot_end > $avail_start && $slot_end < $avail_end)) {
            return true;
        }
    }
    return false;
}

function getWeekStartEndTime($work_plan, $availabilites)
{
    $avail_starts = array();
    $avail_ends = array();
    $start_times = array();
    $end_times = array();
    $avl_Min = '';
    $avl_Max = '';
    $work_plan_array = json_decode($work_plan, true);
    $out = '';
    $working_true = false;
    $days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
    foreach ($days as $key => $day) {
        foreach ($work_plan_array as $key => $working_days) {
            if (ucfirst($key) == ucfirst($day)) {
                if (array_key_exists("start", $working_days)) {
                    $start_times[] = $working_days['start'];
                    $end_times[] = $working_days['end'];
                    break;
                }
            }
        }
    }

    //Get availabilities
    // avl_Min start time and avl_max end time from avilability

    foreach ($availabilites as $key => $availability) {
        $avail_starts[] = date('H:i', strtotime($availability['start_datetime']));
        $avail_ends[] = date('H:i', strtotime($availability['end_datetime']));
    }
    if (!empty($avail_starts)) {
        $avl_Min = min($avail_starts);
    }

    if (!empty($avail_ends)) {
        $avl_Max = min($avail_ends);
    }

    $out = array(
        'start' => min($start_times),
        'end' => max($end_times),
    );

    if ($avl_Min < $out['start'] && $avl_Min != '') {
        $out['start'] = $avl_Min;
    }

    if ($avl_Max > $out['end'] && $avl_Max != '') {
        $out['end'] = $avl_Max;
    }
    return $out;
}

function get_min_time_slots($slots)
{
    $slts_starts = array();
    $slts_ends = array();
    foreach ($slots as $slt) {
        $slts_starts[] = date('H:i', strtotime($slt['start_time']));
        $slts_ends[] = date('H:i', strtotime($slt['end_time']));
    }
    $out = array(
        'start' => min($slts_starts),
        'end' => max($slts_ends),
    );
    return $out;
}

function app_admin_email()
{
    return getenv('APP_ADMIN_EMAIL');
}
function app_from_email()
{
    return getenv('APP_FROM_EMAIL');
}

// For Create Guid Data
function GUID()
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

function breaksToslots($breaks)
{
    $flag = false;
    $new_breaks = array();
    $i = 0;
    foreach ($breaks as $break) {
        $i++;
        $curr_break_start = $break['start'];
        $curr_break_end = $break['end'];

        if (keyexistsinmultiarray($breaks, 'start', $curr_break_end)) {
            $new_break_end = $curr_break_end;
            if ($flag == false) {
                $new_break_start = $curr_break_start;
            }
            $flag = true;
            continue;
        } else {
            if ($flag == false) {
                $new_break_start = $curr_break_start;
            }
            $new_break_end = $curr_break_end;
            $flag = false;
            $new_breaks[] = array('start' => $new_break_start, 'end' => $new_break_end);
        }
    }
    return $new_breaks;
}

function keyexistsinmultiarray($array, $key, $val)
{
    foreach ($array as $item) {
        if (isset($item[$key]) && $item[$key] == $val) {
            return true;
        }
    }

    return false;
}

function format_phone_number($number)
{
    $result = preg_replace('~.*(\d{2})[^\d]{0,7}(\d{4})[^\d]{0,7}(\d{4}).*~', '$1$2$3', $number);
    $result = implode(array_filter(str_split($result, 1), "is_numeric"));
    return $result;
}

function default_phone_country_code()
{
    return getenv('APP_PHONE_COUNTRY_CODE');
}

if (!function_exists('get_files_in')) {
    function get_files_in($val)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_FRESH_CONNECT => true, CURLOPT_FAILONERROR => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_URL => $val, CURLOPT_HEADER => 'User-Agent: Chrome\r\n', CURLOPT_TIMEOUT => '3L'));
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }
}
function split_name($name)
{
    $name = trim($name);
    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));
    if (empty($first_name)) {
        $first_name = $last_name;
        $last_name = '';
    }

    return array($first_name, $last_name);
}

function maskPhoneNumber($phoneNumber)
{
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    if (strlen($phoneNumber) > 10) {
        $countryCode = substr($phoneNumber, 0, strlen($phoneNumber) - 10);
        $areaCode = substr($phoneNumber, -10, 3);
        $nextThree = substr($phoneNumber, -7, 3);
        $lastFour = substr($phoneNumber, -4, 4);

        $phoneNumber = '+' . $countryCode . ' (' . $areaCode . ') ' . $nextThree . '-' . $lastFour;
    } elseif (strlen($phoneNumber) == 10) {
        $areaCode = substr($phoneNumber, 0, 3);
        $nextThree = substr($phoneNumber, 3, 3);
        $lastFour = substr($phoneNumber, 6, 4);

        $phoneNumber = '(' . $areaCode . ') ' . $nextThree . '-' . $lastFour;
    } elseif (strlen($phoneNumber) == 7) {
        $nextThree = substr($phoneNumber, 0, 3);
        $lastFour = substr($phoneNumber, 3, 4);

        $phoneNumber = $nextThree . '-' . $lastFour;
    }

    return $phoneNumber;
}

function GroupingSlots($slots)
{
    $days = [];
    $out = [];
    foreach ($slots as $slot) {
        $day = date('Y-m-d', strtotime($slot['start_time']));
        if (!in_array($day, $days)) {
            $out[$day] = [];
            $days[] = $day;
        }
        if (in_array($day, $days)) {
            $time_a = array(
                'start_time' => date('h:i A', strtotime($slot['start_time'])),
                'end_time' => date('h:i A', strtotime($slot['end_time'])),
            );
            array_push($out[$day], $time_a);
        }
    }
    return $out;
}
function relativeTime($time, $short = false)
{
    $time = strtotime($time);
    $SECOND = 1;
    $MINUTE = 60 * $SECOND;
    $HOUR = 60 * $MINUTE;
    $DAY = 24 * $HOUR;
    $MONTH = 30 * $DAY;
    $before = time() - $time;
    if ($before < 0) {
        return "not yet";
    }

    if ($short) {
        if ($before < 1 * $MINUTE) {
            return ($before < 5) ? "just now" : $before . " ago";
        }

        if ($before < 2 * $MINUTE) {
            return "1m ago";
        }

        if ($before < 45 * $MINUTE) {
            return floor($before / 60) . "m ago";
        }

        if ($before < 90 * $MINUTE) {
            return "1h ago";
        }

        if ($before < 24 * $HOUR) {
            return floor($before / 60 / 60) . "h ago";
        }

        if ($before < 48 * $HOUR) {
            return "1d ago";
        }

        if ($before < 30 * $DAY) {
            return floor($before / 60 / 60 / 24) . "d ago";
        }

        if ($before < 12 * $MONTH) {
            $months = floor($before / 60 / 60 / 24 / 30);
            return $months <= 1 ? "1mo ago" : $months . "mo ago";
        } else {
            $years = floor($before / 60 / 60 / 24 / 30 / 12);
            return $years <= 1 ? "1y ago" : $years . "y ago";
        }
    }

    if ($before < 1 * $MINUTE) {
        return ($before <= 1) ? "just now" : $before . " seconds ago";
    }

    if ($before < 2 * $MINUTE) {
        return "a minute ago";
    }

    if ($before < 45 * $MINUTE) {
        return floor($before / 60) . " minutes ago";
    }

    if ($before < 90 * $MINUTE) {
        return "an hour ago";
    }

    if ($before < 24 * $HOUR) {
        return (floor($before / 60 / 60) == 1 ? 'about an hour' : floor($before / 60 / 60) . ' hours') . " ago";
    }

    if ($before < 48 * $HOUR) {
        return "yesterday";
    }

    if ($before < 30 * $DAY) {
        return floor($before / 60 / 60 / 24) . " days ago";
    }

    if ($before < 12 * $MONTH) {
        $months = floor($before / 60 / 60 / 24 / 30);
        return $months <= 1 ? "one month ago" : $months . " months ago";
    } else {
        $years = floor($before / 60 / 60 / 24 / 30 / 12);
        return $years <= 1 ? "one year ago" : $years . " years ago";
    }

    return "$time";
}

/* Get  weeks beetween weeks */
function getWeeksDaysBetweenDays($start_date, $end_date, $format)
{
    $startDateUnix = strtotime($start_date);
    $endDateUnix = strtotime($end_date);
    $currentDateUnix = $startDateUnix;
    $weekNumbers = array();
    while ($currentDateUnix < $endDateUnix) {
        $weekNumbers[] = date($format, $currentDateUnix);
        $currentDateUnix = strtotime('+1 week', $currentDateUnix);
    }
    if(count($weekNumbers)>0){
        $keys = array_keys($weekNumbers);
        $last = end($keys);
        $weekNumbers[$last] = date($format, $endDateUnix);
    }
    return $weekNumbers;
}

/* Get  Months beetween weeks */
function getMonthsDaysBetweenDays($startDate, $endDate, $format = "m-y")
{
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    $current = $start;
    $ret = array();
    $c = strtotime($startDate);
    $ret[] = date($format, $c);

    while ($current <= $end) {
        $next = @date('Y-M-01', $current) . "+1 month";
        $current = @strtotime($next);
        $ret[] = date($format, $current);
    }
    $retf = array_pop($ret);
    return $ret;
}

/* Get  Years beetween weeks */
function getYearsBetweenDays($startDate, $endDate, $format = "m-y")
{
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    $current = $start;
    $ret = array();
    $c = strtotime($startDate);
    $ret[] = date($format, $c);
    while ($current < $end) {
        $next = @date('Y-M-01', $current) . "+1 year";
        $current = @strtotime($next);
        $ret[] = date($format, $current);
    }
    $retf = array_pop($ret);
    return $ret;
}

function getCountDaysBeetweenDates($startDate, $endDate)
{
    $date1 = new DateTime($startDate);
    $date2 = new DateTime($endDate);
    $diff = $date2->diff($date1)->format("%a");
    return $diff;
}

function secToMin($sec)
{
    if ($sec > 59) {
        return gmdate('i:s', $sec) . ' Min';
    }
    return $sec . " Sec";
}

function getRatingScale($rating)
{
    if ($rating >= 0 && $rating < 2) {
        $out = array('title' => 'Bad', 'class' => 'bad-number-rating');
    } elseif ($rating >= 2 && $rating < 3) {
        $out = array('title' => 'Not Good', 'class' => 'not_good-number-rating');
    } elseif ($rating >= 3 && $rating < 4) {
        $out = array('title' => 'Good', 'class' => 'good-number-rating');
    } elseif ($rating >= 4 && $rating < 4) {
        $out = array('title' => 'Very Good', 'class' => 'very_good-number-rating');
    } else {
        $out = array('title' => 'Excellent', 'class' => 'excellent-number-rating');
    }

    return $out;
}

function dateCountDiff($d1, $d2)
{
    $date1 = new DateTime($d1);
    $date2 = new DateTime($d2);
    return $date2->diff($date1)->format("%a");
}

function lead_requruiref_fieds($fields)
{
    $fields = array_keys($fields);
    $req = ['first_name', 'email', 'phone'];
    foreach ($req as $req_field) {
        if (!in_array($req_field, $fields)) {
            return $req_field;
        }
    }
    return true;
}

function task_requruiref_fieds($fields)
{
    $fields = array_keys($fields);
    $req = ['title', 'type', 'description', 'action_date', 'duration', 'priority'];
    foreach ($req as $key => $req_field) {
        if (!in_array($req_field, $fields)) {
            return $req_field;
        }
    }
    return true;
}

function searchForLabel($label, $array)
{
    if ($array == null) {
        return null;
    }
    foreach ($array as $key => $val) {
        if ($val['label'] === $label) {
            return $key;
        }
    }
    return null;
}

function searchForId($id, $array)
{
    foreach ($array as $key => $val) {
        if ($val['id'] === $id) {
            return $key;
        }
    }
    return null;
}

function findSpecialitieFromName($array, $name)
{
    $result = array_filter($array, function ($item) use ($name) {
        if (stripos($item['name'], $name) !== false) {
            return true;
        }
        return false;
    });
}

function convertSingleDimenstionalArray($input)
{
    $out = [];
    // print_r($input); die;
    if (isset($input['code']) == false && count($input) > 0) {
        foreach ($input as $key => $post) {
            $child_a = [];
            foreach ($post as $key => $value) {
                if (!is_array($value)) {
                    $child_a[$key] = $value;
                }
                if ($key == 'title') {
                    $child_a[$key] = $value['rendered'];
                }
                if ($key == 'meta') {
                    if (isset($post['meta']['wpcf_email'][0])) {
                        $child_a['email'] = $post['meta']['wpcf_email'][0];
                    }
                }

                if ($key == 'meta') {
                    if (isset($post['meta']['wpcf_phone_no'][0])) {
                        $child_a['phone'] = $post['meta']['wpcf_phone_no'][0];
                    }
                }
                if ($key == 'meta') {
                    if (isset($post['meta']['wpcf_province'][0])) {
                        $child_a['province'] = $post['meta']['wpcf_province'][0];
                    }
                    if (isset($post['meta']['wpcf_address'][0])) {
                        $child_a['address'] = $post['meta']['wpcf_address'][0];
                    }
                    if (isset($post['meta']['wpcf_website'][0])) {
                        $child_a['website_url'] = $post['meta']['wpcf_website'][0];
                    }
                    if (isset($post['meta']['wpcf_gender'][0])) {
                        $child_a['gender'] = $post['meta']['wpcf_gender'][0];
                    }
                    if (isset($post['meta']['wpcf_city'][0])) {
                        $child_a['city'] = $post['meta']['wpcf_city'][0];
                    }
                    if (isset($post['meta']['wpcf_country'][0])) {
                        $child_a['country'] = $post['meta']['wpcf_country'][0];
                    }
                    if (isset($post['meta']['wpcf_facebook_link'][0])) {
                        $child_a['facebook_link'] = $post['meta']['wpcf_facebook_link'][0];
                    }
                    if (isset($post['meta']['wpcf_twitter_link'][0])) {
                        $child_a['twitter_link'] = $post['meta']['wpcf_twitter_link'][0];
                    }
                    if (isset($post['meta']['wpcf_google_link'][0])) {
                        $child_a['google_link'] = $post['meta']['wpcf_google_link'][0];
                    }
                    if (isset($post['meta']['wpcf_job_title'][0])) {
                        $child_a['job_title'] = $post['meta']['wpcf_job_title'][0];
                    }
                    if (isset($post['meta']['wpcf_youtube_link'][0])) {
                        $child_a['youtube_link'] = $post['meta']['wpcf_youtube_link'][0];
                    }
                    if (isset($post['meta']['wpcf_instagram_link'][0])) {
                        $child_a['instagram_link'] = $post['meta']['wpcf_instagram_link'][0];
                    }
                    if (isset($post['meta']['wpcf_social_links'][0])) {
                        $child_a['social_links'] = $post['meta']['wpcf_social_links'][0];
                    }
                    if (isset($post['meta']['linkedin_link'][0])) {
                        $child_a['linkedin_link'] = $post['meta']['wpcf_linkedin_link'][0];
                    }
                    if (isset($post['meta']['wpcf_claim_status'][0])) {
                        $child_a['claim_status'] = $post['meta']['wpcf_claim_status'][0];
                    }
                    if (isset($post['meta']['wpcf_certifications'][0])) {
                        $child_a['certifications'] = $post['meta']['wpcf_certifications'][0];
                    }
                    if (isset($post['meta']['wpcf_linkedin_link'][0])) {
                        $child_a['linkedin_link'] = $post['meta']['wpcf_linkedin_link'][0];
                    }
                    if (isset($post['meta']['wpcf_education'][0])) {
                        $child_a['education'] = $post['meta']['wpcf_education'][0];
                    }
                    if (isset($post['meta']['wpcf_hospital_affiliations'][0])) {
                        $child_a['hospital_affiliations'] = $post['meta']['wpcf_hospital_affiliations'][0];
                    }
                    if (isset($post['meta']['wpcf_hospital_affiliations'][0])) {
                        $child_a['hospital_affiliations'] = $post['meta']['wpcf_hospital_affiliations'][0];
                    }
                    if (isset($post['meta']['wpcf_additional_info'][0])) {
                        $child_a['additional_info'] = $post['meta']['wpcf_additional_info'][0];
                    }
                    if (isset($post['meta']['wpcf_clinic_name'][0])) {
                        $child_a['clinic_name'] = $post['meta']['wpcf_clinic_name'][0];
                    }
                }
                if ($key == 'content') {
                    $child_a['description'] = $post['content']['rendered'];
                }
            }
            $out[] = $child_a;
        }
    }
    return $out;
}

function convertHdClinicData($input)
{
    $out = [
        'name' => $input['title'],
        'description' => $input['description'],
        'address' => $input['address'],
        'state' => $input['province'],
        'email' => $input['email'],
        'country' => $input['country'],
        'city' => $input['city'],
        'phone' => $input['phone'],
        'site_url' => $input['website_url'],
        'hd_publish_status' => $input['status'],
        'hd_post_id' => $input['id'],
    ];
    if (isset($input['facebook_link'])) {
        $out['facebook_link'] = $input['facebook_link'];
    }
    if (isset($input['twitter_link'])) {
        $out['twitter_link'] = $input['twitter_link'];
    }
    if (isset($input['google_link'])) {
        $out['google_link'] = $input['google_link'];
    }
    if (isset($input['youtube_link'])) {
        $out['youtube_link'] = $input['youtube_link'];
    }
    if (isset($input['instagram_link'])) {
        $out['instagram_link'] = $input['instagram_link'];
    }
    if (isset($input['social_links'])) {
        $out['social_links'] = $input['social_links'];
    }
    if (isset($input['linkedin_link'])) {
        $out['linkedin_link'] = $input['linkedin_link'];
    }
    if (isset($input['claim_status'])) {
        $out['claim_status'] = $input['claim_status'];
    }
    if (isset($input['certifications'])) {
        $out['certifications'] = $input['certifications'];
    }
    //print_r($out); die;
    return $out;
}
function convertHdProviderData($input)
{
    if (!isset($input['website_url'])) {
        $input['website_url'] = '';
    }
    if (!isset($input['address'])) {
        $input['address'] = '';
    }
    if (!isset($input['job_title'])) {
        $input['job_title'] = '';
    }
    if (!isset($input['gender'])) {
        $input['gender'] = '';
    }
    $out = [
        'data.name' => $input['title'],
        'data.bio' => $input['description'],
        'data.gender' => $input['gender'],
        'state' => $input['province'],
        'data.email' => $input['email'],
        'data.phone' => $input['phone'],
        'data.website_url' => $input['website_url'],
        'data.province' => $input['province'],
        'data.address' => $input['address'],
        'data.city' => $input['city'],
        'data.country' => $input['country'],
        'data.hd_publish_status' => $input['status'],
        'data.job_title' => $input['job_title'],
    ];
    if (isset($input['facebook_link'])) {
        $out['data.facebook_link'] = $input['facebook_link'];
    }
    if (isset($input['twitter_link'])) {
        $out['data.twitter_link'] = $input['twitter_link'];
    }
    if (isset($input['google_link'])) {
        $out['data.google_link'] = $input['google_link'];
    }
    if (isset($input['youtube_link'])) {
        $out['data.youtube_link'] = $input['youtube_link'];
    }
    if (isset($input['instagram_link'])) {
        $out['data.instagram_link'] = $input['instagram_link'];
    }
    if (isset($input['social_links'])) {
        $out['data.social_links'] = $input['social_links'];
    }
    if (isset($input['linkedin_link'])) {
        $out['data.linkedin_link'] = $input['linkedin_link'];
    }
    if (isset($input['claim_status'])) {
        $out['data.claim_status'] = $input['claim_status'];
    }
    if (isset($input['certifications'])) {
        $out['data.certifications'] = $input['certifications'];
    }
    if (isset($input['education'])) {
        $out['data.education'] = $input['education'];
    }
    if (isset($input['hospital_affiliations'])) {
        $out['data.hospital_affiliations'] = $input['hospital_affiliations'];
    }
    if (isset($input['additional_info'])) {
        $out['data.additional_info'] = $input['additional_info'];
    }
    if (isset($input['clinic_name'])) {
        $out['data.clinic_name'] = $input['clinic_name'];
    }
    return $out;
}

function specializationsArray($specializations)
{
    $out = [];
    if (count($specializations) > 0) {
        foreach ($specializations as $spcl) {
            $out[] = $spcl['wp_id'];
        }
    }
    return $out;
}

function restrictedCompaniesRoles()
{
    return ["super.call.center", "super.admin.agent"];
}

function configStatusesList()
{
    return ['providers_setup', 'notification_setup', 'user_setup', 'google_analytics_setup','twilio_setup', 'sendgrid_setup','skip_integration'];
}
function intigrationsKeys()
{
    return [
        'google_analytics'=>[
        'google_analytics_setup',
        'analytics_profile_id',
        'google_analytics_token',
        'google_analytics_refresh_token'
        ]
        ];
}

function getSmsListsFromInput($input)
{
    $out = [];
    foreach ($input as $list) {
        $out[] =  $list['id'];
    }
    return $out;
}

function mergeTagsContact()
{
    return ['{$first_name}'=>'first_name',
        '{$last_name}'=>'last_name',
        '{$phone_number}'=>'mobile_number',
        '{$email}'=>'email'
    ];
}
function mergeTagsCompany()
{
    return ['{$client_name}'=>'name',
        '{$company_name}'=>'name',
        '{$location}'=>'address',
        '{$office_phone}'=>'phone',
        '{$website_link}'=>'site_url'
    ];
}
function mergeTagsLead()
{
    return ['{$first_name}'=>'first_name',
    '{$last_name}'=>'last_name',
    '{$phone_number}'=>'phone_number',
    '{$name}'=>'full_name',
    '{$email}'=>'email',
    '{{contant_name}}'=>'full_name',
    '{{contact_phone}}'=>'phone_number',
    '{{contact_email}}'=>'email',
    '{{notes}}'=>'notes',
    '{{bob_signature}}'=>'sign',
    '{{source}}'=>'source',
    '{{assignee}}'=>'assign',
    '{{time}}'=>'time',
    '{$bob_signature}'=>'sign',
    '{$source}'=>'source',
    '{$notes}'=>'notes',
    '{$time}'=>'time'];
}

function getAuthUserArray($user)
{
    $allow_arrays = ['id','name','roles','avatar','status'];
    $out =[];
    foreach ($allow_arrays as $u_fields) {
        $out[$u_fields]= $user[$u_fields];
    }
    return $out;
}
function filterAbilities($all_ablties, $allow_ableties)
{
    $required_abelities = ["admin.user", "super.call.center", "super.admin.agent", "admin.super","doctor", "sales"];
    $out = [];
    foreach ($required_abelities as $u_fields) {
        $out[$u_fields]= $all_ablties[$u_fields];
    }
    if (!in_array($all_ablties, $all_ablties)) {
        $out[$allow_ableties] = $all_ablties[$allow_ableties];
    }
    return $out;
}
