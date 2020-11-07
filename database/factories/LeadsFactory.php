<?php

use Faker\Generator as Faker;

use App\Leads;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\AreaCodes;

$factory->define(Leads::class, function (Faker $faker) {
	$phone = $faker->phoneNumber;
	$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($phone);
	$area_code = substr ($formated_phone_number, 2 ,3);
	$us_state = '';
	if($area_code){
		$us_state = AreaCodes::code_to_state($area_code);
	}
	else{
		$area_code = 0;
	}

	$phone_status = 1;
	if(empty($formated_phone_number)){
		$phone_status = 3;
	}
	else{
		$dub = Leads::where( 'formated_phone_number', $formated_phone_number )->first();
		if(!empty($dub->id)){
			$phone_status = 4;
		}
	}

	$first_name = $faker->firstName;
	$last_name = $faker->lastName;
	return [
		'first_name' => $first_name,
		'last_name' => $last_name,
		'full_name' => $first_name.' '.$last_name,
		'email' => $faker->safeEmail,
		'phone' => $phone,
		'formated_phone_number' => $formated_phone_number,
		'phone_status' => $phone_status,
		'area_code' => $area_code,
		'us_state' => $us_state,
		'source' => 'test',
		'sourceId' => 0
	];
});


$factory->state(leads::class, 'source', function() {
	$source = factory(App\PhoneSource::class)->create();
	$data = [];
	if($source && $source->count()){
		$data = [
			'source' => $source->source,
			'sourceId' => $source->id
		];
	}
	return $data;
});
