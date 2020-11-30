<?php
if(App::environment('local')){
	return [
		'stripeKey'      => env( 'STRIPE_TEST_API_SECRET_KEY' ) ?: '',
		'endpointSecret' => env( 'STRIPE_TEST_ENDPOINT_SECRET' ) ?: '',
		'price'          => env( 'STRIPE_TEST_PRICE_ID' ) ?: '',
		'coupon'         => env( 'STRIPE_TEST_COUPON_ID_PRIME' ) ?: '',
	];
}
else {
	return [
		'stripeKey'      => env( 'STRIPE_PROD_API_SECRET_KEY' ) ?: '',
		'endpointSecret' => env( 'STRIPE_PROD_ENDPOINT_SECRET' ) ?: '',
		'price'          => env( 'STRIPE_PROD_PRICE_ID' ) ?: '',
		'coupon'         => env( 'STRIPE_PROD_COUPON_ID_PRIME' ) ?: '',
	];
}