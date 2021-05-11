<?php
if(config('app.env') == 'local'){
	return [
		'stripeKey'      => env( 'STRIPE_TEST_API_SECRET_KEY' ) ?: '',
		'endpointSecret' => env( 'STRIPE_TEST_ENDPOINT_SECRET' ) ?: '',
		'price'          => env( 'STRIPE_TEST_PRICE_ID' ) ?: '',
		'coupon'         => env( 'STRIPE_TEST_COUPON_ID_PRIME' ) ?: '',
		'webhook_secret' => env( 'STRIPE_TEST_WEBHOOK_SECRET' ) ?: '',
	];
}
else {
	return [
		'stripeKey'      => env( 'STRIPE_PROD_API_SECRET_KEY' ) ?: '',
		'endpointSecret' => env( 'STRIPE_PROD_ENDPOINT_SECRET' ) ?: '',
		'price'          => env( 'STRIPE_PROD_PRICE_ID' ) ?: '',
		'coupon'         => env( 'STRIPE_PROD_COUPON_ID_PRIME' ) ?: '',
		'webhook_secret' => env( 'STRIPE_PROD_WEBHOOK_SECRET' ) ?: '',
	];
}