<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Carbon\Carbon;

class TwilioCallController extends Controller
{
	/**
	 * Process a new call
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function newCall(Request $request)
	{
//		Storage::put('request.txt', json_encode($request) );
		$response = new VoiceResponse();
		$callerIdNumber = config('twilio.twilio.twilio_from');

		$phoneNumberToDial = $request->input('phoneNumber');

		if (isset($phoneNumberToDial)) {
			$dial = $response->dial($phoneNumberToDial, ['callerId'=>$callerIdNumber]);
			$dial->number($phoneNumberToDial);
		}
		else {
			if(!empty($request->input('From'))){
				$callerIdNumber = $request->input('From');
			}
			$dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
			$identity = "1";
			$dial->client($identity);
		}

		return $response;
	}

	public function callStats(Request $request){
//		Storage::put('calls.txt', json_encode($request) );
		Storage::append('calls.log', json_encode($request->input()));
	}

	public function getLogBySid($call_sid){
		$log = [];
		$sid           = config( 'twilio.twilio.twilio_sid' );
		$token         = config( 'twilio.twilio.twilio_token' );
		$twilio = new Client( $sid, $token );
		try{
			$call = $twilio->calls
				->read(["parentCallSid" => $call_sid], 1);
			if(!empty($call) && !empty($call[0])) {
				$log['status']   = $call[0]->status;
				$log['duration'] = $call[0]->duration;
				$log['from']     = $call[0]->from;
				$log['to']       = $call[0]->to;
			}
			return $log;
		}
		catch ( TwilioException $exception ) {
			$error = '';
			if ( ! empty( $exception->getCode() ) ) {
				$error .= $exception->getCode().' ';
				// https://www.twilio.com/docs/api/errors
			}
			if ( ! empty( $exception->getMessage() ) ) {
				$error .= $exception->getMessage();
			}
			return $error;
		}
	}

}
