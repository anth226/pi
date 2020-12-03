<?php
if(config('app.env') == 'local'){
	return  [
		'file_name' => env( 'FIREBASE_JSON_TEST_FILE' ) ?: '',
		'api_key' => env( 'FIREBASE_TEST_API_KEY' ) ?: '',
		'project_id' => env( 'FIREBASE_TEST_API_KEY' ) ?: '',
	];
}
else {
	return  [
		'file_name' => env( 'FIREBASE_JSON_PROD_FILE' ) ?: '',
		'api_key' => env( 'FIREBASE_PROD_API_KEY' ) ?: '',
	];
}