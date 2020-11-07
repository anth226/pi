<?php

namespace App\Http\Controllers\API;

use App\EmailCampaignsStat;
use App\EmailUnsubscribes;
use App\KmClasses\Sms\EmailSender;
use App\uEmails;
use Validator, Input, Exception;
use App\Exeptions;

class EmailsFeedbackMailgunController extends EmailsFeedbackController
{
	public function processingRequest($request){

		try {
			$content = $request->getContent();
//			Exeptions::create( ['error' =>$content, 'controller' => 'EmailsFeedback', 'function' => 'processingRequest error'] );

			$data = json_decode( $content, 1 );
			if(
				!empty($data) &&
				!empty($data['event-data']) &&
				!empty($data['signature']) &&
				!empty($data['signature']['timestamp'])&&
				!empty($data['signature']['token'])&&
				!empty($data['signature']['signature'])
			){
				if(!empty($data['event-data']['user-variables'])) {
					$campaign_id = 0;
					$email_id = 0;
					$project_id = 0;

					if(!empty($data['event-data']['user-variables']['msa-c'])){
						$campaign_id = $data['event-data']['user-variables']['value'];
					}
					if(!empty($data['event-data']['user-variables']['msa-e'])){
						$email_id = $data['event-data']['user-variables']['msa-e'];
					}
					if(!empty($data['event-data']['user-variables']['msa-p'])){
						$project_id = $data['event-data']['user-variables']['msa-p'];
					}


					if($email_id && $project_id && !empty($data['event-data']['event'])) {
						switch ( $data['event-data']['event'] ) {
							case 'delivered':
								if($campaign_id) {
									EmailCampaignsStat::countDelivered( $campaign_id );
								}
								uEmails::setActive($email_id);
								break;
							case 'complained':
								$res = EmailUnsubscribes::where('email_id', $email_id)
								                        ->where('project_id', $project_id)
								                        ->where('unsubscribed_by', 2)
								                        ->count();
								if(!$res) {
									if($campaign_id) {
										EmailCampaignsStat::countComplaints( $campaign_id );
									}
									EmailSender::unsubscribe( $email_id, $project_id, 0, 2 );
								}
								break;
							case 'failed':
								$isHard = false;
								if( $data['event-data']['severity']) {
									if($data['event-data']['severity'] == 'permanent') {
										if($campaign_id) {
											EmailCampaignsStat::countBouncesPermanent( $campaign_id );
										}
										$isHard = true;
									}
									else{
										if($data['bounce']['bounceType'] == 'temporary'){
											if($campaign_id) {
												EmailCampaignsStat::countBouncesTransient( $campaign_id );
											}
										}
									}
									uEmails::setBounced($email_id, $isHard);
								}
								break;
						}
					}
				}
			}
			return $this->sendResponse( $content, '' );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'EmailsFeedbackMailgunController', 'function' => 'processingRequest error'] );
			return $this->sendError( 'Error');
		}
	}

	protected function veryfyClickMartMG($token, $timestamp, $signature){
		$signingKey = '01da6aace9e30034b6c65edc782113a9-fd0269a6-1b531f4f';
		return $this->verify($signingKey, $token, $timestamp, $signature);
	}

	public function verify($signingKey, $token, $timestamp, $signature)
	{
		// check if the timestamp is fresh
//		if (\abs(\time() - $timestamp) > 15) {
//			return false;
//		}

		// returns true if signature is valid
		return \hash_equals(\hash_hmac('sha256', $timestamp . $token, $signingKey), $signature);
	}
}
