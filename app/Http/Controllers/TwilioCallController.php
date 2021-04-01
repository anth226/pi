<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
		$response = new VoiceResponse();
		$callerIdNumber = config('twilio.twilio.twilio_from');

		$dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
		$phoneNumberToDial = $request->input('phoneNumber');

		if (isset($phoneNumberToDial)) {
			$dial->number($phoneNumberToDial);
		}
		else {
			$dial->client('support_agent');
		}

		return $response;
	}
}
