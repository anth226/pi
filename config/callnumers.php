<?php
$ph = false;
$def_forward = false;
if(!empty(env('CALLNUMBERS'))) {
	$ph = array_unique(explode( ',', env( 'CALLNUMBERS' ) ));
	if(!empty(env('DEFAULT_FORWARD_NUMBER'))) {
		$def_forward = trim( env('DEFAULT_FORWARD_NUMBER') );
	}
}
return [
	'phone' => [
		'calling_numbers' => $ph,
		'default_forward_number' => $def_forward
	]
];