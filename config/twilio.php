<?php

return [
	'twilio' => [
		'twilio_sid' => env('TWILIO_SID') ?: '',
		'twilio_token' => env('TWILIO_TOKEN') ?: '',
		'twilio_from' => env('TWILIO_FROM') ?: '',
	],
];