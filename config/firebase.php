<?php
if(config('app.env') == 'local'){
	return  [
		'file_name' => env( 'FIREBASE_JSON_TEST_FILE' ) ?: ''
	];
}
else {
	return  [
		'file_name' => env( 'FIREBASE_JSON_PROD_FILE' ) ?: ''
	];
}