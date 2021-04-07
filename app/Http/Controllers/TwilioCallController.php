<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\KmClasses\Pipedrive;
use App\Salespeople;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use App\Errors;
use Exception;

class TwilioCallController extends BaseController
{
	/**
	 * Process a new call
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function newCall(Request $request)
	{
//		Storage::put('request.txt', json_encode($request) );
		$response = new VoiceResponse();
		$callerIdNumber = config('twilio.twilio.twilio_from');

		$phoneNumberToDial = $request->input('phoneNumber');

		if (isset($phoneNumberToDial)) {
			$dial = $response->dial($phoneNumberToDial, ['callerId'=>$callerIdNumber]);
			$dial->number($phoneNumberToDial);
		}
		else {
			if(!empty($request->input('From'))){
				$callerIdNumber = $request->input('From');
			}
			$dial = $response->dial(null, ['callerId'=>$callerIdNumber]);
			$identity = "1";
			$dial->client($identity);
		}

		return $response;
	}

	public function callStats(Request $request){
//		Storage::put('calls.txt', json_encode($request) );
		Storage::append('calls.log', json_encode($request->input()));
	}

	public function getLogBySid($call_sid){
		$log = [];
		$sid           = config( 'twilio.twilio.twilio_sid' );
		$token         = config( 'twilio.twilio.twilio_token' );
		$twilio = new Client( $sid, $token );
		try{
			$call = $twilio->calls
				->read(["parentCallSid" => $call_sid], 1);
			if(!empty($call) && !empty($call[0])) {
				$log['status']   = $call[0]->status;
				$log['duration'] = $call[0]->duration;
				$log['from']     = $call[0]->from;
				$log['to']       = $call[0]->to;
			}
			return $log;
		}
		catch ( TwilioException $exception ) {
			$error = '';
			if ( ! empty( $exception->getCode() ) ) {
				$error .= $exception->getCode().' ';
				// https://www.twilio.com/docs/api/errors
			}
			if ( ! empty( $exception->getMessage() ) ) {
				$error .= $exception->getMessage();
			}
			return $error;
		}
	}

	protected function savePiPerson($person, $owner_id){
		try{
//			$source_field_name = config( 'pipedrive.source_field_id' );
//			$extra_field_name = config( 'pipedrive.extra_field_id' );
//			$timezone_field_name = config( 'pipedrive.timezone_field_id' );

			$source_field_name = '0d42d585b2f6407cd384cd02838de179c0a1527d';
			$extra_field_name = '012fe2582b1a93009814bdd11aa6a630622eb209';
			$timezone_field_name = '0733183570c5c4a996b459ea05ab6f19c9ee2f72';

			$source = [];
			$extra = [];
			foreach($person as $k => $v){
				if($k == $source_field_name){
					if(!empty($v)) {
						$sources_arr = explode( ',', $v );
						if ( $sources_arr && count( $sources_arr ) ) {
							foreach ( $sources_arr as $s ) {
								$s_trimed = trim( $s );
								if ( $s_trimed ) {
									$source[] = $s_trimed;
								}
							}
							$source = array_unique($source);
						}
					}
				}
				if($k == $extra_field_name){
					if(!empty($v)) {
						$extra_arr = explode( ',', $v );
						if ( $extra_arr && count( $extra_arr ) ) {
							foreach ( $extra_arr as $s ) {
								$s_trimed = trim( $s );
								if ( $s_trimed ) {
									$extra[] = $s_trimed;
								}
							}
							$extra = array_unique($extra);
						}
					}
				}
			}

			$data = [
				'ownerId' => $owner_id,
				'personId' => $person->id,
				'name' => $person->name,
				'timezone' => $person->$timezone_field_name,
				'addTime'  => $person->addTime,
				'label' => $person->label,
				'extra_field' => $extra,
				'source_field' => $source,
				'phone' => $person->phone,
				'email' => $person->email,
//				'persons_data' => $person
			];

			return $data;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'ProcessingPiLeads',
				'function' => 'savePiPerson'
			]);
			return false;
		}
	}

	public function getLeadsByOwnerOnePage($owner_id, $start = 0, $limit = 500){
		try{
			$res = [
				'data' => [],
				'next_start' => -1
			];
//			$key = config( 'pipedrive.api_key' );
			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
			$persons  = Pipedrive::executeCommand( $key, new Pipedrive\Commands\GetPersons($owner_id, $start, $limit) );
			if(!empty($persons)){
				if(!empty($persons->data)) {
					foreach($persons->data as $p){
						if($p->label != 21 && $p->wonDealsCount < 1) {
							$data = $this->savePiPerson($p, $owner_id);
							$res['data'][] = $data;
						}
					}
				}
				if(
					!empty($persons->additionalData) &&
					!empty($persons->additionalData->pagination) &&
					!empty($persons->additionalData->pagination->nextStart)
				)
				{
					$res['next_start'] = $persons->additionalData->pagination->nextStart;
				}

			}
			return $res;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'ProcessingPiLeads',
				'function' => 'getLeadsByOwnerOnePage'
			]);
			return false;
		}
	}

	public function getPersonsByOwner(Request $request){
		$input = $request->all();
		if(
			!empty($input) &&
			!empty( $input['owner_id'] )
		) {
			$res  = $this->getLeadsByOwner($input['owner_id']);
			if($res){
				return $this->sendResponse($res);
			}
			else{
				return $this->sendError("Something went wrong.");
			}
		}
		return	$this->sendError("Something went wrong.");

	}

	public function getLeadsByOwner($owner_id){
		try{
			$res = [];
			$next_start = 0;
			while ($next_start >= 0){
				$result = $this->getLeadsByOwnerOnePage($owner_id, $next_start, $limit = 100);
				$res = array_merge($res, $result['data']);
				$next_start = $result['next_start'];
			}
			return $res;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'TwilioCallController',
				'function' => 'getLeadsByOwner'
			]);
			return false;
		}
	}

	public function getLeadsForUsers(){
		try{
			$users = User::get();
			if (! empty( $users ) && $users->count() ) {
				foreach ( $users as $u ) {
					if ( ! empty( $u->pipedrive_user_id ) ) {
						$this->getLeadsByOwner($u->pipedrive_user_id);
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'TwilioCallController',
				'function' => 'getLeadsForUsers'
			]);
			return false;
		}
	}

	public function getLeadsForSalespeople(){
		try{
			$salespeople = Salespeople::get();
			if (! empty( $salespeople ) && $salespeople->count() ) {
				foreach ( $salespeople as $s ) {
					if ( ! empty( $s->pipedrive_user_id ) ) {
						$this->getLeadsByOwner($s->pipedrive_user_id);
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'TwilioCallController',
				'function' => 'getLeadsForSalespeople'
			]);
			return false;
		}
	}

}
