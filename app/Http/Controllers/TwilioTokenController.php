<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\ClientToken;
use Twilio\Jwt\Grants\VoiceGrant;

class TwilioTokenController extends Controller
{
	public $clientToken ;
	public $accessToken ;

	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:salespeople-reports-view-all');
		$this->clientToken = new ClientToken(config('twilio.twilio.twilio_sid'), config('twilio.twilio.twilio_token'));
//		$this->accessToken = new AccessToken(config('twilio.twilio.twilio_sid'), config('twilio.twilio.twilio_key'), config('twilio.twilio.twilio_secret'));

	}

	/**
	 * Create a new capability token
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function newToken(Request $request)
	{
		$forPage = $request->input('forPage');

		if ($forPage === route('test-call', [], false)) {
			$this->clientToken->allowClientIncoming('support_agent');
		} else {
			$this->clientToken->allowClientIncoming('customer');
		}

		$token = $this->clientToken->generateToken();
		return response()->json(['token' => $token]);
	}

	public function newToken_v2(Request $request)
	{
		$forPage = $request->input('forPage');
		$accountSid = config('twilio.twilio.twilio_sid');
		$applicationSid = config('twilio.twilio.twilio_app_sid');
		$apiKey = config('twilio.twilio.twilio_key');
		$apiSecret = config('twilio.twilio.twilio_secret');

		if ($forPage === route('dashboard', [], false)) {
			$this->accessToken->setIdentity('support_agent');
		} else {
			$this->accessToken->setIdentity('customer');
		}

		// Create Voice grant
		$voiceGrant = new VoiceGrant();
		$voiceGrant->setOutgoingApplicationSid($applicationSid);

		// Optional: add to allow incoming calls
		$voiceGrant->setIncomingAllow(true);

		// Add grant to token
		$this->accessToken->addGrant($voiceGrant);

		// render token to string
		$token = $this->accessToken->toJWT();

		return response()->json(['token' => $token]);
	}
}
