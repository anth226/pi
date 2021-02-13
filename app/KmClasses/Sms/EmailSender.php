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
use App\Errors;
use App\ProjectsEmails;
use Illuminate\Support\Facades\Mail;
use App\Exeptions;
use Exception;


class EmailSender {

	public function sendEmail(
					$to,
					$bcc = '',
					$cc = '',
					$from_email,
					$template = '',
					$subject = '',
					$from_name = '',
					$name = '',
					$salesperson = '',
					$pathToFile = '',
					$customer_email = '',
					$mime = 'application/pdf'

	){
		$data = array();
		$data['name'] = $name;
		$data['email'] = $customer_email;
		$data['salesperson'] = $salesperson;

		try {
			Mail::send( $template, $data, function ( $message ) use ($to, $bcc, $cc, $subject, $from_email, $from_name, $pathToFile, $mime ) {
				$message->to( $to )->subject( $subject );
				if($bcc){
					$message->bcc($bcc);
				}
				if($cc){
					$message->cc($cc);
				}
				$message->from( $from_email, $from_name );
				if($pathToFile) {
					if(is_array($pathToFile)){
						if(count($pathToFile)){
							foreach($pathToFile as $p){
								$file_mime = !empty($p['mime']) ? $p['mime'] : $mime;
								if(!empty($p['filename'])) {
									$message->attach( $p['filename'], [ 'mime' => $file_mime ] );
								}
							}
						}
					}
					else {
						$message->attach( $pathToFile, [ 'mime' => $mime ] );
					}
				}
			} );
			return array('success' => true);
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Errors::create( ['error' => $err, 'controller' => 'EmailSender', 'function' => 'sendEmail'] );
			return array('success' => false, 'message' => $err);
		}


	}


}