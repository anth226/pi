<?php
/**
 * Created by PhpStorm.
 * User: K
 * Date: 3/12/2019
 * Time: 1:49 PM
 */

namespace App\KmClasses\Sms;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;


class SmsSender {
	protected $account_sid, $auth_token, $twilio_number, $app_url, $client;

	public function __construct()
	{
		$this->account_sid = config('twilio.twilio.twilio_sid');
		$this->auth_token = config('twilio.twilio.twilio_token');
		$this->twilio_number = config('twilio.twilio.twilio_from');
		$this->app_url = config('app.url');
		$this->client = new Client( $this->account_sid, $this->auth_token );
	}



	public function sendSupportSMS($formated_phone, $from, $message, $message_id = 0, $mediaUrl = false){
		$result = array(
			'sid' => '',
			'error_code' => 0
		);

		if($this->account_sid && $this->auth_token && $this->twilio_number && $formated_phone && $from) {
			try {
				//$client = new Client( $this->account_sid, $this->auth_token );
				$data = array(
					'from'           => $from,
					'body'           => $message,
					'statusCallback' => $this->app_url . '/api/supankapkryukhaha?me_id=' . $message_id
				);
				if ( ! empty( $mediaUrl ) ) {
					$data['mediaUrl'] = $mediaUrl;
				}
				$res = $this->client->messages->create(
					$formated_phone,
					$data
				);
				if ( $res ) {
					$result['sid'] = $res->sid;
				}
			} catch ( TwilioException $exception ) {
				if ( ! empty( $exception->getCode() ) ) {
					$result['error_code'] = $exception->getCode();	// https://www.twilio.com/docs/api/errors
				}
				$err = ! empty( $exception->getCode()) ? 'Error code: '.$exception->getCode(). ', ' : '';
				$err .= ! empty( $exception->getMessage()) ? 'Error code: '.$exception->getMessage() : '';
				$err .=  ! empty($result['sid']) ? ', sid: '.$result['sid'] : '';
//				Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'sendSupportSMS'] );
			}
		}
		else {
			$result['error_code'] = 111111;
		}
		return $result;
	}


}