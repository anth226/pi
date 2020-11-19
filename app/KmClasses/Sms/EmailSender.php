<?php
/**
 * Created by PhpStorm.
 * User: K
 * Date: 3/12/2019
 * Time: 1:49 PM
 */

namespace App\KmClasses\Sms;

use App\EmailCampaigns;
use App\EmailCampaignsStat;
use App\EmailLogs;
use App\EmailsResponses;
use App\EmailTags;
use App\EmailUnsubscribes;
use App\ProjectsEmails;
use Illuminate\Support\Facades\Mail;
use App\Exeptions;
use Exception;


class EmailSender {

	public function sendEmail(
					$to,
					$from_email,
					$campaign_id = 0,
					$template = '',
					$subject = '',
					$token = '',
					$email_id = 0,
					$from_name = '',
					$from_address_id = 0,
					$name = ''
	){
		$project_id =  ProjectsEmails::getProjectIdByFromAddressId($from_address_id);
		$project_id = $project_id ? $project_id : 0;
		$fingerprint = 'e='.$token.'&p='.$project_id.'&c='.$campaign_id;
		$app_url = config('app.url');
		$data = array();
		$data['tracking'] = 'utm_campaign=email&utm_content=email&utm_source=email&utm_term=email&'.$fingerprint;
		$data['tracking2'] = 'utm_campaign=emailleads&utm_content=emailleads&utm_source=emailleads&utm_term=email&'.$fingerprint;
		$data['name'] = $name;
		$data['email'] = $to;
		$data['pixel'] = "<img width='0' height='0'  alt='' src='".$app_url."/api/act?act=pxl&".$fingerprint."' />";
		$data['unsubscribe_url'] = $app_url."/unsubscribe_me?".$fingerprint;
		$data['token'] = $token;

		try {
			Mail::send( $template, $data, function ( $message ) use ($campaign_id, $to, $subject, $from_email, $from_name, $email_id, $project_id ) {
				$message->to( $to )->subject( $subject );
				$message->from( $from_email, $from_name );
				$swiftMessage = $message->getSwiftMessage();
				$headers = $swiftMessage->getHeaders();
				$headers->addTextHeader('msa-c', $campaign_id);
				$headers->addTextHeader('msa-e', $email_id);
				$headers->addTextHeader('msa-p', $project_id);
				$mailgun_params = [
					'msa-c' => $campaign_id,
					'msa-e' => $email_id,
					'msa-p' => $project_id
				];
				$headers->addTextHeader('X-Mailgun-Variables',json_encode($mailgun_params));
			} );
			if($email_id){
				EmailLogs::getFieldId($campaign_id,$email_id);
				if($campaign_id){
					EmailCampaignsStat::countSent($campaign_id);
				}
				if($project_id){
					EmailsResponses::countSent($email_id,$project_id);
				}
			}
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			$res = Exeptions::create( ['error' => $err, 'controller' => 'SmsSender', 'function' => 'sendEmail'] );
			$error_id = 0;
			if($res && $res->id){
				$error_id = $res->id;
			}
			if($email_id) {
				EmailLogs::getFieldId( $campaign_id, $email_id, 2, $error_id );
				if ( $campaign_id ) {
					EmailCampaignsStat::countErrors( $campaign_id );
				}
			}
			return array('success' => false, 'message' => $err);
		}
		return array('success' => true);

	}

	public static function unsubscribe($email_id, $project_id, $campaign_id = 0, $unsubscribed_by = 0){
		$res = EmailUnsubscribes::isAlreadyUnsubscribed($email_id, $project_id);
		if(!$res) {
			EmailUnsubscribes::getFieldId( $email_id, $project_id, $unsubscribed_by );
			if ( $campaign_id ) {
				EmailCampaignsStat::countUnsubscribes( $campaign_id );
			}
		}
		return $res;
	}

	public static function subscribe($email_id, $project_id, $campaign_id = 0){
		$id = EmailUnsubscribes::where('email_id', $email_id)->where('project_id', $project_id)->value('id');
		if($id) {
			EmailUnsubscribes::where('id', $id)->delete();
			if ( $campaign_id ) {
				EmailCampaignsStat::countUnsubscribeDecrement( $campaign_id );
			}
		}
		return true;
	}

}