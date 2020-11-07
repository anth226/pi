<?php

use Faker\Generator as Faker;

use App\Automations;
use Illuminate\Support\Str;

$factory->define(
	Automations::class,
	function (Faker $faker){

			$sources_included = [];
			$sources_excluded = [];
			$sources_qa = [];

			$sources = json_encode([
				'sources_qa' => $sources_qa,
				'sources_excluded' => $sources_excluded,
				'sources_included' => $sources_included,
			]);


		    return [
			    'message' => $faker->realText(100),
			    'percentage' => 10,
			    'title' => 'Automation Test '.$faker->numberBetween(0,50000),
			    'sources' => $sources,
			    'delay' => 0,
			    'message2' => ''
		    ];
});

$factory->state(Automations::class, 'included', function() {
	$phone_sources = \App\PhoneSource::limit(2)->pluck('id')->toArray();

	$sources_included = array_unique($phone_sources);
	$sources_excluded = [];
	$sources_qa = [];

	$sources = json_encode([
		'sources_qa' => $sources_qa,
		'sources_excluded' => $sources_excluded,
		'sources_included' => $sources_included,
	]);

	return [
		'sources' => $sources,
	];
});

$factory->state(Automations::class, 'qa', function() {
	$ps = \App\FormsAnswers::select('forms_answers.id', 'questions_sources.source_id')
										->leftJoin( 'questions_sources', function ( $join ) {
											$join->on( 'forms_answers.qs_id', 'questions_sources.id' );
										} )
										->limit(2)
										->get()
	;
	$phone_sources = [];
	$qa = [];
	if($ps && count($ps)){
		foreach($ps as $v){
			$phone_sources[] = $v->source_id;
			$qa[] = $v->id;
		}

	}

	$sources_included = array_unique($phone_sources);
	$sources_excluded = [];
	$sources_qa = array_unique($qa);

	$sources = json_encode([
		'sources_qa' => $sources_qa,
		'sources_excluded' => $sources_excluded,
		'sources_included' => $sources_included,
	]);

	return [
		'sources' => $sources,
	];
});

$factory->state(Automations::class, 'excluded', function() {
	$phone_sources = \App\PhoneSource::limit(2)->pluck('id')->toArray();

	$sources_included = [];
	$sources_excluded = array_unique($phone_sources);
	$sources_qa = [];

	$sources = json_encode([
		'sources_qa' => $sources_qa,
		'sources_excluded' => $sources_excluded,
		'sources_included' => $sources_included,
	]);

	return [
		'sources' => $sources,
	];
});


