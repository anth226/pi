<?php

use Faker\Generator as Faker;
use App\LeadsData;
use App\Leads;
use App\KmClasses\Sms\AreaCodes;

$factory->define(LeadsData::class, function (Faker $faker) {
    return [
	    'leadId' => 0,
	    'answer_id' => 0
    ];
});


$factory->state(LeadsData::class, 'createQA', function(Faker $faker) {
	$leads = factory('App\Leads', 4)->states('source')->create();
	$data = [];
	if($leads && $leads->count()){
		//create dubs
		foreach ($leads as $l) {
			if ( empty( $source ) || empty( $source_id ) ) {
				$source    = $l->source;
				$source_id = $l->sourceId;
			}

			$l->source   = $source;
			$l->sourceId = $source_id;

			$phone_status = 1;
			$us_state     = '';
			$area_code    = 0;
			if ( empty( $l->formated_phone_number ) ) {
				$phone_status = 3;
			} else {
				$dub = Leads::where( 'formated_phone_number', $l->formated_phone_number )->first();
				if ( ! empty( $dub->id ) ) {
					$phone_status = 4;
					$area_code    = substr( $l->formated_phone_number, 2, 3 );
					if ( $area_code ) {
						$us_state = AreaCodes::code_to_state( $area_code );
					}
				}
			}


			$lead = factory( 'App\Leads', 1 )->create([
				'first_name'            => $l->first_name,
				'last_name'             => $l->last_name,
				'full_name'             => $l->first_name . ' ' . $l->last_name,
				'email'                 => $l->email,
				'phone'                 => $l->phone,
				'formated_phone_number' => $l->formated_phone_number,
				'source'                => $source,
				'sourceId'              => $source_id,
				'area_code'             => $area_code,
				'us_state'              => $us_state,
				'phone_status'          => $phone_status
			]);

			$q = factory('App\FormsQuestions', 1)->create();

			$qs = factory('App\QuestionsSources', 1)->create([
				'source_id' => $source_id,
				'question_id' => $q[0]->id
			]);
			$a = factory('App\FormsAnswers', 1)->create([
				'question_id' => $q[0]->id,
				'answer' => $faker->word,
				'qs_id' => $qs[0]->id
			]);

			factory('App\LeadsData',1)->create([
				'leadId' => $lead[0]->id,
				'answer_id' => $a[0]->id
			]);


		}
	}
	return $data;
});