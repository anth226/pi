<?php

namespace App\Http\Controllers\API;

use App\EmailCampaignsStat;
use App\EmailUnsubscribes;
use App\KmClasses\Sms\EmailSender;
use App\uEmails;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;
use App\Exeptions;

class EmailsFeedbackController extends BaseController
{
	public function store(Request $request)
	{
		$this->processingRequest($request);
	}
	public function index(Request $request){
		$this->processingRequest($request);
	}

	public function processingRequest($request){
//		$content = '{"notificationType":"Bounce","bounce":{"bounceType":"Permanent","bounceSubType":"General","bouncedRecipients":[{"emailAddress":"bounce@simulator.amazonses.com","action":"failed","status":"5.1.1","diagnosticCode":"smtp; 550 5.1.1 user unknown"}],"timestamp":"2020-01-31T19:10:21.701Z","feedbackId":"0100016ffd02df6c-e1762b97-7316-45bc-bfce-44b17e072e5e-000000","remoteMtaIp":"35.170.23.22","reportingMTA":"dsn; a8-84.smtp-out.amazonses.com"},"mail":{"timestamp":"2020-01-31T19:10:21.000Z","source":"support@magicstarsystem.com","sourceArn":"arn:aws:ses:us-east-1:804017507103:identity/support@magicstarsystem.com","sourceIp":"45.49.18.151","sendingAccountId":"804017507103","messageId":"0100016ffd02dd48-0da65f8b-a644-4db6-9284-d6375b3756e9-000000","destination":["bounce@simulator.amazonses.com"],"headersTruncated":false,"headers":[{"name":"Message-ID","value":"<cf518941e31866315cf4b159fdc02108@sms.local>"},{"name":"Date","value":"Fri, 31 Jan 2020 11:10:19 -0800"},{"name":"Subject","value":"test555"},{"name":"From","value":"Magicstar <support@magicstarsystem.com>"},{"name":"To","value":"bounce@simulator.amazonses.com"},{"name":"MIME-Version","value":"1.0"},{"name":"Content-Type","value":"text/html; charset=utf-8"},{"name":"Content-Transfer-Encoding","value":"quoted-printable"}],"commonHeaders":{"from":["Magicstar <support@magicstarsystem.com>"],"date":"Fri, 31 Jan 2020 11:10:19 -0800","to":["bounce@simulator.amazonses.com"],"messageId":"<cf518941e31866315cf4b159fdc02108@sms.local>","subject":"test555"}}}';

//		$data = json_decode( $content, 1 );
//		echo "<pre>";
//		var_export($data);
//		echo "</pre>";
//
//		die;

		try {
			$content = $request->getContent();
//			Exeptions::create( ['error' =>$content, 'controller' => 'EmailsFeedback', 'function' => 'processingRequest error'] );


			$data = json_decode( $content, 1 );
			if(!empty($data) && !empty($data['notificationType'])){
				if(!empty($data['mail']) && !empty($data['mail']['headers'])) {
					$campaign_id = 0;
					$email_id = 0;
					$headers = $data['mail']['headers'];
					if(count($headers)){
						foreach($headers as $h){
							if(!empty($h['name']) && !empty($h['value'])){
								if($h['name'] == 'msa-c') {
									$campaign_id = $h['value'];
								}
								if($h['name'] == 'msa-e') {
									$email_id = $h['value'];
								}
								if($h['name'] == 'msa-p') {
									$project_id = $h['value'];
								}
							}
						}
					}
					if($email_id && $project_id) {
						switch ( $data['notificationType'] ) {
							case 'Delivery':
								if($campaign_id) {
									EmailCampaignsStat::countDelivered( $campaign_id );
								}
								uEmails::setActive($email_id);
								break;
							case 'Complaint':
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
							case 'Bounce':
								$isHard = false;
								if(
									!empty($data['bounce']) &&
									!empty($data['bounce']['bounceType'])
								) {
									if($data['bounce']['bounceType'] == 'Permanent') {
										if($campaign_id) {
											EmailCampaignsStat::countBouncesPermanent( $campaign_id );
										}
										$isHard = true;
									}
									else{
										if($data['bounce']['bounceType'] == 'Transient'){
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
			Exeptions::create( ['error' => $err, 'controller' => 'EmailsFeedback', 'function' => 'processingRequest error'] );
			return $this->sendError( 'Error');
		}
	}
}
