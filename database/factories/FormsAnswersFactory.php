<?php

use Faker\Generator as Faker;
use App\FormsAnswers;

$factory->define(FormsAnswers::class, function (Faker $faker) {
    return [
	    'question_id' => 0,
	    'answer' => '',
	    'qs_id' => 0
    ];
});
