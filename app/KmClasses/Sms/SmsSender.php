<?php
/**
 * Created by PhpStorm.
 * User: K
 * Date: 3/12/2019
 * Time: 1:49 PM
 */

namespace App\KmClasses\Sms;
use App\Campaigns;
use App\PhoneTags;
use App\Uleads;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
//use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\Logs;
use App\PhoneCallsLog;
use App\Leads;
use App\LeadsData;
use App\AutomationLogs;
use App\Automations;
use App\Unsubscribed;
use App\CampaignsReports;
use DB;
use App\PhoneSource;
use App\Exeptions;
use App\CallingNumbers;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;


class SmsSender {
	protected $account_sid, $auth_token, $twilio_number, $app_url, $client, $exclusions, $source_phone;
	public $default_forward_number, $call_numbers;

	public function __construct()
	{
		$this->account_sid = config('twilio.twilio.twilio_sid');
		$this->auth_token = config('twilio.twilio.twilio_token');
		$this->twilio_number = config('twilio.twilio.twilio_from');
		$this->app_url = config('app.url');
		$this->exclusions = config('exclusions.area.code');
		$this->default_forward_number = config('callnumers.phone.default_forward_number');
		$this->call_numbers = config('callnumers.phone.calling_numbers');
		$this->client = new Client( $this->account_sid, $this->auth_token );
		$this->source_phone = false;
	}

	public function sendSMS($phone, $message, $leadId = 0, $campaignId = 0, $campaign_type = 0, $formated = true, $mediaUrl = false){
		$result = array(
			'sid' => '',
			'error_code' => 0
		);
		$formated_phone = $phone;
		if(!$formated){
			$formated_phone = FormatUsPhoneNumber::formatPhoneNumber($phone);
		}
		if($this->account_sid && $this->auth_token && $this->twilio_number && $formated_phone && $message) {
			$dnc = $this->checkDNC($formated_phone, $campaignId, $campaign_type, $leadId);
			if(!$dnc) {
				if ( ! $this->exclusions || ! count( $this->exclusions ) || ! in_array( substr( $formated_phone, 2, 3 ), $this->exclusions ) ) {
					try {
						//$client = new Client( $this->account_sid, $this->auth_token );
						$data = array(
							'from'           => $this->twilio_number,
							'body'           => $message,
							'statusCallback' => $this->app_url . '/api/ankapkryukhaha?leadid=' . $leadId . '&cid=' . $campaignId . '&ctype=' . $campaign_type
						);
						if ( ! empty( $mediaUrl ) ) {
							$data['mediaUrl'] = $this->app_url . '/images/' . $mediaUrl;
						}
						$res = $this->client->messages->create(
							$formated_phone,
							$data
						);
						if ( $res ) {
							$result['sid'] = $res->sid;
						}
					} catch ( TwilioException $exception ) {
						if ( ! empty( $exception->getCode() ) ) {
							$result['error_code'] = $exception->getCode();
							// https://www.twilio.com/docs/api/errors
							$this->errorProcessing( $exception->getCode(), $formated_phone, $campaignId, $campaign_type, $leadId, false );
						}
					}
				} else {
					$result['error_code'] = 222222;
				}
			}
			else {
				$result['error_code'] = 333333;
			}
		}
		else {
			$result['error_code'] = 111111;
		}
		return $result;
	}

	public function testCall($to, $from){

				try {
					$url = $this->app_url . '/api/calltochoice?leadid=0&cid=0&ctype=1&callerId='.$to.'&source=test';
					$statusCallback = $this->app_url . '/api/callankapkryukhaha?leadid=0&cid=0&ctype=0';
					$data = array(
						"url" => $url,
						"statusCallback" => $statusCallback,
						"statusCallbackEvent" => array("completed","answered")
					);

					$res = $this->client->calls->create(
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

	public function sendCall($to, $leadId = 0, $campaignId= 0, $campaign_type = 0, $source = '', $amd = 0, $amd_timeout = 5000 ){
		// campaign_type (campaign/automation - 0/1)
		$result = array(
			'sid' => '',
			'error_code' => 0
		);

		if($this->account_sid && $this->auth_token && $this->twilio_number && $to) {
			$dnc = $this->checkDNC($to, $campaignId, $campaign_type, $leadId);
			if(!$dnc) {
				if ( ! $this->exclusions || ! count( $this->exclusions ) || ! in_array( substr( $to, 2, 3 ), $this->exclusions ) ) {
					try {
						$url            = $this->app_url . '/api/calltochoice?leadid=' . $leadId . '&cid=' . $campaignId . '&ctype=' . $campaign_type . '&callerId=' . $to . '&source=' . $source;
						$statusCallback = $this->app_url . '/api/callankapkryukhaha?leadid=' . $leadId . '&cid=' . $campaignId . '&ctype=' . $campaign_type;
						$data           = array(
							"url"                 => $url,
							"statusCallback"      => $statusCallback,
							"statusCallbackEvent" => array( "completed", "answered" )
						);

						if ( $amd ) {
							$data['url'] =  $data['url'].'&amd=1';
							$data["machineDetection"]               = "Enable";
							$data["MachineDetectionSilenceTimeout"] = $amd_timeout;
						}
						$from = $this->calculateFromNumber();

						if ( $from ) {
							$res = $this->client->calls->create(
								$to, //to
								$from, //from
								$data
							);
							if ( $res && $res->sid ) {
								$result['sid'] = $res->sid;
							}
						}


					} catch ( TwilioException $exception ) {
						if ( ! empty( $exception->getCode() ) ) {
							$result['error_code'] = $exception->getCode();
							// https://www.twilio.com/docs/api/errors
							$this->errorProcessing( $exception->getCode(), $to, $campaignId, $campaign_type, $leadId );
						}
					}
				} else {
					$result['error_code'] = 222222;
				}
			}
			else {
				$result['error_code'] = 333333;
			}
		}
		else {
			$result['error_code'] = 111111;
		}

		if($campaign_type == 1) {
			$data = [
				'automationId' => $campaignId,
				'leadId'       => $leadId,
				'sid'          => $result['sid'],
				'error_code'   => $result['error_code'] ? $result['error_code'] : 0
			];
			AutomationLogs::create( $data );
		}
		if(empty($from)){
			$from = '';
		}
		$data = [
			'campaignId'     => $campaignId,
			'leadId'         => $leadId,
			'sid'            => $result['sid'],
			'error_code'     => $result['error_code'] ? $result['error_code'] : 0,
			'campaign_type'  => $campaign_type,
			'to'             => $to,
			'from'           => $from
		];

		PhoneCallsLog::create( $data );
		if($result['error_code'] && $result['error_code'] != 333333) {
			CampaignsReports::countErrors( $campaignId, $campaign_type );
		}
		return $result;
	}

	protected function getAllShortcodes($string){
		if (preg_match_all('/(\{{.*?\}})/', $string , $matches)) {
			return $matches[0];
		}
		return false;
	}

	public function processShortcodes($string, $l){
		$shortcodes = $this->getAllShortcodes($string);
		if($shortcodes && is_array($shortcodes) && count($shortcodes)){
			foreach($shortcodes as $sh){
				$code = strtolower(trim(str_replace(['{{', '}}'], '', $sh)));
				if(!empty($code)) {
						$all_parts = explode('=', $code);
						if(!empty($all_parts[0])){
							$key = trim($all_parts[0]);
							$val =  !empty($all_parts[1]) ? trim($all_parts[1]) : '';
							switch($key){
								case 'phoneid':
									$leadid = "";
									if(!empty($l->phone_id)){
										$leadid = $l->phone_id;
									}
									$string = str_replace($sh, $leadid , $string);
									break;
								case 'name':
									$name = "";
									if(!empty($l->first_name)){
										$name = $l->first_name;
									}
									else{
										if(!empty($l->full_name)){
											$name = $l->full_name;
										}
									}
									$string = str_replace($sh, $name , $string);
									break;
								case 'phone':
									$phone = $val;
									if(!$this->source_phone){
										$source_phone_res = PhoneSource::select('phone','source')->get();
										$source_phone = array();
										if($source_phone_res && $source_phone_res->count()){
											foreach($source_phone_res as $ph){
												$source_phone[$ph->source] = $ph->phone;
											}
											$this->source_phone = $source_phone;
										}
									}
									if(!empty($this->source_phone)){
										$sph = $this->source_phone;
										if(!empty($sph[$l->source])){
											$phone = $sph[$l->source];
										}
									}
									$string = str_replace($sh, $phone , $string);
									break;
								default:
									$string = str_replace($sh, '' , $string);
							}
						}
				}
			}
		}
		return $string;
	}

	public function isCallSendTime(){
		$time_now = Carbon::now();
		$time_start = new Carbon('06:30:00');
		$time_end = new Carbon('18:00:00');

		/*
		$weekMap = [
			0 => 'SU',
			1 => 'MO',
			2 => 'TU',
			3 => 'WE',
			4 => 'TH',
			5 => 'FR',
			6 => 'SA',
		];
		$weekday = $weekMap[$dayOfTheWeek];
		*/
		$dayOfTheWeek = Carbon::now()->dayOfWeek;

		if($dayOfTheWeek == 6){
			$time_start = new Carbon('08:30:00');
			$time_end = new Carbon('13:00:00');
		}
		if($dayOfTheWeek && $time_now >= $time_start && $time_now <= $time_end) {
			return true;
		}
		return false;
	}

	public function isSMSAutomationSendTime(){

		$dayOfTheWeek = Carbon::now()->dayOfWeek;

		if($dayOfTheWeek) {
			return true;
		}
		return false;
	}

	public function	welcomeCall($lead, $a, $campaign_type = 1){
		if($this->isCallSendTime()) {
			$source = ! empty( $lead->source ) ? $lead->source : '';
			$this->sendCall( $lead->formated_phone_number, $lead->id, $a->id, $campaign_type, $source, $a->is_call_amd_enabled, $a->call_amd_timeout );

		}
	}

	protected function calculateFromNumber(){
		$from = false;
		$all_from  = $this->call_numbers;
		$last_from_number = PhoneCallsLog::orderBy( 'id', 'desc' )->value( 'from' );
		if ( is_array( $all_from ) && count( $all_from ) > 0 ) {
			$from = trim( $all_from[0] );
			if ( ! empty( $last_from_number ) ) {
				$all_from = array_unique( $all_from );
				if ( count( $all_from ) > 0 ) {
					foreach ( $all_from as $k => $phone ) {
						if ( trim( $phone ) == $last_from_number ) {
							if ( ! empty( $all_from[ $k + 1 ] ) ) {
								$from = trim( $all_from[ $k + 1 ] );
								return  $from;
							} else {
								$from = trim( $all_from[0] );
								return  $from;
							}
							break;
						}
					}
				}
			}
		}
		return  $from;
	}

	public function getFromNumber($sim_calls_count){
		$all_from  = $this->call_numbers;
		if ( is_array( $all_from ) && count( $all_from ) > 0 ) {
			$all_numbers = CallingNumbers::pluck('formated_phone')->toArray();
			if(is_array( $all_numbers )) {
				// checking if we have new number
				$need_to_add = array_diff( $all_from, $all_numbers );

				if($need_to_add && count($need_to_add)) {
					foreach ( $need_to_add as $from ) {
						$data = [
							'formated_phone' => $from,
						];
						CallingNumbers::create( $data );
					}
				}

				//checking if we deleted some numbers from our list, can be commented
				$need_to_delete = array_diff( $all_numbers, $all_from );
				if($need_to_delete && count($need_to_delete)) {
					foreach ( $need_to_delete as $n ) {
						CallingNumbers::where( 'formated_phone', $n )->delete();
					}
				}

			}
			$res = CallingNumbers::orderBy('calls')->first();
			if($res && $res->calls < $sim_calls_count){
				return $res->formated_phone ;
			}
			else {
				$res_number = CallingNumbers::where( 'updated_at', '<=', Carbon::now()->subMinutes( 30 ) )->orderBy('updated_at','asc')->value( 'formated_phone');
				if(!empty($res_number)){
					CallingNumbers::where( 'formated_phone', $res_number)->where('calls','>',0)->update( ['calls' => 0]);
					return $res_number;
				}
				return false;
			}
		}
		return false;
	}

	public function	welcomeSMS($lead, $a){
		if ( ! empty( $a->message ) && $this->isSMSAutomationSendTime() ) {
			$message_id = 1;
			$res_message = $a->message;
			$mediaUrl = !empty($a->mediaUrl) ? $a->mediaUrl : false;
			$percentage = ! empty( $a->percentage ) ? $a->percentage : 10;
			$percentage2 = 10 - $percentage;
			$message2   = ! empty( $a->message2 ) ? $a->message2 : '';
			if ( !empty($message2) && $percentage != 10 ) {
				// getting last 10 leads from that automation
				$last_leads = AutomationLogs::select('message_id')
									->where( 'automationId', $a->id )
									->where( 'error_code', 0 )
				                    ->orderBy( 'id', 'desc' )
				                    ->limit( 9 )
				                    ->get();
				$leads_count = $last_leads->count();
				if ( $leads_count) {
					$message1_total = 0;
					$message2_total = 0;
					foreach ( $last_leads as $k=>$l ) {
						switch ( $l->message_id  ) {
							case 1:
								$message1_total ++;
								break;
							case 2:
								$message2_total ++;
								break;
						}
					}
					if ($message1_total >= $percentage && ($message2_total != $percentage2)) {
						$message_id = 2;
						$res_message =  $message2;
						$mediaUrl = !empty($a->mediaUrl2) ? $a->mediaUrl2 : false;
					}
				}

			}

			$this->sendMessage($res_message,$lead,$a->id,1,$message_id, $mediaUrl);
		}


	}


	public function welcome($lead){
		if(!empty($lead) && !empty($lead->formated_phone_number) && !empty($lead->id)) {
			$lead_tags = !empty($lead->tags) ? $lead->tags : [];

			$automations = Automations::select( 'id', 'message', 'message2', 'percentage', 'mediaUrl', 'mediaUrl2','sources', 'a_type', 'is_call_amd_enabled', 'call_amd_timeout' )
			                          ->where( 'status' ,1 )
			                          ->where( 'delay' , 0 )
			                          ->orderBy( 'id', 'asc' )
			                          ->orderBy( 'a_type', 'desc' )
			                          ->get();


			if($automations) {
				foreach ( $automations as $a ) {
					$source = !empty($a->sources) ? PhoneSource::getAllSources($a->sources) : [];
					$can_send = true;

					$min_call_duration = false;
					$max_call_duration = false;
					$input_sms_count = false;
					if(!empty($source)) {
						if(!empty($source['sources_qa'])) {
							$res = LeadsData::where('leadId',$lead->id)->whereIn('answer_id', $source['sources_qa'])->count();
							if(!$res){
								$can_send = false;
							}
						}
						else {
							if ( ! empty( $source['sources_included'] ) && ! count(array_intersect( $lead_tags, $source['sources_included'] )) ) {
								$can_send = false;
							} else {
								if ( ! empty( $source['sources_excluded'] ) && count(array_intersect( $lead_tags, $source['sources_excluded']) ) ) {
									$can_send = false;
								}
							}
						}

						if(isset( $source['min_call_duration']) && $source['min_call_duration'] != ''){
							$min_call_duration = $source['min_call_duration'];
						}
						if(isset( $source['max_call_duration']) && $source['max_call_duration'] != ''){
							$max_call_duration = $source['max_call_duration'];
						}
						if(isset( $source['input_sms_count']) && $source['input_sms_count'] != ''){
							$input_sms_count = $source['input_sms_count'];
						}

					}

					$res_filter = true;

					if(
						(isset($min_call_duration) && $min_call_duration !== false) ||
						(isset($max_call_duration) && $max_call_duration !== false) ||
						(!empty($input_sms_count))
					){
						$res = Leads::where( 'leads.id', $lead->id );
						$res_filter = Leads::filterByDuration( $res, $min_call_duration, $max_call_duration, $input_sms_count )->first( 'leads.id' );
					}

					if($can_send && $res_filter) {
						if ( $a->a_type ) {
							$this->welcomeCall( $lead, $a );
						} else {
							$this->welcomeSMS( $lead, $a );
						}
					}

				}
			}

		}

	}

	public function sendMessage($message,$lead,$campaignId=0,$campaign_type= 0,$message_id, $mediaUrl){
		$res_message = $this->processShortcodes($message,$lead );
//		echo "<pre>";
//		var_export($lead->formated_phone_number. ', '.$res_message.', '.$lead->id);
//		echo "</pre>";
		$res = $this->sendSMS( $lead->formated_phone_number, $res_message, $lead->id, $campaignId,$campaign_type, true, $mediaUrl);
//		$res = false;
		if ( $res ) {
			if($campaign_type == 1) {
				$data = [
					'automationId' => $campaignId,
					'leadId'       => $lead->id,
					'sid'          => $res['sid'],
					'error_code'   => $res['error_code'] ? $res['error_code'] : 0,
					'message_id'   => $message_id
				];
				AutomationLogs::create( $data );
			}
			$data = [
				'campaignId'     => $campaignId,
				'leadId'         => $lead->id,
				'sid'            => $res['sid'],
				'message'        => $res_message,
				'mediaUrl'       => $mediaUrl ? $mediaUrl : '',
				'error_code'     => $res['error_code'] ? $res['error_code'] : 0,
				'campaign_type'  => $campaign_type,
				'message_id'     => $message_id
			];
			Logs::create( $data );
			return $res;
		}
		return false;
	}

	public function if_completed($phone){
		try {
			$data = array(
				"to" => $phone,
				"status" => "queued"
			);

			$res = $this->client->calls->read(
				$data,
				1
			);

			if ( count($res) ) {
				return false;
			}
			else{
				return true;
			}
		} catch ( TwilioException $exception ) {
			$err = ! empty( $exception->getCode()) ? 'Error code: '.$exception->getCode(). ', ' : '';
			$err .= ! empty( $exception->getMessage()) ? 'Error code: '.$exception->getMessage() : '';
			Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'if_completed'] );
			return true;
		}
	}

	public function if_queued(){
		try {
			$data = array(
				//"startTime" => new \DateTime('2009-7-6'),
				"status" => "queued"
				//"status" => "completed"
			);

			$res = $this->client->calls->read(
				$data,
				10
			);

			if ( count($res) ) {
				return true;
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


	public function errorProcessing($error_code, $formated_phone_number, $campaignId = 0, $campaign_type = 0, $leadId = 0, $is_call = true){
		$lead = false;
		switch ( $error_code ) {
			case 21610: // unsubscribed
				$lead = $this->unsubscribeNumber($formated_phone_number, 2, $campaignId, $campaign_type, $leadId);
				break;
			case 21612: //unreachable
			case 30003: //unreachable
				$lead = Leads::where('formated_phone_number' , $formated_phone_number)->where('phone_status', '!=', 4 )->where('phone_status', '!=', 0 )->update([ 'phone_status' => 2 ]);
				Uleads::where('formated_phone_number' , $formated_phone_number)->update([ 'phone_status' => 2 ]);
				break;
			case 21614: //not valid
			case 21211:
				$lead = Leads::where('formated_phone_number' , $formated_phone_number)->where('phone_status', '!=', 4 )->where('phone_status', '!=', 0 )->update([ 'phone_status' => 5 ]);
				Uleads::where('formated_phone_number' , $formated_phone_number)->update([ 'phone_status' => 5 ]);
				break;
		}
		CampaignsReports::countErrors( $campaignId, $campaign_type );

		if($formated_phone_number){
			$status_field = 'sms_status';
			if($is_call){
				$status_field = 'call_status';
			}
			$result = Uleads::where('formated_phone_number', $formated_phone_number)->where($status_field, '<=', 3)->first();
			if($result){
				$result->increment( $status_field, 1 );
				$result->save();
			}
		}
		return $lead;
	}

	public function checkDNC($formated_phone_number, $campaignId = 0, $campaign_type = 0 /* automation or campaign */, $leadId = 0){
		if($formated_phone_number == '+18184507532'){
			return 0;
		}
		$err = '';
		$phone_number =  str_replace('+','',$formated_phone_number);
		if($phone_number) {
			$endpoint = 'https://dnc.metals.com/DNC';
			try {
				$client      = new \GuzzleHttp\Client(
					[ 'base_uri' => $endpoint, 'verify' => false ]
				);
				$res         = $client->request( 'GET', $endpoint, [
					'query' => [
						'phone_number' => $phone_number,
					]
				] );
				$status_code = $res->getStatusCode();
				$header      = $res->getHeader( 'content-type' )[0];
				$err         = 'Status code: ' . $status_code . ', Header: ' . $header;
				$body        = $res->getBody();
				if ( ! empty( $body ) ) {
					$result = json_decode( $body );
					if ( ! empty( $result ) && isset( $result->status ) ) {
						if($result->status) {
							$this->unsubscribeNumber( $formated_phone_number, 1, $campaignId, $campaign_type, $leadId );
							CampaignsReports::countDnc($campaignId, $campaign_type);
						}
						return $result->status;
					}
				}
			}
			catch (GuzzleException $exception){
				$err = ! empty( $exception->getCode()) ? 'Error code: '.$exception->getCode(). ', ' : '';
				$err .= ! empty( $exception->getMessage()) ? 'Error code: '.$exception->getMessage() : '';
				Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'checkDNC'] );
				return 'error';
			}
		}
		else{
			$err = 'empty phone number';
		}
		Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'checkDNC'] );
		return 'error';
	}

	public function unsubscribeNumber($formated_phone_number, $unsubscribed_by = 0, $campaignId = 0, $campaign_type = 0, $leadId = 0){
		/*
		 * unsubscribed_by : 0 - from user , 1 - from DNC , 2 - from twilio, 4 - manually , 6 - programmatically (cron, etc)
		 */
		$lead = Leads::where('formated_phone_number' , $formated_phone_number)->where('phone_status', '!=', 4 )->where('phone_status', '!=', 0)->update([ 'phone_status' => 0 ]);
		$phone_id = Leads::where('formated_phone_number' , $formated_phone_number)->value('phone_id');
		if($lead && $phone_id) {
			$res                  = Unsubscribed::firstOrNew( [
				'formated_phone_number' => $formated_phone_number
			] );
			$res->unsubscribed_by = $unsubscribed_by;
			$res->campaignId      = $campaignId;
			$res->campaign_type   = $campaign_type;
			$res->leadId          = $leadId;
			$res->phone_id        = $phone_id;
			$res->save();
			$this->countUnsubscribeds( $formated_phone_number, $unsubscribed_by, $campaignId, $campaign_type );
		}
		return $lead;
	}
	 public function subscribeNumber($formated_phone_number){
		 $lead = Leads::where('phone_status', 0)
		      ->where('formated_phone_number', $formated_phone_number)
		      ->update(['phone_status' => 1]);
		 Unsubscribed::where('formated_phone_number', $formated_phone_number)->delete();
		 return $lead;
	 }

	 protected function countUnsubscribeds($formated_phone_number, $unsubscribed_by = 0, $campaignId = 0, $campaign_type = 0){
		if(!$unsubscribed_by){
			$res = $this->getCampaignByPhone($formated_phone_number);
			if($res && $res->campaignId){
				$campaignId = $res->campaignId;
				$campaign_type = !empty($res->campaign_type) ? $res->campaign_type : 0;
			}
		}
		return CampaignsReports::countUnsubscribed($campaignId, $campaign_type);
	 }

	 public function getCampaignByPhone($formated_phone_number){
		 $report_date = Carbon::today();
		 $res = false;
		 $max_id = Logs::max('id');
		 $min_id = 1;
		 if($max_id && $max_id >= 1000001){
		 	$min_id = $max_id - 1000000;
		 }

		 if($min_id) {
			 $res = Logs::selectRaw('phone_messages_logs.*')
						 ->leftJoin( 'leads', function ( $join ) {
							 $join->on( 'leads.id', '=', 'phone_messages_logs.leadId' )
								 ->where( 'leads.phone_status', 0 );
						 } )
			            ->where( 'phone_messages_logs.id', '>=', $min_id )
			            ->where( 'phone_messages_logs.created_at','>=',$report_date )
			            ->where( 'leads.formated_phone_number', $formated_phone_number )
			            ->orderBy( 'phone_messages_logs.id', 'asc' )
				        ->first()
			 ;
		 }

		 return $res;
	 }


	public function sendCampaign($c, $chunk, $call_chunk){

		$leads_data = json_decode( $c->segment_info );
		if ( ! empty( $leads_data )) {
			$leads = false;
			$l_total = 0;
			$finish_id = 0;

			$chunk_size = $chunk;
			if($c->c_type){
				$chunk_size = $call_chunk;
			}

			$data_to_update = [
				'started_at' => date( 'Y-m-d H:i:s' ),
				'status' => 2
			];

			$bysource_last_leadid = 0;
			$bysegment_last_leadid = !empty($leads_data->added_min_id) ? $leads_data->added_min_id : 0 ;

			if($c->last_leadId){ //if continue sending campaign from last lead id
				$bysource_last_leadid = $c->last_leadId*1;
				$bysegment_last_leadid = ($c->last_leadId*1) + 1;

				$data_to_update = [
					'status' => 2
				];
			}

			$min_call_duration = false;
			$max_call_duration = false;
			$input_sms_count = false;
			if(isset( $leads_data->min_call_duration) && $leads_data->min_call_duration != ''){
				$min_call_duration = $leads_data->min_call_duration;
			}
			if(isset( $leads_data->max_call_duration) && $leads_data->max_call_duration != ''){
				$max_call_duration = $leads_data->max_call_duration;
			}
			if(!empty( $leads_data->input_sms_count)){
				$input_sms_count = $leads_data->input_sms_count;
			}

			$by_tag = false;
			if(empty( $leads_data->added_min_id ) && empty( $leads_data->added_max_id ) && !empty($leads_data->tags)) { // segment by source
				$by_tag = true;
				if(empty( $leads_data->tags->tags_qa)){
					$leads_data->tags->tags_qa = false;
				}
				$em = new PhoneTags();
				$em2 = new PhoneTags();
				$res = $em->getSourcesUniqueLeadsLimited(
					$leads_data->tags->tags_included,
					false,
					$bysource_last_leadid ,
					0,
					$chunk_size,
					$min_call_duration,
					$max_call_duration,
					$input_sms_count,
					$leads_data->tags->tags_qa,
					$c->c_type
				);
				$res2 = $em2->getSourcesUniqueLeadsLimited(
					$leads_data->tags->tags_included,
					false,
					$bysource_last_leadid ,
					0,
					$chunk_size,
					$min_call_duration,
					$max_call_duration,
					$input_sms_count,
					$leads_data->tags->tags_qa,
					$c->c_type
				);
				if ( ! empty( $res )) {
					$finish = $res2->orderBy( 'uleads.id', 'desc' )->first();
					$leads = $res->orderBy( 'uleads.id', 'asc' )->get();
					if($leads){
						$l_total = $leads->count();
					}
					if($finish && $finish->phone_id){
						$finish_id = $finish->phone_id;
					}
				}
			}
			else{ //segment by id
				$leads = Leads::getSegmentLeads( $bysegment_last_leadid, $leads_data->added_max_id, $min_call_duration, $max_call_duration, $input_sms_count, $c->c_type )
				              ->orderBy( 'leads.id', 'asc' )
				              ->limit( $chunk_size )
				              ->get();
				if($leads){
					$l_total = $leads->count();
					$finish_id = $leads_data->added_max_id;
				}
			}

			Campaigns::findOrFail( $c->id )->update( $data_to_update );
			$last_leadId = $c->last_leadId;
			if ( $l_total ) {
				foreach ( $leads as $l ) {
					if ( $l->formated_phone_number ) {
						$mediaUrl = ! empty( $c->mediaUrl ) ? $c->mediaUrl : false;
						if($by_tag){
							$last_leadId = $l->phone_id;

							$l->id = 0;
							$lead_ids = Leads::select('id')->where('phone_id', $l->phone_id)->where('phone_status', '!=', 4)->first();
							if($lead_ids && $lead_ids->id){
								$l->id = $lead_ids->id;
							}
						}
						else{
							$last_leadId = $l->id;
						}
						if ( $c->c_type ) {  //calls
							if($this->isCallSendTime()) {
								$source   = ! empty( $l->source ) ? $l->source : '';
								// campaign_type (campaign/automation - 0/1)
								$this->sendCall( $l->formated_phone_number, $l->id, $c->id, 0, $source, $c->is_call_amd_enabled, $c->call_amd_timeout );
//								usleep(200000);
								usleep(2000000); //2sec
							}
							else{
								Campaigns::pauseCampaign($c->id, $last_leadId );
								break;
							}
						} else { //Sms
							// campaign_type (campaign/automation - 0/1)
							$this->sendMessage( $c->message, $l, $c->id, 0, 1, $mediaUrl );
						}

					}
				}

				if ( $last_leadId == $finish_id ) {
					Campaigns::finishCampaign($c->id, $last_leadId );
				} else {
					Campaigns::pauseCampaign($c->id, $last_leadId );
				}
			} else {
				Campaigns::finishCampaign($c->id, $last_leadId );
			}

		}


	}

	public function sendSupportSMS($formated_phone, $from, $message, $message_id = 0, $mediaUrl = false){
		$result = array(
			'sid' => '',
			'error_code' => 0
		);

		if($this->account_sid && $this->auth_token && $this->twilio_number && $formated_phone && $from) {
			try {
				//$client = new Client( $this->account_sid, $this->auth_token );
				$data = array(
					'from'           => $from,
					'body'           => $message,
					'statusCallback' => $this->app_url . '/api/supankapkryukhaha?me_id=' . $message_id
				);
				if ( ! empty( $mediaUrl ) ) {
					$data['mediaUrl'] = $mediaUrl;
				}
				$res = $this->client->messages->create(
					$formated_phone,
					$data
				);
				if ( $res ) {
					$result['sid'] = $res->sid;
				}
			} catch ( TwilioException $exception ) {
				if ( ! empty( $exception->getCode() ) ) {
					$result['error_code'] = $exception->getCode();	// https://www.twilio.com/docs/api/errors
				}
				$err = ! empty( $exception->getCode()) ? 'Error code: '.$exception->getCode(). ', ' : '';
				$err .= ! empty( $exception->getMessage()) ? 'Error code: '.$exception->getMessage() : '';
				$err .=  ! empty($result['sid']) ? ', sid: '.$result['sid'] : '';
				Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'sendSupportSMS'] );
			}
		}
		else {
			$result['error_code'] = 111111;
		}
		return $result;
	}


}