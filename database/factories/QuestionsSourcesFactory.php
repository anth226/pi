<?php

use Faker\Generator as Faker;
use App\QuestionsSources;

$factory->define(QuestionsSources::class, function (Faker $faker) {
    return [
	    'source_id' => 0,
	    'question_id' => 0
    ];
});
