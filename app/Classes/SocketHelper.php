<?php
namespace App\Classes;

use L5Redis;

class SocketHelper
{
    public static function IncomingSmsNotify($sms_data, $contact_id)
    {
        $redis = L5Redis::connection();
        $redis->publish(
            'chat.message',
            json_encode(
                [
                    'sms_data' => $sms_data,
                    'contact_id' => $contact_id,
                    'incoming_sms' => true,
                ]
            )
        );
    }
}
