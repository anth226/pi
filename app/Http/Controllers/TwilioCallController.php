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
		Storage::put('request.txt', json_encode($request) );
		$response = new VoiceResponse();
		$callerIdNumber = config('twilio.twilio.twilio_from');

		$dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
		$phoneNumberToDial = $request->input('phoneNumber');

//		Storage::put('headers.txt', json_encode($request->input('phoneNumber')) );
		Storage::put('phon_number.txt', $phoneNumberToDial );

		if (isset($phoneNumberToDial)) {
			$dial->number($phoneNumberToDial);
		}
		else {
			$dial->client('support_agent');
		}

		Storage::put('response.txt', $response );


		return $response;
	}
}
