<?php
$ex = false;
if(!empty(env('EXCLUSIONS'))) {
	$ex = array_unique(explode( ',', env( 'EXCLUSIONS' ) ));
}
return [
	'area' => [
		'code' => $ex
	]
];