<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exeptions;
use Twilio\TwiML\VoiceResponse;
use App\PhoneCallsLog;
use App\Automations;
use App\Campaigns;
use App\CampaignsReports;

use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;

class CallSettingsController extends BaseController
{
	public function index(Request $request)
	{
		$this->callSettings($request);
	}
	public function store(Request $request)
	{
		$this->callSettings($request);
	}

	protected function callSettings($request){
		try {
			$input = $request->all();
			$answered_by = !empty($input['AnsweredBy']) ? $input['AnsweredBy'] : '';
			$response    = new VoiceResponse();
			if(	$answered_by != 'machine_start' && $answered_by != 'fax' && $answered_by != 'machine_end_beep' && $answered_by != 'machine_end_other') {
				$destination = config( 'callnumers.phone.default_forward_number' );
				if ( $input['cid'] ) {
					if ( $input['ctype'] ) {
						$res = Automations::select('message', 'mediaUrl')->where( 'id', $input['cid'] )->first();
						if($res){
							$message = $res->message;
							$mediaUrl = $res->mediaUrl;
						}
					} else {
						$res = Campaigns::select('message', 'mediaUrl', 'redirected_phone')->where( 'id', $input['cid'] )->first();
						if($res){
							$message = $res->message;
							$mediaUrl = $res->mediaUrl;
							$destination = $res->redirected_phone;
						}
					}
				}
				$call_text = ! empty( $message ) ? trim( $message ) : '';
				$call_audio = ! empty( $mediaUrl ) ? trim( $mediaUrl ) : '';
				$app_url = config('app.url');
				if(!empty($input['amd'])){
					if ( $input['callerId'] ) {
						$res_data = array(
							"callerId" => $input['callerId']
						);
						if ( ! empty( $call_audio ) ) {
							$response->play($app_url.'/images/'.$call_audio);
						}
						else {
							if ( ! empty( $call_text ) ) {
								//$response->say( $call_text, ['voice' => 'male'] );
								$response->say( $call_text );
							}
						}
						$response->dial( $destination, $res_data );

						//$response->say( 'I am calling!' );
						CampaignsReports::countForwarded($input['cid'], $input['ctype']);

						print $response;

					}
				}
				else {
					$fullUrl = $request->fullUrl();
					$action  = str_replace( 'calltochoice', 'calltoredirect', $fullUrl );
					$gather  = $response->gather( [
//						'input'     => 'speech dtmf',
						'input'     => 'dtmf',
						'timeout'   => 10,
						'numDigits' => 1,
						'action'    => $action . '&destination=' . $destination
					] );
					if ( ! empty( $call_audio ) ) {
						$gather->play($app_url.'/images/'.$call_audio);
					}
					else {
						if ( $call_text ) {
							$gather->say( $call_text );
						}
					}
					print $response;

				}

			} else {
				$response->hangup();
				print $response;
			}


		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'CallSettingsController', 'function' => 'callSettings'] );
			abort(500, $err);
		}
	}
}
