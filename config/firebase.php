<?php
if(config('app.env') == 'local'){
	return [
		'databaseURL'                 => env( 'FIREBASE_TEST_DATABASE_URL' ) ?: '',
		'type'                        => env( 'FIREBASE_TEST_TYPE' ) ?: '',
		'project_id'                  => env( 'FIREBASE_TEST_PROJECT_ID' ) ?: '',
		'private_key_id'              => env( 'FIREBASE_TEST_PRIVATE_KEY_ID' ) ?: '',
		'private_key'                 => preg_replace('/\\n/', '\n', env( 'FIREBASE_TEST_PRIVATE_KEY' )) ?: '',
		'client_email'                => env( 'FIREBASE_TEST_CLIENT_EMAIL' ) ?: '',
		'client_id'                   => env( 'FIREBASE_TEST_CLIENT_ID' ) ?: '',
		'auth_uri'                    => env( 'FIREBASE_TEST_AUTH_URI' ) ?: '',
		'token_uri'                   => env( 'FIREBASE_TEST_TOKEN_URI' ) ?: '',
		'auth_provider_x509_cert_url' => env( 'FIREBASE_TEST_AUTH_PROVIDER_X509_CERT_URL' ) ?: '',
		'client_x509_cert_url'        => env( 'FIREBASE_TEST_CLIENT_X509_CERT_URL' ) ?: ''
	];
}
else {
	return [
		'databaseURL'                 => env( 'FIREBASE_PROD_DATABASE_URL' ) ?: '',
		'type'                        => env( 'FIREBASE_PROD_TYPE' ) ?: '',
		'project_id'                  => env( 'FIREBASE_PROD_PROJECT_ID' ) ?: '',
		'private_key_id'              => env( 'FIREBASE_PROD_PRIVATE_KEY_ID' ) ?: '',
		'private_key'                 => preg_replace('/\\n/', '\n', env( 'FIREBASE_PROD_PRIVATE_KEY' )) ?: '',
		'client_email'                => env( 'FIREBASE_PROD_CLIENT_EMAIL' ) ?: '',
		'client_id'                   => env( 'FIREBASE_PROD_CLIENT_ID' ) ?: '',
		'auth_uri'                    => env( 'FIREBASE_PROD_AUTH_URI' ) ?: '',
		'token_uri'                   => env( 'FIREBASE_PROD_TOKEN_URI' ) ?: '',
		'auth_provider_x509_cert_url' => env( 'FIREBASE_PROD_AUTH_PROVIDER_X509_CERT_URL' ) ?: '',
		'client_x509_cert_url'        => env( 'FIREBASE_PROD_CLIENT_X509_CERT_URL' ) ?: ''
	];
}