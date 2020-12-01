<?php
if(config('app.env') == 'local'){
	return [
		'basePath' => env( 'KLAVIYO_TEST_BASEPATH' ) ?: '',
		'apiKey'   => env( 'KLAVIYO_TEST_API_KEY' ) ?: '',
		'listId'   => env( 'KLAVIYO_TEST_LIST_ID' ) ?: ''
	];
}
else {
	return [
		'basePath' => env( 'KLAVIYO_PROD_BASEPATH' ) ?: '',
		'apiKey'   => env( 'KLAVIYO_PROD_API_KEY' ) ?: '',
		'listId'   => env( 'KLAVIYO_PROD_LIST_ID' ) ?: ''
	];
}