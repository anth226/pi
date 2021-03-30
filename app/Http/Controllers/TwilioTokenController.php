<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\Grants\VoiceGrant;

class TwilioTokenController extends Controller
{
	public $clientToken ;

	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:salespeople-reports-view-all');
		$this->clientToken = new ClientToken(config('services.twilio.twilio_sid'), config('services.twilio.twilio_token'));
//		$this->clientToken = new AccessToken(config('services.twilio.twilio_sid'), config('services.twilio.twilio_key'), config('services.twilio.twilio_secret'));

	}

	/**
	 * Create a new capability token
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function newToken(Request $request)
	{
		$forPage = $request->input('forPage');

		$applicationSid = config('services.twilio.twilio_app_sid');

		if ($forPage === route('test-call', [], false)) {
			$this->clientToken->allowClientIncoming('support_agent');
		} else {
			$this->clientToken->allowClientIncoming('customer');
		}

		$token = $this->clientToken->generateToken();
		return response()->json(['token' => $token]);
	}
}
