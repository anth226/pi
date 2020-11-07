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

	public function sendEmailCampaign($c, $chunk){

		$leads_data = json_decode( $c->segment_info );
		if ( ! empty( $leads_data )) {
			$email_ids = false;
			$finish_id = 0;

			$chunk_size = $chunk;

			$data_to_update = [
				'started_at' => date( 'Y-m-d H:i:s' ),
				'status' => 2
			];

			$min_id = !empty($leads_data->added_min_id) ? $leads_data->added_min_id : 0;
			$max_id = !empty($leads_data->added_max_id) ? $leads_data->added_max_id : 0;
			$tags_included = !empty($leads_data->tags_included) ? $leads_data->tags_included : 0;
			$plus_new = !empty($leads_data->plus_new) ? $leads_data->plus_new : 0;
			$response_type = !empty($leads_data->response_type) ? $leads_data->response_type : 0;
			$last_days = !empty($leads_data->last_days) ? $leads_data->last_days : 0;

			if($c->last_email_id){ //if continue sending campaign from last lead id
				$min_id = $c->last_email_id*1;

				$data_to_update = [
					'status' => 2
				];
			}

			if(!empty($c->from_address_id)) {
				$em = new EmailTags();
				$em2 = new EmailTags();
				$res = $em->getSourcesUniqueLeadsLimited(
					$tags_included,
					false,
					$c->from_address_id,
					$min_id,
					$max_id,
					$chunk_size,
					$plus_new,
					$response_type,
					$last_days
				);
				$res2 = $em2->getSourcesUniqueLeadsLimited(
					$tags_included,
					false,
					$c->from_address_id,
					$min_id,
					$max_id,
					$chunk_size,
					$plus_new,
					$response_type,
					$last_days
				);
				if ( ! empty( $res )) {
					$finish = $res2->orderBy( 'u_emails.id', 'desc' )->first();
					$email_ids = $res->orderBy( 'u_emails.id', 'asc' )->get();
					if($finish && $finish->id){
						$finish_id = $finish->id;
					}
				}

			}

			EmailCampaigns::findOrFail( $c->id )->update( $data_to_update );
			$from_email = '';
			$from_name = '';
			$from = ProjectsEmails::getFromEmail($c->from_address_id)->first();
			if($from){
				$from_email= $from->email_address ? $from->email_address : '';
				$from_name= $from->email_name ? $from->email_name : '';
			}
			$last_leadId = $c->last_leadId ? $c->last_leadId : 0;

			$template ='vendor.maileclipse.templates.'.$c->template;

			$campaign_id = $c->id ? $c->id : 0;
			$from_address_id = $c->from_address_id ? $c->from_address_id : 0;
			$subject = $c->subject ? $c->subject :'';

			if ($from_email && $finish_id && $email_ids) {
				foreach ( $email_ids as $e ) {
					if ( $e->id) {
						$email_id = $e->id;
						$to = $e->email ? $e->email :'';
						$token = $e->token ? $e->token : '';

						$name = '';
						if($e->first_n){
							$name = $e->first_n;
						}
						else{
							if($e->full_n){
								$name = $e->full_n;
							}
						}

						$this->sendEmail(
							$to,
							$from_email,
							$campaign_id,
							$template,
							$subject,
							$token,
							$email_id,
							$from_name,
							$from_address_id,
							$name
						);

						$last_leadId = $e->id;
//						usleep(10000000);//10sec
//						usleep(250000);
						usleep(25000);
//						usleep(1000000);
					}
				}
				if ( $last_leadId == $finish_id ) {
					EmailCampaigns::finishCampaign($c->id, $last_leadId );
				} else {
					EmailCampaigns::pauseCampaign($c->id, $last_leadId );
				}
			} else {
				EmailCampaigns::finishCampaign($c->id, $last_leadId );
			}

		}


	}

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