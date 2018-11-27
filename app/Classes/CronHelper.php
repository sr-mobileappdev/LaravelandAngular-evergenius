<?php
namespace App\Classes;

use App\CronRecord;

class CronHelper
{
    public static function createCronRecord($type)
    {
        $current_time = date('Y-m-d H:i:s', time());
        $cron_record = new CronRecord;
        $cron_record->type = $type;
        $cron_record->start_at = $current_time;
        $cron_record->last_execution_time = $current_time;
        $cron_record->created_at = $current_time;
        $cron_record->save();
        return $cron_record->id;
    }

    public static function getRecentExecutedTime($type)
    {
        $record = CronRecord::select('last_execution_time')
            ->where('type', $type)
            ->orderBy('id', 'desc')
            ->first();
        if (count($record) > 0) {
            return $record->last_execution_time;
        } else {
            return false;
        }
    }
    public static function udateCronEndTime($id)
    {
        $current_time = date('Y-m-d H:i:s', time());
        CronRecord::where('id', '=', intval($id))->update(array('end_at' => $current_time));
        return true;
    }
}
