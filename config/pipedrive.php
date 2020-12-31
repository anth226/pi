<?php
if(config('app.env') == 'local'){
	return [
		'api_key' => env( 'PIPEDRIVE_API_TEST_KEY' ) ?: ''
	];
}
else {
	return [
		'api_key' => env( 'PIPEDRIVE_API_PROD_KEY' ) ?: ''
	];
}