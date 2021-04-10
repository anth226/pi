<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\KmClasses\Pipedrive;
use Illuminate\Http\Request;
use App\Errors;
use Exception;

class TwillioController extends BaseController
{
	protected $twilioKey, $source_field_name, $extra_field_name, $timezone_field_name;

	public function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware('permission:make-calls');

//		$this->twilioKey = config( 'pipedrive.api_key' );
//		$this->source_field_name = config( 'pipedrive.source_field_id' );
//	    $this->extra_field_name = config( 'pipedrive.extra_field_id' );
//		$this->timezone_field_name = config( 'pipedrive.timezone_field_id' );

		$this->source_field_name = '0d42d585b2f6407cd384cd02838de179c0a1527d';
		$this->extra_field_name = '012fe2582b1a93009814bdd11aa6a630622eb209';
		$this->timezone_field_name = '0733183570c5c4a996b459ea05ab6f19c9ee2f72';
		$this->twilioKey = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		return view('twilio.call');
	}

	public function getPersonsByOwner(Request $request){
		$input = $request->all();
		if(
			!empty($input) &&
			!empty( $input['owner_id'] )
		) {
			$find = !empty( $input['text'] ) ?  $input['text'] : '';
			$start = !empty( $input['start'] ) ?  $input['start'] : 0;
			$res  = $this->getLeadsByOwner($input['owner_id'], $find, $start);
			if($res){
				return $this->sendResponse($res);
			}
			else{
				return $this->sendError("Something went wrong.");
			}
		}
		return	$this->sendError("Error! Something went wrong.");

	}

	public function getLeadsByOwner($owner_id, $find = '',$start = 0, $paginated = true){
		try{
			$res = [
				'data' => [],
				'start' => 0,
				'next_start' => 0,
				'page_size' => 500
			];
			$limit = $res['page_size'];
			if($find){
				$limit = 100;
				$res['page_size'] = $limit;
			}
			if(!$paginated) {
				$next_start = 0;
				while ( $next_start >= 0 ) {
					$result     = $this->getLeadsByOwnerOnePage( $owner_id, $find, $next_start, $limit );
					$res['data']        = array_merge( $res['data'], $result['data'] );
					$next_start = $result['next_start'];
				}
			}
			else{
				$result     = $this->getLeadsByOwnerOnePage( $owner_id, $find, $start, $limit );
				$res['data']        = array_merge( $res['data'], $result['data'] );
				$res['start'] = $start;
				$res['next_start'] = $result['next_start'];
			}
			return $res;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'TwillioController',
				'function' => 'getLeadsByOwner'
			]);
			return false;
		}
	}

	public function getLeadsByOwnerOnePage($owner_id, $find, $start = 0, $limit = 500){
		try{
			$res = [
				'data' => [],
				'next_start' => -1
			];

			$key = $this->twilioKey;
			if($find){
				$persons = Pipedrive::executeCommand( $key, new Pipedrive\Commands\FindPerson( $find, $start, $limit ) );
			}
			else {
				$persons = Pipedrive::executeCommand( $key, new Pipedrive\Commands\GetPersons( $owner_id,0, $start, $limit ) );
			}
			if(!empty($persons)){
				if(!empty($persons->data)) {
					if($find){
						if(!empty($persons->data->items)){
							foreach ( $persons->data->items as $i ) {
								if(!empty($i) && !empty($i->item)) {
									$p = $i->item;
									if(!empty($p->owner) && !empty($p->owner->id) && $p->owner->id == $owner_id) {
										$data = $this->processingFindedPiPerson( $p, $owner_id );
										if ( ! empty( $data ) && count( $data ) ) {
											$res['data'][] = $data;
										}
									}
								}
							}
						}
					}
					else {
						foreach ( $persons->data as $p ) {
							if ( $p->label != 21 && $p->wonDealsCount < 1 ) {
								$data = $this->processingPiPerson( $p, $owner_id );
								if ( ! empty( $data ) && count( $data ) ) {
									$res['data'][] = $data;
								}
							}
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
				'controller' => 'TwillioController',
				'function' => 'getLeadsByOwnerOnePage'
			]);
			return false;
		}
	}

	protected function processingPiPerson($person, $owner_id){
		try{
			$source_field_name = $this->source_field_name;
			$extra_field_name = $this->extra_field_name;
			$timezone_field_name = $this->timezone_field_name;

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
				'ownerName' => $person->ownerId->name,
				'ownerEmail' => $person->ownerId->email,
				'personId' => $person->id,
				'name' => $person->name,
				'timezone' => $person->$timezone_field_name,
				'addTime'  => $person->addTime,
				'label' => $person->label,
				'extra_field' => $extra,
				'source_field' => $source,
				'phone' => $person->phone,
				'email' => $person->email
			];

			return $data;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'TwillioController',
				'function' => 'processingPiPerson'
			]);
			return false;
		}
	}

	protected function processingFindedPiPerson($person, $owner_id){
		try{
			if(!empty($person->id)){
				$person = Pipedrive::executeCommand( $this->twilioKey, new Pipedrive\Commands\GetPersonCustomField( $person->id ) );
				if(!empty($person)) {
					if ( ! empty( $person->data ) ) {
						$p = $person->data;
						if ( $p->label != 21 && $p->wonDealsCount < 1 ) {
							return $this->processingPiPerson( $p, $owner_id );
						}
					}
				}
			}
			return false;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'TwillioController',
				'function' => 'processingFindedPiPerson'
			]);
			return false;
		}
	}
}
