<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Twilio\TwiML\VoiceResponse;

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
			$dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
			$dial->number($phoneNumberToDial);
			Storage::put('to_customer.txt', json_encode($request) );
		}
		else {
			if(!empty($request->input('From'))){
				$callerIdNumber = $request->input('From');
			}
			$dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
			$identity = "1";
			$dial->client($identity);
			Storage::put('from_customer.txt', json_encode($request) );
		}

		return $response;
	}
}
