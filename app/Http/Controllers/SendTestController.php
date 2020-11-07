<?php

namespace App\Http\Controllers;

use App\EmailCampaigns;
use App\KmClasses\Sms\EmailSender;
use App\ProjectsEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\SmsSender;
use App\Automations;
use App\Campaigns;
use Validator, Input, Exception;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;


class SendTestController extends BaseController
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//$this->middleware(['auth','verified','approved']);
		$this->middleware(['auth','verified']);
		$this->middleware('permission:send-test', ['only' => ['sendTest']]);
		$this->middleware('permission:send-email-test', ['only' => ['sendEmailTest']]);
	}

	public function sendEmailTest(Request $request){
		$sender =  new EmailSender();
		$input = $request->all();
		$campaign_id    = ! empty( $input['cid'] ) ? $input['cid'] : 0;
		$to = ! empty( $input['email'] ) ? $input['email'] : 0;
		if($to && $campaign_id) {
			$c =  EmailCampaigns::where('id', $campaign_id)->first();
			if($c && $c->id) {
				$from_email = '';
				$from_name = '';
				$from = ProjectsEmails::getFromEmail($c->from_address_id)->first();
				if($from){
					$from_email= $from->email_address ? $from->email_address : '';
					$from_name= $from->email_name ? $from->email_name : '';
				}
				$template ='vendor.maileclipse.templates.'.$c->template;
				$campaign_id = $c->id ? $c->id : 0;
				$from_address_id = $c->from_address_id ? $c->from_address_id : 0;
				$subject = $c->subject ? $c->subject :'';
				$res = $sender->sendEmail(
					$to,
					$from_email,
					$campaign_id,
					$template,
					$subject,
					'',
					0,
					$from_name,
					$from_address_id
				);
				if($res){
					if($res['success']) {
						return $this->sendResponse( ' Message has been sent', 'Success!' );
					}
					else{
						if($res['message']){
							return $this->sendError( $res['message'] );
						}
					}
				}
			}
		}
		return $this->sendError( 'Error!' );
	}

	public function sendTest(Request $request){
		try {
			$input = $request->all();
			$cid    = ! empty( $input['cid'] ) ? $input['cid'] : 0;
			$ctype = ! empty( $input['ctype'] ) ? $input['ctype'] : 0;
			$phone = ! empty( $input['phone'] ) ? $input['phone'] : 0;
			if($phone && $cid){
				$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($phone);
				if(!empty($formated_phone_number)){
					$can_send = false;
					if($ctype){ //automation
						$camp =  Automations::where('id', $cid)->first();
						if($camp && $camp->id){
							$camp_type = $camp->a_type;
							$can_send = true;
						}
					}
					else{ //campaign
						$camp =  Campaigns::where('id', $cid)->first();
						if($camp && $camp->id){
							$camp_type = $camp->c_type;
							$can_send = true;
						}
					}

					if($can_send){
						$sender = new SmsSender();
						if($camp_type){ // call
							$result = $sender->sendCall($formated_phone_number, 0, $cid, $ctype, 'test', $camp->is_call_amd_enabled, $camp->call_amd_timeout );
							$provider_response = '';
							if(!empty($result) && !empty($result['sid'])){
								$sid           = config( 'twilio.twilio.twilio_sid' );
								$token         = config( 'twilio.twilio.twilio_token' );
								$twilio        = new Client( $sid, $token );
								try {
									$calls = $twilio->calls($result['sid'])->fetch();
									if($calls){
										if($calls->status){
											$provider_response .= "<div class='small'>status: ".$calls->status.'</div>';
										}
										if($calls->to){
											$provider_response .= "<div class='small'>to: ".$calls->to.'</div>';
										}
										if($calls->from){
											$provider_response .= "<div class='small'>from: ".$calls->from.'</div>';
										}
									}
								}
								catch ( TwilioException $exception ) {
									$provider_response = "Can't get status.";
									if ( ! empty( $exception->getMessage() ) ) {
										$provider_response = $exception->getMessage();
									}
									if ( ! empty( $exception->getCode() ) ) {
										$provider_response .= " Error code: ".$exception->getCode() . ' ';
										// https://www.twilio.com/docs/api/errors
									}

								}
							}
						}
						else{ //sms
							$result = $sender->sendSMS($formated_phone_number, $camp->message, 0, $cid, $ctype, true, $camp->mediaUrl);
							$provider_response = '';
							if(!empty($result) && !empty($result['sid'])){
								$sid           = config( 'twilio.twilio.twilio_sid' );
								$token         = config( 'twilio.twilio.twilio_token' );
								$twilio        = new Client( $sid, $token );
								try {
									$calls = $twilio->messages($result['sid'])->fetch();
									if($calls){
										if($calls->status){
											$provider_response .= "<div class='small'>status: ".$calls->status.'</div>';
										}
										if($calls->to){
											$provider_response .= "<div class='small'>to: ".$calls->to.'</div>';
										}
										if($calls->from){
											$provider_response .= "<div class='small'>from: ".$calls->from.'</div>';
										}
									}

								}
								catch ( TwilioException $exception ) {
									$provider_response = "Can't get status.";
									if ( ! empty( $exception->getMessage() ) ) {
										$provider_response = $exception->getMessage();
									}
									if ( ! empty( $exception->getCode() ) ) {
										$provider_response .= " Error code: ".$exception->getCode() . ' ';
										// https://www.twilio.com/docs/api/errors
									}

								}
							}
						}
//						$result = array(
//							'sid' => '',
//							'error_code' => 654646
//						);
						if(!empty($result)){
							if(!empty($result['error_code'])){
								return $this->sendError( $result['error_code'] );
							}
							else{
								return $this->sendResponse( $provider_response, 'Success!' );
							}
						}
					}

				}
			}
			return $this->sendError( 'Something went wrong...' );
		}
		catch (Exception $ex){
			return $this->sendError( $ex->getMessage() );
		}
	}
}
