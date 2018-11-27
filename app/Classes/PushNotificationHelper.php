<?php
namespace App\Classes;

use App\AdminView;
use App\Classes\ActivityHelper;
use App\User;

class PushNotificationHelper
{
    public static function sendPushNotification($company_id, $activity_type, $object_type, $object_id, $contact_id = null, $user_id = null, $created_by = null, $created_at = null)
    {
        $activity_done = str_replace('_', ' ', $activity_type);
        $activities = array('SMS_SEND', 'SMS_REMINDER_SEND');
        if (!in_array($activity_type, $activities)) {
            $playerIds = array();
            $company_owner = AdminView::where('company_id', $company_id)->first();
            if ($company_owner) {
                $players = User::whereIn('id', [$company_owner->user_id, $user_id])->where('company_id', $company_id)->get();
                foreach ($players as $player) {
                    $message = ActivityHelper::translateActivity($company_id, $activity_type, $object_type, $object_id, $contact_id, $user_id, $created_by, $created_at);
                    /*URL*/
                    $url = self::getUrl($message);
                    /**URL**/
                    $message = strip_tags($message);
                    $content = array("en" => $message);
                    $url = $url;
                    //dd($url);
                    if ($player->player_id) {
                        $playerIds[] = $player->player_id;
                    }
                }
                $fields = array('app_id' => getenv('ONESIGNAL_APPID'), 'include_player_ids' => $playerIds, 'contents' => $content, 'url' => $url);
                $fields = json_encode($fields);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type: application/json; charset=utf-8',
                        'Authorization: Basic ' . getenv('ONESIGNAL_APPKEY') . '',
                    )
                );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($ch);
                curl_close($ch);
                //dd($response);
                return $response;
            }
        }
        return true;
    }
    public static function getUrl($message)
    {
        $url = "";
        preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', $message, $matches);
        if (!empty($matches)) {
            $url = $matches[2];
            $url = current($url);
            $host = \Request::getSchemeAndHttpHost();
            $url = $host . "/" . $url;
        }
        return $url;
    }
}
