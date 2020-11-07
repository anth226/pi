<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\KmClasses\Sms\AreaCodes;
use App\Leads;

use App\Console\Commands\ProcessingAutomations;

class ProcessAutomationTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
//	    $this->seedLeedsAndSources();
//	    $this->seedQA();
//	    $this->seedAutomations();
//	    $processing = new ProcessingAutomations();
//		$processing->handle();
		//$this->assertEquals('test');
    }

    protected function seedLeedsAndSources(){
	    $leads = factory('App\Leads', 4)->states('source')->create();
	    if($leads && $leads->count()) {
	    	foreach($leads as $l) {
			    $lead = factory( 'App\Leads', 4 )->create( [
				    'source'   => $l->source,
				    'sourceId' => $l->sourceId
			    ] );
			    //create dubs
			    if(empty($source) || empty($source_id) ){
			    	$source = $l->source;
			    	$source_id = $l->sourceId;
			    }

			    $lead->source = $source;
			    $lead->sourceId = $source_id;
			    $all_leads = $lead->toArray();

			    $phone_status = 1;
			    $us_state = '';
			    $area_code = 0;
			    if(empty($all_leads[0]['formated_phone_number'])){
				    $phone_status = 3;
			    }
			    else{
				    $dub = Leads::where( 'formated_phone_number', $all_leads[0]['formated_phone_number'] )->first();
				    if(!empty($dub->id)){
					    $phone_status = 4;
					    $area_code = substr ($all_leads[0]['formated_phone_number'], 2 ,3);
					    if($area_code){
						    $us_state = AreaCodes::code_to_state($area_code);
					    }
				    }
			    }

			    factory( 'App\Leads', 1 )->create([
				    'first_name' => $all_leads[0]['first_name'],
				    'last_name' => $all_leads[0]['last_name'],
				    'full_name' => $all_leads[0]['first_name'].' '.$all_leads[0]['last_name'],
				    'email' => $all_leads[0]['email'],
				    'phone' => $all_leads[0]['phone'],
				    'formated_phone_number' => $all_leads[0]['formated_phone_number'],
				    'source' => $source,
				    'sourceId' => $source_id,
				    'area_code' => $area_code,
				    'us_state' => $us_state,
				    'phone_status' => $phone_status
			    ]);
		    }
	    }
    }

    protected function seedQA(){
	    factory('App\LeadsData',1)->states('createQA')->create();
    }

    protected function seedAutomations(){
	    factory('App\Automations', 1)->states('included')->create();
	    factory('App\Automations', 1)->states('excluded')->create();
	    factory('App\Automations', 1)->states('qa')->create();
	    factory('App\Automations', 1)->states('included')->create(['delay' => 600]);
	    factory('App\Automations', 1)->states('excluded')->create(['delay' => 600]);
	    factory('App\Automations', 1)->states('qa')->create(['delay' => 600]);
	    factory('App\Automations', 1)->states('included')->create(['a_type' => 1]);
	    factory('App\Automations', 1)->states('excluded')->create(['a_type' => 1]);
	    factory('App\Automations', 1)->states('qa')->create(['a_type' => 1]);
	    factory('App\Automations', 1)->states('included')->create(['delay' => 600,'a_type' => 1]);
	    factory('App\Automations', 1)->states('excluded')->create(['delay' => 600,'a_type' => 1]);
	    factory('App\Automations', 1)->states('qa')->create(['delay' => 600,'a_type' => 1]);
    }
}
