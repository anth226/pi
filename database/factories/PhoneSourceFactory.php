<?php

use Faker\Generator as Faker;

use App\PhoneSource;
use Illuminate\Support\Str;
use App\KmClasses\Sms\FormatUsPhoneNumber;

$factory->define(PhoneSource::class, function (Faker $faker) {
	$phone = $faker->phoneNumber;
	$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($phone);

	return [
		'source' => $faker->unique()->word,
		'phone' => $phone,
		'formated_phone' => $formated_phone_number,
		'total_leads' => $faker->numberBetween(0,500)
	];
});
