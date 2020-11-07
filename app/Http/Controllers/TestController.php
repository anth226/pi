<?php

namespace App\Http\Controllers;

use App\Console\Commands\ProcessingCallCampaigns;
use App\Console\Commands\ProcessingEmailCampaigns;
use App\Console\Commands\ProcessingShopify;
use App\Console\Commands\ProcessingSMSCampaigns;
use App\EmailTags;
use App\Exeptions;
use App\Http\Controllers\API\LeadsShopifyController;
use App\KmClasses\Curl\Shopify;
use App\KmClasses\MailEclipse\mailEclipse;
use App\Projects;
use App\ProjectsEmails;
use App\Tags;
use App\Uleads;
use App\Unsubscribed;

use Illuminate\Http\Request;

use Validator,Redirect,Response;
use App\Leads;
use App\PhoneCallsLog;
use App\PhoneSource;
use App\Logs;
use App\AutomationLogs;
use App\Automations;
use App\CampaignsReports;
use App\KmClasses\Sms\SmsSender;
use App\CallsDurations;
use App\FormsAnswers;
use App\FormsQuestions;
use App\QuestionsSources;
use App\LeadsData;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Mail;

use App\Console\Commands\ProcessingCampaigns;
use App\Console\Commands\ProcessingAutomations;
use App\Console\Commands\ProcessingAutomationsReports;
use App\Console\Commands\ProcessingUnsubscribedReports;
use App\Console\Commands\ProcessingImports;
use App\Console\Commands\ProcessingDubs;
use App\Console\Commands\ProcessingEmpty;
use App\Console\Commands\ProcessingInfo;
use App\Console\Commands\ProcessingLeadsLog;
use App\Console\Commands\ProcessingCleanup;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Mailgun\Mailgun;
use Exception;
use Aws\SesV2\SesV2Client;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;



class TestController extends Controller
{
	public function __construct()
	{
		$this->middleware(['auth','verified','approved']);
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */

	public function index()
	{
//		$this->getUnsubscribeds();
//		$ps = new ProcessingShopify();
//		$project = Projects::where('id', 2)->first();
//		$ps->getItemsByParts($project,'checkouts', 13042759139401);

//		$t = new ProcessingShopify();
//		$res = $t->handle();
//		dd($res);

//		$this->testTime();
//		$t = new LeadsShopifyController();
//		$res = $t->getTagsFromShopify('4375676321875', 'clickmart');
//		dd($res);
//		$t = new ProcessingImports();
//		$t->handle();
//		$t = EmailTags::getSourcesUniqueLeadsLimited([36],1, 0, 0, 10);
//		echo "<pre>";
//		var_export($t->toSql());
//		echo "</pre>";
//		$test =  new ProcessingImports();
//		$test->processingAllUnsubscribed();
//		$test->processingNames();
//		$test->fixedEmails();
//		$this->xare();
//		$this->getQueue();
//		$this->stopQueue();
//		$this->sendTestMail();
//		$this->fixDB2();
//		$t = new ProcessingCampaigns();
//		$t->handle();
//		$this->sendTestEmail();
//		$this->awsTest();
//		$this->addTagId();
//		$t = new ProcessingAutomations();
//		$t->handle();
//		$t = new ProcessingEmailCampaigns();
//		$t->handle();


//		$t = new ProcessingSMSCampaigns();
//		$t = new ProcessingCallCampaigns();
//		$t->handle();

//		$e = new mailEclipse();
//		$e->import();
	}

	public function addTagId(){
		$res = QuestionsSources::select('questions_sources.id','phone_sources.source')
								->leftJoin( 'phone_sources', function ( $join ) {
										$join->on( 'phone_sources.id','questions_sources.source_id' );
									} )
								->get()
		;
		if($res){
			foreach ($res as $r){
				$tag_id = Tags::where('tag', $r->source)->value('id');
				if($tag_id) {
					QuestionsSources::where( 'id', $r->id )->update( [ 'tag_id' => $tag_id ] );
				}
			}
		}
	}

	public function awsTest(){
		$ses = new SesV2Client([
			'version' => 'latest',
			'region'  => 'us-east-1',
			'credentials' => [
				'secret' => 'gz8wAd5ECz2nYZIUU6BDDzOdZwwpLRgSiE4SD3Pd',
				'key' =>'AKIAJNFDOLKLQGLQVAOQ'
			]
		]);
		$client = new SesClient(
			[
				'version' => 'latest',
				'region'  => 'us-east-1',
				'credentials' => [
					'secret' => '2YsYWq6sS4BPsN2N21PO8Ivn7kJJb5WOjbhnlnnY',
					'key' =>'AKIA3WMZNRMP24X7BLRH'
				]
			]
		);
		try {
			$result = $client->getSendStatistics([
			]);

			$r = json_decode($result, 1);
			echo "<pre>";
			var_export($result);
			echo "</pre>";
//			var_dump($result);
		} catch (AwsException $e) {
			// output error message if fails
			echo $e->getMessage();
			echo "\n";
		}


	}

	public function sendTestEmail(){
		$template ='vendor.maileclipse.templates.sfhsfhs';
		$data = array();
		$data['name'] = '';
		$data['pixel'] = '';
		$data['unsubscribe_url'] = '';
		try {
			Mail::send( $template, $data, function ( $message ) {
				$message->to( 'complaint@simulator.amazonses.com' )->subject('test555' );
//				$message->to( 'bounce@simulator.amazonses.com' )->subject('test555' );
//				$message->to( 'suppressionlist@simulator.amazonses.com' )->subject('test555' );
//				$message->to( 'success@simulator.amazonses.com' )->subject('test555' );
//				$message->to( 'ooto@simulator.amazonses.com' )->subject('test555' );
//				$message->to( 'markareno@gmail.com' )->subject('test' );
				$message->from('support@magicstarsystem.com', 'Magicstar' );
				$swiftMessage = $message->getSwiftMessage();
				$headers = $swiftMessage->getHeaders();
				$headers->addTextHeader('msa-c', 2);
				$headers->addTextHeader('msa-e', 1103382);
				$headers->addTextHeader('msa-p', 1);
			} );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			echo $err;
			return false;
		}
	}

	public function fixDB2(){
		$c = array();
		$res =  Uleads::leftJoin('leads',function ( $join ){
							$join->on( 'uleads.formated_phone_number', 'leads.formated_phone_number' );
						} )
						->whereNull('leads.id')
						->pluck('uleads.id')
			;
		if($res && $res->count()){
			foreach($res as $r){
				if($r) {
					$c[] = Uleads::where('id', $r)->delete();
				}
			}
		}

		echo "<pre>";
		var_export(count($c));
		echo "</pre>";
		echo "<pre>";
		var_export($res->toArray());
		echo "</pre>";
	}

	public function fixDB(){
		//SELECT formated_phone_number, count(*) as c FROM sms.leads where leads.phone_status = 0 group by formated_phone_number having c > 1 ;
		$rrr = array();
		$res  = Leads::select('formated_phone_number', DB::raw("COUNT(*) c") )
			->where('phone_status', 0)
            ->groupBy('formated_phone_number')
			->havingRaw('c > 1')
			->get();
		if($res){
			foreach($res as $r){
				if($r->formated_phone_number) {
					$leads = Leads::where('formated_phone_number', $r->formated_phone_number)->orderBy('id')->get();
					if($leads){
						foreach($leads  as $i => $l){
							if(($i != 0) && $l->id){
								$result = Leads::where('id', $l->id)->update(['phone_status' => 4]);
								$rrr[] = $l->formated_phone_number;
							}
						}
					}
				}
			}
		}

	}

	public  function xare(){
//		$processing = new ProcessingCampaigns();
//		$processing->handle();

//		$processing = new ProcessingAutomations();
//		$processing->handle();

//		$processing = new ProcessingCleanup();
//		$processing->handle();

//		$processing = new ProcessingUnsubscribedReports();
//		$processing->handle();
//
//	    $processing = new ProcessingAutomationsReports();
//		$processing->handle();

//	    $processing = new ProcessingLeadsLog();
//		$processing->handle();
//
//		$processing = new ProcessingNumbers();
//		$processing->handle();

//		$processing = new ProcessingImports();
//		$processing->addSourceId();
//		$processing->maxDuration();
//		$processing->handle();
//
//		$processing = new ProcessingDubs();
//		$processing->handle();

//		$processing = new ProcessingEmpty();
//		$processing->handle();
//		$processing->getCampaignInfo();

//		$processing = new ProcessingInfo();
//		$processing->handle();
//


//		$processing = new ProcessingCallsReports();
//		$processing->handle();


		//$campaigns = Campaigns::getCampaignSegment()->orderBy('id','desc')->paginate(10);
		//$campaigns = Campaigns::orderBy('status', 'desc')->orderBy('scheduled_at','asc')->orderBy('started_at','desc')->paginate(10);

//		$sender = new SmsSender();
//		$sender->testCall('+16262603849','+17863453916');
//		$this->testCall('+18184507532','+17863453916');
//		dd($sender->if_completed('+14054015750'));
//		dd($sender->if_completed('+18184507532'));
//		$sender -> sendCall('+8184507532', $leadid = 0, $campaignId= 0, $campaign_type = 0, $source = '', $amd = 0, $amd_timeout = 5000 );
//		dd($sender->getFromNumber(2));
//		$sender->testCall('+18184507532','+12404363732');
//		$sender->welcomeCall(1,2);
//		$sender->sendCall('+18184507532', '+13236760207');

//		$sender->sendSMS('+12100000000', 'test', $leadid = 0, $campaignId = 0, $campaign_type = 0, $formated = true, $mediaUrl = false);

//		$sender->checkDNC('+15414906035', 11 , 1, 11111);
//		dd($sender->errorProcessing(21610,'+16186238106',11,1,5034));
//		$sender->getCampaignByPhone('+17068929489');
//		$sender->unsubscribeNumber('+17068929489');

//		$campaignId = 0;
//		$campaign_type = 0;
//		$res = $this->getCampaignByPhone('+16097511085');
//		if($res && $res->campaignId){
//			$campaignId = $res->campaignId;
//			$campaign_type = !empty($res->campaign_type) ? $res->campaign_type : 0;
//		}
//		dd($campaignId.', '.$campaign_type);

//		CallsDurations::updateMaxDuration(11111, 60);
//		$call_numbers = config('callnumers.phone.calling_numbers');
//		echo "Numbers count: ".count($call_numbers);
//		dd($call_numbers);

//		$form_data =  [
//                ['q' => 'question 1', 'a' => 'answer 1'],
//                ['q' => 'question 2', 'a' => 'answer 2']
//            ];
//
//		$this->saveFormData($form_data);

//		dd(PhoneSource::addSource('hhhhhh'));
//		$this->getQueue();

	}

	public function testCall($to, $from){

		try {
			$account_sid = config('twilio.twilio.twilio_sid');
			$auth_token = config('twilio.twilio.twilio_token');
			$client = new Client( $account_sid, $auth_token );

			$url = 'http://134.209.116.138/api/calltochoice?leadid=0&cid=947&ctype=1&callerId='.$to.'&source=test';
			$statusCallback = 'http://134.209.116.138/api/callankapkryukhaha?leadid=0&cid=947&ctype=1';
			$data = array(
				"url" => $url,
				"statusCallback" => $statusCallback,
				"statusCallbackEvent" => array("completed","answered")
			);

			$res = $client->calls->create(
				$to, //to
				$from, //from
				$data
			);
			if ( $res ) {
				echo "<pre>";
				print($res);
				echo "</pre>";
			}
		} catch ( TwilioException $exception ) {
			if ( ! empty( $exception->getCode() ) ) {
				echo "<pre>";
				print($exception->getCode());
				echo "</pre>";

			}
		}

		return true;
	}


	public function getCalls($phone){
	    $account_sid = config('twilio.twilio.twilio_sid');
	    $auth_token = config('twilio.twilio.twilio_token');
	    $client = new Client( $account_sid, $auth_token );
	    $calls = $client->calls
		    ->read( array( "to" => $phone ), 100 );
		$res = [];
	    foreach ( $calls as $i => $message ) {
	    	$res[] = $this->createDateTimelocalString( $message->dateCreated );
	    }
	    dd($res);
    }

	public function updateDelays(){
		$automations = Automations::where('delay','>',0)->get();
		if($automations && $automations->count()) {
			foreach ( $automations as $a ) {
				Automations::findOrFail( $a->id )->update( ['delay' => ($a->delay)*24*60*60] );
			}
		}
	}

	public function stopAllDelayedAutomations(){
		$automations = Automations::where('delay','>',0)->get();
		if($automations && $automations->count()) {
			foreach ( $automations as $a ) {
				Automations::findOrFail( $a->id )->update( ['status' => 0] );
			}
		}
	}

	public function startAllDelayedAutomations(){
		$automations = Automations::where('delay','>',0)->get();
		if($automations && $automations->count()) {
			foreach ( $automations as $a ) {
				Automations::findOrFail( $a->id )->update( ['status' => 1] );
			}
		}
	}

	public function getQueue(){
		try {
			$data = array(
				//"startTime" => new \DateTime('2009-7-6'),
				"status" => "queued"
				//"status" => "completed"
			);

			$call_numbers = config('callnumers.phone.calling_numbers');
			//dd($call_numbers);

			$account_sid = config('twilio.twilio.twilio_sid');
			$auth_token = config('twilio.twilio.twilio_token');
			$client = new Client( $account_sid, $auth_token );
			$res = $client->calls->read(
				$data,
				2000
			);
			$result = array();
			if ( count($res) ) {
				foreach ($res as $r){
					$phone = $r->from;
					if(!isset($result[$phone])){
						$result[$phone] = 0;
					}
					$result[$phone] ++;
				}
				echo "Total: ".count($result);
				echo "<pre>";
				var_export($result);
				echo "</pre>";
			}
			else{
				return false;
			}
		} catch ( TwilioException $exception ) {
			$err = ! empty( $exception->getCode()) ? 'Error code: '.$exception->getCode(). ', ' : '';
			$err .= ! empty( $exception->getMessage()) ? 'Error code: '.$exception->getMessage() : '';
			Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'if_queued'] );
			return true;
		}
	}


	protected function updateQAData(){
		$qas = LeadsData::getQAs()->orderBy('leads.id','desc')->get();
//		dd($qas->toArray());
		if($qas && $qas->count()){
			foreach($qas as $qa) {
//				dd($qa->answer);
				$qs = QuestionsSources::addRecord( $qa->question_id, $qa->source_id );
//				dd($qs);
				if ( $qs && $qs->id ) {
					$a_res = FormsAnswers::findOrFail( $qa->answer_id )->update( ['qs_id' => $qs->id] );
//					dd($a_res);
				}
			}
		}
	}

	public function stopQueue(){
		try {
			$data = array(
				//"startTime" => new \DateTime('2009-7-6'),
				"status" => "queued"
				//"status" => "completed"
			);

			$call_numbers = config('callnumers.phone.calling_numbers');
			//dd($call_numbers);

			$account_sid = config('twilio.twilio.twilio_sid');
			$auth_token = config('twilio.twilio.twilio_token');
			$client = new Client( $account_sid, $auth_token );
			$res = $client->calls->read(
				$data,
				2000
			);
			$result = array();
			if ( count($res) ) {
				foreach ($res as $r){
					$sid = $r->sid;
					$status = $r->status;
					$result[$sid] = $status;
					$result[$sid] ++;
					echo '<div>'.$sid.':'.$status.'</div>';
					$call = $client->calls($sid)
//					               ->update(array("status" => "canceled"))
					               ->update(array("status" => "completed"))
						;
					echo '<div>'.$sid.':'.$call->status.'</div></hr>';
				}
				echo "Total: ".count($result);

			}
			else{
				return false;
			}
		} catch ( TwilioException $exception ) {
			$err = ! empty( $exception->getCode()) ? 'Error code: '.$exception->getCode(). ', ' : '';
			$err .= ! empty( $exception->getMessage()) ? 'Error code: '.$exception->getMessage() : '';
			Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'if_queued'] );
			return true;

		}
	}

	function testTime(){
		$timezone = config('app.timezone');
		$time1 = '2020-04-18T13:09:55-04:00';
		$t1 = $this->createDateTimelocalString2($time1);
		$t2 = new Carbon();
		$t2->timezone($timezone);
		dd($t2->diffInSeconds($t1));
	}


	protected function createDateTimelocalString2($datetime){
		$datetime = Carbon::parse($datetime);
		$timezone = config('app.timezone');
		$carbon = Carbon::instance($datetime);
		$carbon->timezone($timezone);
		return $carbon->toDateTimeString();
	}

	private function createDateTimelocalString($datetime){
		$timezone = config('app.timezone');
		$carbon = Carbon::instance($datetime);
		$carbon->timezone($timezone);
		return $carbon->toDateTimeString();
	}

	private function sendTestMail(){
		$key = config('mail.mailgun.key');
		$mg = Mailgun::create($key); // For US servers
		$domain = 'mg.magicstarsystem.com';
		$params = array(
			"event" => "accepted",
			"event" => "delivered",
			"event" => "failed",
			"event" => "complained"
		);
		$mg->messages()->send('mg.clickpod.com', [
			'from'    => 'support@clickpod.com',
			'to'      => 'kevin@magicstar.io',
			'subject' => 'Test',
			'html'    =>  '<h1 style="text-align: center">This is test</h1><p>This is unsubscribe <a href="%unsubscribe_url%">link</a>.</p>',
//			'template'    => 'test_template',
//			'h:X-Mailgun-Variables'    => '{
//											"content": "test test test",
//											"unsubscribe_url": "%unsubscribe_url%"
//											}'
		]);
		echo "<pre>";
		var_export($mg->stats()->total($domain, $params));
		echo "</pre>";
		echo "<pre>";
		var_export($mg->suppressions()->unsubscribes()->index($domain));
		echo "</pre>";
	}

	public function getItemsByParts($project, $type, $since_id = 0){
		ini_set( 'memory_limit', '8024M' );
		set_time_limit( 72000 );
		try{
			$source = $project->slug;
			$config  = array(
				'ShopUrl'  => $project->shopify_url,
				'ApiKey'   => $project->api_key,
				'Password' => $project->api_pass,
				'Token'    => $project->token,
				'PageSize' => 250,
				'Params'   => 'status=any&since_id='.$since_id.'&created_at_min=2019-10-01T00:00:00-00:00',
				'CountParams'   => 'status=any&since_id='.$since_id.'&created_at_min=2019-10-01T00:00:00-00:00'
			);
			$shopify = new Shopify( $config );
			if ( $shopify ) {
				$count = $shopify->getCount($type);
				if($count){
					$page_size = $shopify::$page_size;
					$pages_count = ceil($count/$page_size);
					for($j = 1; $j <= $pages_count; $j++){
						$shopify::$params = 'status=any&since_id='.$since_id.'&created_at_min=2019-10-01T00:00:00-00:00';
						$res = $shopify->getPart($type);
						echo "<pre>";
						var_export($j);
						echo "</pre>";
						dump($res);
						dump($since_id);
						if(!empty($res)){
							if(!empty($res['items'])){
								foreach ($res['items'] as $p ) {
									$product_id = '';
									$product_ids = [];
									$baby_tag = '';
									if(!empty($p->line_items) && count($p->line_items)){
										foreach($p->line_items as $i){
											if(!empty($i->product_id)){
												$product_ids[] = ($i->product_id) * 1;
											}
											if(!empty($i->title) && (strpos($i->title, 'BINKIPOD') !== false)){
												$baby_tag = ','.$source.'_baby';
											}
										}
									}
									if(!empty($product_ids) && count($product_ids)){
										$product_id = implode(',', $product_ids);
									}
									$email = !empty($p->email) ? $p->email : "";
									$first_name = (!empty($p->customer) && !empty($p->customer->first_name)) ? $p->customer->first_name : '';
									$last_name = (!empty($p->customer) && !empty($p->customer->last_name)) ? $p->customer->last_name : '';
									$phone = (!empty($p->customer) && !empty($p->customer->default_address) && !empty($p->customer->default_address->phone)) ? $p->customer->default_address->phone : '';
									$tag_for_type = '';
									if($type == 'orders'){
										$tag_for_type = $source.'_paidorder';
									}
									else{
										if($type == 'checkouts'){
											$tag_for_type = $source.'_abandoned';
										}
									}


									$input = [
										'product_id' => $product_id,
										'source' => $source,
//										'tags' => $source.','.$tag_for_type,
										'tags' => $source.','.$tag_for_type.','.$source.'_imported'.$baby_tag,
										'email' => $email,
										'first_name' => $first_name,
										'last_name'=> $last_name,
										'full_name'=> $first_name.' '.$last_name,
										'phone' => $phone
									];

									$leads = new LeadsShopifyController();
									$leads->saveLead($input);
								}
							}
							if(!empty($res['last_id'])){
								$since_id = $res['last_id'];
							}
						}
					}
				}
			}
		}
		catch ( Exception $ex ) {
			$err = $ex->getMessage();
			Exeptions::create( [ 'error'      => $err,
			                     'controller' => 'test',
			                     'function'   => 'getItemsByParts'
			] );
			return false;
		}
		return $since_id;
	}



	public function getUnsubscribeds(){
		ini_set('memory_limit', '8024M');
		set_time_limit(72000);
		$leads = Uleads::select('uleads.formated_phone_number')->join( 'unsubscribeds', function ( $join ){
			$join->on( 'unsubscribeds.formated_phone_number', 'uleads.formated_phone_number' );
		} )->pluck('uleads.formated_phone_number');

//		dd($leads->toArray());

		foreach($leads as $l){
			$input = [
				'Body' => 'stop',
				'From' => $l
			];

			$url = 'https://clickmart.magicstarsystem.com/api/vochankapkryukhaha';
			$postvars = http_build_query($input);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, count($input));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
			curl_exec($ch);
			curl_close($ch);
			usleep(500000);
			echo "<pre>";
			var_export($l);
			echo "</pre>";

		}
		echo "end";
	}

}
