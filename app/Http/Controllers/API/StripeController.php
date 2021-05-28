<?php

namespace App\Http\Controllers\API;

use App\ActionsLog;
use App\Http\Controllers\CustomersController;
use App\Invoices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Errors;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends BaseController
{
    public function stripe(Request $request){
	    // Set your secret key. Remember to switch to your live secret key in production.
		// See your keys here: https://dashboard.stripe.com/apikeys
	    Stripe::setApiKey(config( 'stripe.webhook_secret' ));

	    $payload = @file_get_contents('php://input');
	    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
	    $endpoint_secret = config( 'stripe.endpointSecret' );
	    $event = null;

	    try {
		    $event = Webhook::constructEvent(
			    $payload, $sig_header, $endpoint_secret
		    );
	    } catch(SignatureVerificationException $e) {
		    // Invalid payload
		    http_response_code(400);
		    exit();
	    }

		// Handle the event
	    switch ($event->type) {
		    case 'customer.subscription.updated':
		    case 'customer.subscription.deleted':
		    case 'customer.subscription.pending_update_expired':
		    case 'customer.subscription.pending_update_applied':
			    $customerSubscription = $event->data->object;
			    try {
//					Storage::put('file1.txt', json_encode($customerSubscription));
				    if(!empty($customerSubscription) && !empty($customerSubscription->id)){
				    	return $this->updateSubscription($customerSubscription);
				    }
				    else{
					    $err_mess = 'No subscription Id';
				    }
				    return $this->sendError($err_mess);
			    } catch(Exception $ex) {
				    $error = $ex->getMessage();
				    Errors::create([
					    'error' => $error,
					    'controller' => 'API/StripeController',
					    'function' => 'stripe'
				    ]);
				    return $this->sendError($error);
			    }
			    break;

		    // ... handle other event types
		    default:
			    $message = 'Received unknown event type ' . $event->type;
			    return $this->sendResponse([], $message);
	    }

    }


    public function updateSubscription($subscription){
    	try {
		    $invoice  = Invoices::where( 'stripe_subscription_id', $subscription->id );
		    $is_exist = $invoice->count();
		    if ( $is_exist ) {
			    $dataToUpdate = [];
			    if ( ! empty( $subscription->status ) ) {
				    $status_id = Invoices::STRIPE_STATUSES[ $subscription->status ];
				    if ( $status_id ) {
					    $dataToUpdate['stripe_subscription_status'] = $status_id;
					    if ( ! empty( $subscription->current_period_start ) ) {
						    $dataToUpdate['stripe_current_period_start'] = date( "Y-m-d H:i:s", $subscription->current_period_start );
					    }
					    if ( ! empty( $subscription->current_period_end ) ) {
						    $dataToUpdate['stripe_current_period_end'] = date( "Y-m-d H:i:s", $subscription->current_period_end );
					    }
					    $invoice->update( $dataToUpdate );
					    if(Invoices::STRIPE_STATUSES[ $subscription->status ] != 1 || Invoices::STRIPE_STATUSES[ $subscription->status ] != 11) { //not active or trialing
					        $in = Invoices::where( 'stripe_subscription_id', $subscription->id )->with('customer.contacts')->first();
					        if($in && $in->count() && !empty($in->customer)){
                                $this->unsunscribeEmailsAndPhones($in->customer);
                            }
                        }
					    return $this->sendResponse($subscription, 'done');
				    } else {
					    $err_mess = 'Can not get subscription status for ' . $subscription->id;
				    }
			    } else {
				    $err_mess = 'No subscription status for ' . $subscription->id;
			    }
			    return $this->sendError( $err_mess, $subscription);
		    }
		    return $this->sendResponse( $subscription, "Nothing to Update, We don't have the invoice for " . $subscription->id );
	    }
	    catch(Exception $ex) {
		    $error = $ex->getMessage();
		    Errors::create([
			    'error' => $error,
			    'controller' => 'API/StripeController',
			    'function' => 'updateSubscription'
		    ]);
		    return $this->sendError($error);
	    }
    }

    public function unsunscribeEmailsAndPhones($customer){
        try{
            $user_id = 41; // Stripe API Webhook
            $cc = new CustomersController();
            $phones = [];
            $emails = [];
            $emailToContact = [];
            $contacts = $customer->contacts;
            if($contacts && $contacts->count()){
                foreach($contacts as $contact){
                    if($contact->contact_type){
                        $phones[] = $contact->formated_contact_term;
                    }
                    else{
                        $emails[] = $contact->formated_contact_term;
                        $emailToContact[$contact->formated_contact_term] = $contact->id;
                    }
                }
            }

            if($emails && count($emails)){
                foreach($emails as $email) {//
                    // unsubscribe klavio customer
                    $res_klaviyo = $cc->unsubscribeKlaviyo($email, false);
                    if (!$res_klaviyo || !$res_klaviyo['success']) {
                        $error = "Can not unsubscribe " . $email . " from Klaviyo. ";
                        Errors::create([
                            'error' => $error,
                            'controller' => 'API/StripeController',
                            'function' => 'unsunscribeEmailsAndPhones'
                        ]);
                    } else {
                        ActionsLog::create([
                            'user_id' => $user_id,
                            'model' => 8,
                            'action' => 8,
                            'related_id' => $emailToContact[$email] //contact_id
                        ]);
                    }
                }

                $res_smssystem = $cc->unsubscribeSmsSystem(json_encode($emails), json_encode($phones), false);
                if(!$res_smssystem || !$res_smssystem['success']){
                    $error = !empty($res_smssystem['message']) ? $res_smssystem['message'] : "Can not unsubscribe from SMS System";
                    Errors::create([
                        'error' => $error,
                        'controller' => 'API/StripeController',
                        'function' => 'unsunscribeEmailsAndPhones'
                    ]);
                }
                else{
                    ActionsLog::create([
                        'user_id' => $user_id,
                        'model' => 2,
                        'action' => 10,
                        'related_id' => $customer->id
                    ]);
                }
            }

            $cc->subscriptionsCheck($customer->id, 1);

        }
        catch (Exception $ex){
            $err_message = $ex->getMessage();
            $errors[] = $err_message;
            Errors::create([
                'error' => $err_message,
                'controller' => 'API/StripeController',
                'function' => 'unsunscribeEmailsAndPhones'
            ]);
            return $this->sendError( $err_message, $errors, 404, false );
        }
    }
}
