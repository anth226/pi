<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exeptions;
use App\PhoneCallsLog;
use App\PhoneSource;
use App\CampaignsReports;

use Twilio\TwiML\VoiceResponse;

use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;

class CallRedirectController extends BaseController
{
	public function index(Request $request)
	{
		$this->callRedirect($request);
	}
	public function store(Request $request)
	{
		$this->callRedirect($request);
	}

	protected function callRedirect($request){
		try {
			$input = $request->all();

			$response = new VoiceResponse();
			if($input['callerId'] && $input['destination']) {
				// If the user entered digits, process their request
				if ( array_key_exists( 'Digits', $input ) ) {
					switch ( $input['Digits'] ) {
						case 1:
							$res_data = array(
								"callerId" => $input['callerId']
							);
							$response->dial( $input['destination'], $res_data );

							//$response->say('I am calling!');
							CampaignsReports::countForwarded($input['cid'], $input['ctype']);
//							PhoneCallsLog::where('campaignId' , $input['cid'])
//							             ->where('leadId' , $input['leadid'])
//							             ->where('campaign_type' , $input['ctype'])
//							             ->update(['redirected_to' => $input['destination']]);

							break;
						default:
							$response->say( 'Sorry, I don\'t understand that choice.' );
							//$response->redirect('/2-call-answered.php');
					}
					print $response;
				}

			}

		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'CallRedirectController', 'function' => 'callRedirect'] );
			abort(500, $err);
		}
	}
}
