<?php

namespace App\Http\Controllers\API;

use App\KmClasses\Sms\EmailSender;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\PopupSubscribers;
use App\ProjectsEmails;
use App\uEmails;
use Illuminate\Http\Request;


class LeadsSubscriptionNoConfirmController extends LeadsController
{
	public function store(Request $request)
	{
		$input = $request->all();
		$res = $this->saveLead($input);
		if($res){
			$lead = json_decode($res->content());
			if(isset($lead) && isset($lead->data)){
				return $this->sendSubsEmail($lead->data, $input);
			}

		}
		return $res;
	}

	public function sendSubsEmail($lead, $input){
		$sender =  new EmailSender();
		$campaign_id    = 0;
		$to = ! empty( $lead->email ) ? $lead->email : 0;
		if($to) {
			$from_email = '';
			$from_name = '';
			$token = '';
			$email_id = $lead->email_id ? $lead->email_id : 0;
			if($email_id){
				$email_obj = uEmails::where('id', $email_id)->first();
				if(isset($email_obj) && isset($email_obj->unsubscribe_token)){
					$token = $email_obj->unsubscribe_token;
				}
			}
			if(isset($input) && isset($input['url']) && isset($input['sh_name'])){
				$from_email = 'support@'.$input['url'];
				$from_name = $input['sh_name'];
			}
			$from_address_id = 0;
			$from = ProjectsEmails::where('email_address', $from_email)->where('email_name', $from_name)->first();
			if(isset($from) && isset($from->id)){
				$from_address_id = $from->id;
			}
			$template ='vendor.maileclipse.templates.subscription';
			$subject = 'Here\'s Your 10% Off Discount Code!';

//				Config::set("mail.driver", "ses");
//				(new \Illuminate\Mail\MailServiceProvider(app()))->register();

			$res = $sender->sendEmail(
				$to,
				$from_email,
				$campaign_id,
				$template,
				$subject,
				$token,
				$email_id,
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
		return $this->sendError( 'Error!' );
	}
}
