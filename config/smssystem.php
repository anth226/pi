<?php
if(config('app.env') == 'local'){
	return [
		'url' => env( 'SMS_SYSTEM_TEST' ) ?: ''
	];
}
else {
	return [
		'url' => env( 'SMS_SYSTEM_PROD' ) ?: ''
	];
}