<?php

use Faker\Generator as Faker;
use App\FormsQuestions;

$factory->define(FormsQuestions::class, function (Faker $faker) {
    return [
	    'question' => $faker->realText(100)
    ];
});
