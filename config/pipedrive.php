<?php
if(config('app.env') == 'local'){
	return [
		'api_key' => env( 'PIPEDRIVE_API_TEST_KEY' ) ?: '',
		'source_field_id' => '0416c433c4366702814fd1546d42f1ead5c103a2',
		'timezone_field_id' => 'fd9a52bdbc306ff7ef583cf9596bfef322565250',
		'extra_field_id' => '385b6276b3f1c7027b45139a7ae90823a5aa9174'
	];
}
else {
	return [
		'api_key' => env( 'PIPEDRIVE_API_PROD_KEY' ) ?: '',
		'source_field_id' => '0d42d585b2f6407cd384cd02838de179c0a1527d',
		'timezone_field_id' => '0733183570c5c4a996b459ea05ab6f19c9ee2f72',
		'extra_field_id' => '012fe2582b1a93009814bdd11aa6a630622eb209'
	];
}