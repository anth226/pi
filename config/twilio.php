<?php

return [
	'twilio' => [
		'twilio_sid' => env('TWILIO_SID') ?: '',
		'twilio_token' => env('TWILIO_TOKEN') ?: '',
		'twilio_from' => env('TWILIO_FROM') ?: '',
		'twilio_key' => env('TWILIO_API_KEY') ?: '',
		'twilio_secret' => env('TWILIO_API_SECRET') ?: '',
		'twilio_app_sid' => env('TWILIO_APP_SID') ?: '',
	],
];