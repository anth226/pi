<?php

namespace App\Http\Controllers\API;

use App\KmClasses\Sms\EmailSender;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\PopupSubscribers;
use App\ProjectsEmails;
use App\uEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Exeptions;
use Exception;
use Carbon\Carbon;


class LeadsSubscriptionController extends LeadsController
{
	public function store(Request $request)
	{
		try {
			$input = $request->all();
			$email = ! empty( $input['email'] ) ? $input['email'] : 0;
			$confirm_token = ! empty( $input['token'] ) ? $input['token'] : 0;
			if ( $email || $confirm_token) {
				$from_email      = !empty($input['url']) ? 'support@'.$input['url'] : '';
				$from_name       = !empty($input['sh_name']) ? $input['sh_name'] : '';
				$from_address_id = 0;
				$project_id      = 0;
				$token = '';

				if($email && $from_email && $from_name) {	// first email	(confirmation)
					$from            = ProjectsEmails::where( 'email_address', $from_email )->where( 'email_name', $from_name )->first();
					if ( isset( $from ) ) {
						if ( isset( $from->id ) ) {
							$from_address_id = $from->id;
						}
						if ( isset( $from->project_id ) ) {
							$project_id = $from->project_id;
						}
					}

					$popup_subs = PopupSubscribers::where( 'email', $email )->where( 'project_id', $project_id )->orderBy('id','desc')->first();
					if ($popup_subs && $popup_subs->count() ) {
						$dt  = Carbon::now();
						$dt2 = $popup_subs->updated_at;
						if ( $dt->diffInMinutes( $dt2 ) < 1 ) {
							return $this->sendError( 'Error! Too many clicks' );
						}
					}

					$token      = FormatUsPhoneNumber::generateRandomString();
					$popup_subs = PopupSubscribers::create([
						'email'      => $email,
						'token'      => $token,
						'project_id'      => $project_id,
						'from_address_id' => $from_address_id
					]);


				}
				else{
					if($confirm_token){ // second email (discount code)
						$popup_subs = PopupSubscribers::where('token', $confirm_token)->orderBy('id','desc')->first();
						if ( $popup_subs && $popup_subs->count() ) {
							$dt1 = Carbon::now();
							$dt2 = $popup_subs->updated_at;
							$dt =$dt1->diffInMinutes( $dt2 );
							if($popup_subs->is_confirmed) { //prevent sending email if previous click was lass then 3 min ago
								if ( $dt >= 3 ) {
									return $this->sendError( 'Error! Too many link clicks!' );
								}
							}
							else{
								if ( $dt >= 11520  ) { // 8 days
									return $this->sendError( 'Error! Expired link', [ 'The Link You Followed Has Expired' ] );
								}
							}
							$popup_subs->where('id', $popup_subs->id)->update(['is_confirmed' => 1]);
							if ( isset( $popup_subs->from_address_id ) ) {
								$from_address_id = $popup_subs->from_address_id;
								$from  = ProjectsEmails::where( 'id', $from_address_id )->first();
								if ( isset( $from ) ) {
									if ( isset( $from->id ) ) {
										$from_address_id = $from->id;
									}
									if ( isset( $from->project_id ) ) {
										$project_id = $from->project_id;
									}
								}
							}
							if ( isset( $popup_subs->email ) ) {
								$email = $popup_subs->email;
							}
						}
						else{
							return $this->sendError( 'Error! wrong token', ['Sorry, wrong data.'] );
						}
					}
				}



				$uemail = uEmails::select('u_emails.id', 'email_unsubscribes.email_id', 'u_emails.email_status')
				                 ->where( 'email', $email )
				                 ->leftJoin( 'email_unsubscribes', function ( $join ) {
										$join->on( 'email_unsubscribes.email_id', 'u_emails.id' );
									} )
				                 ->first();


				if ( isset( $uemail->id ) ) { // subscriber already exist
					if (isset( $uemail->email_id ) && $project_id) { // checking if unsubscribed
						$sender =  new EmailSender();
						if(!empty($popup_subs->is_confirmed)) {
							$sender::subscribe( $uemail->email_id, $project_id );
						}
						else{
							return $this->sendSubsEmail( $email, $uemail->email_id, $from_email, $from_name, $from_address_id, false, $token, $popup_subs->id);// sending email with confirmation link
						}
					}
					return $this->saveSubscriber($input, $from_email, $from_name, $from_address_id, true, $popup_subs->id); // sending discount code email (to existed user)
				}
				else{ // new subscriber
					if(!empty($popup_subs->is_confirmed)){
						return $this->saveSubscriber($input, $from_email, $from_name, $from_address_id, true, $popup_subs->id); // sending discount code email
					}
					return $this->sendSubsEmail( $email, 0, $from_email, $from_name, $from_address_id, false, $token, $popup_subs->id); // sending email with confirmation link
				}

			}

			return $this->sendError( 'Error! No email address.', ['404 Page Not Found'] );

		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'LeadsSubscriptionController', 'function' => 'store'] );
			return $this->sendError( $err );
		}

	}

	public function saveSubscriber($input, $from_email = '', $from_name = '', $from_address_id = 0, $is_confirmed = true, $ps_id = 0){
		$res = $this->saveLead( $input );
		if ( $res ) {
			$lead = json_decode( $res->content() );
			if ( isset( $lead ) && isset( $lead->data ) ) {
				return $this->sendSubsEmail( $lead->data->email, $lead->data->email_id, $from_email, $from_name, $from_address_id, $is_confirmed, '', $ps_id);
			}
		}
		return $this->sendError( 'Error! Can\'t save lead to db'  );
	}

	public function sendSubsEmail($to_email = 0, $email_id = 0, $from_email = '', $from_name= '', $from_address_id = 0, $is_confirmed = true, $token = '',$ps_id = 0){
		$sender =  new EmailSender();
		$campaign_id    = 0;
		if($to_email && $from_email) {
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

				$template ='vendor.maileclipse.templates.subscriptionConfirmation';
				$subject  = 'Please confirm your subscription';
				if($is_confirmed) {
					$template = 'vendor.maileclipse.templates.subscription';
					$subject  = 'Here\'s Your 10% Off Discount Code!';
				}

//				Config::set("mail.driver", "ses");
//				(new \Illuminate\Mail\MailServiceProvider(app()))->register();

				$res = $sender->sendEmail(
					$to_email,
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
						$dataToUpdate = $is_confirmed ?  ['is_discount_sent' => 1] : ['is_conf_link_sent' => 1];
						if($ps_id) {
							PopupSubscribers::where( 'id', $ps_id )->update( $dataToUpdate );
						}
						return $this->sendResponse( ' Message has been sent', 'Success!' );
					}
					else{
						$dataToUpdate = $is_confirmed ?  ['is_discount_sent' => 3] : ['is_conf_link_sent' => 3];
						if($ps_id) {
							PopupSubscribers::where( 'id', $ps_id )->update( $dataToUpdate );
						}
						if($res['message']){
							return $this->sendError( $res['message'] );
						}
					}
				}
		}
		return $this->sendError( 'Error!' );
	}


}
