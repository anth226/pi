<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\Grants\VoiceGrant;

class TwilioTokenController extends Controller
{
	public $accessToken, $applicationSid ;

	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:salespeople-reports-view-all');
		$this->accessToken = new AccessToken(config('twilio.twilio.twilio_sid'), config('twilio.twilio.twilio_key'), config('twilio.twilio.twilio_secret'));
		$this->applicationSid = config('twilio.twilio.twilio_app_sid');
	}

	/**
	 * Create a new capability token
	 *
	 * @return \Illuminate\Http\Response
	 */

	public function newToken(Request $request)
	{
		$this->accessToken->setIdentity('support_agent');

		// Create Voice grant
		$voiceGrant = new VoiceGrant();
		$voiceGrant->setOutgoingApplicationSid($this->applicationSid);

		// Optional: add to allow incoming calls
		$voiceGrant->setIncomingAllow(true);

		// Add grant to token
		$this->accessToken->addGrant($voiceGrant);

		// render token to string
		$token = $this->accessToken->toJWT();

		return response()->json(['token' => $token]);
	}
}
