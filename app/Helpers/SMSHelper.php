<?php

namespace App\Helpers;

class SMSHelper extends HttpHelper
{
    public static function sendData($data, $action_path = 'ulpi')
    {
        if($smsUrl = config( 'smssystem.url' )) {
            $url  = $smsUrl.$action_path;
            return send_http_request($url, 'POST', $data);
        }
        log_error( "SMS Url not found ", 'SMSHelper', 'sendData');
        return [
            'success' => false,
            'message' => "SMS Url not found "
        ];
    }
}