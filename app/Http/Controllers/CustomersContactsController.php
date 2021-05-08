<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use App\Customers;
use Illuminate\Support\Facades\Auth;
use App\CustomersContacts;
use App\CustomersContactSubscriptions;
use App\Http\Controllers\API\BaseController;
use App\Errors;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use Illuminate\Http\Request;
use Exception;

class CustomersContactsController extends CustomersController
{

	function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware( 'permission:customer-list|customer-create|customer-edit|customer-delete', [
			'only' => [
				'showContacts'
			]
		] );
	}

	public function showContacts(Request $request){
		try {
			$customer_id = !empty($request->input( 'customer_id' )) ? $request->input( 'customer_id' ) : 0;
			if($customer_id) {
				$query = CustomersContacts::with( 'subscriptions.user' )
				                    ->with( 'user' )
				                    ->where( 'customer_id', $customer_id )

				;
				return datatables()->eloquent( $query )->toJson();
			}
			return $this->sendError('No customer Id');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'showContacts'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	public function recheckSubscriptions(Request $request){
		try {
			$customer_id = !empty($request->input( 'customer_id' )) ? $request->input( 'customer_id' ) : 0;
			if($customer_id) {
				$user = Auth::user();
				$this->subscriptionsCheck($customer_id, $user->id );
				return $this->sendResponse( 'done' );
			}
			return $this->sendError('No customer Id');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'recheckSubscriptions'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	public function addContact(Request $request){
		try {
			$customer_id = !empty($request->input( 'customer_id' )) ? $request->input( 'customer_id' ) : 0;
			$phone_number = !empty($request->input( 'phone_number' )) ? strtolower(trim($request->input( 'phone_number' ))) : '';
			$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($phone_number);
			$email_address = !empty($request->input( 'email_address' )) ? strtolower(trim($request->input( 'email_address' ))) : '';

			if($customer_id) {
				$user = Auth::user();
				$contactData = [
					'customer_id' =>  $customer_id,
					'user_id' => $user->id
				];
				if($formated_phone_number){
					$if_phone_exist_global = CustomersContacts::where( 'contact_type', 1 )->where( 'formated_contact_term', $formated_phone_number )->first();
					if($if_phone_exist_global && $if_phone_exist_global->count()){
						if($if_phone_exist_global->customer_id == $customer_id){
							return $this->sendError( 'Phone number already exist.' );
						}
						$is_customer_exist = Customers::where('id',$if_phone_exist_global->customer_id)->count();// checking if deleted
						if($is_customer_exist) {
							return $this->sendError( 'Other <a target="_blank" href="/customers/' . $if_phone_exist_global->customer_id . '">customer</a> has the same phone.' );
						}
					}

					$contactData['contact_type']          = 1;
					$contactData['contact_term']          = $phone_number;
					$contactData['formated_contact_term'] = $formated_phone_number;
					$res                                  = CustomersContacts::create( $contactData );
					if ( $res ) {
						$user = Auth::user();
						ActionsLog::create( [
							'user_id'    => $user->id,
							'model'      => 8,
							'action'     => 0,
							'related_id' => $res->id
						] );
					}
					return $this->sendResponse( $res );

				}
				else{
					if($email_address){
						$if_email_exist_global = CustomersContacts::where( 'contact_type', 0 )->where( 'formated_contact_term', $email_address )->first();
						if($if_email_exist_global && $if_email_exist_global->count()){
							if($if_email_exist_global->customer_id == $customer_id){
								return $this->sendError( 'Email address already exist.' );
							}
							$is_customer_exist = Customers::where('id',$if_email_exist_global->customer_id)->count();// checking if deleted
							if($is_customer_exist) {
								return $this->sendError( 'Other <a target="_blank" href="/customers/' . $if_email_exist_global->customer_id . '">customer</a> has the same address' );
							}
						}

						$contactData['contact_type']          = 0;
						$contactData['contact_term']          = $email_address;
						$contactData['formated_contact_term'] = $email_address;
						$res                                  = CustomersContacts::create( $contactData );
						if ( $res ) {
							$user = Auth::user();
							ActionsLog::create( [
								'user_id'    => $user->id,
								'model'      => 8,
								'action'     => 0,
								'related_id' => $res->id
							] );
						}
						return $this->sendResponse( $res );

					}
					else{
						return $this->sendError('Please Enter value for the field.');
					}
				}

			}
			return $this->sendError('No customer Id');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'addContact'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
	public function deleteContact(Request $request){
		try {
			$this->validate($request, [
				'contact_id' => 'required'

			]);
			$contact = CustomersContacts::find( $request->input('contact_id' ));
			if ( ! $contact->is_main_for_invoice_id && ! $this->ifHaveSubscriptions( $contact ) ) {
				CustomersContacts::where( 'id', $contact->id )->delete();
				$user = Auth::user();
				ActionsLog::create([
					'user_id' => $user->id,
					'model' => 8,
					'action' => 2,
					'related_id' => $contact->id
				]);
				return $this->sendResponse('done');
			} else {
				return $this->sendError( 'Sorry you can not delete this contact' );
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'deleteContact'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	public function subscribe(Request $request){
		try {
			$subscription_type = !empty($request->input( 'subscription_type' )) ? $request->input( 'subscription_type' ) : null;
			$contact_id = !empty($request->input( 'contact_id' )) ? $request->input( 'contact_id' ) : 0;
			$contact = CustomersContacts::with('customer')->find($contact_id);
			if($contact && $contact->count() && isset($subscription_type)) {

				$first_name = $contact->customer->first_name;
				$last_name = $contact->customer->last_name;
				switch ( $subscription_type ) {
					case 2: // Klaviyo: Daily Prime
						$phone = $contact->customer->phone_number;
						$email = $contact->formated_contact_term;
						$res = $this->subscribeKlaviyo($email, $phone, $first_name, $last_name, false);
						if($res && $res['success']){
							$user = Auth::user();
							ActionsLog::create([
								'user_id' => $user->id,
								'model' => 8,
								'action' => 7,
								'related_id' => $contact->id
							]);
							$customer_id = CustomersContacts::where('id', $contact->id)->value('customer_id');
							$this->subscriptionsCheck($customer_id, $user->id);
							return response()->json( $res, 200 );
						}
						return response()->json($res, 404);
						break;
					case 3: // SMS System
					case 4:
						if($contact->contact_type){
							$phone = $contact->formated_contact_term;
							$email = '';
						}
						else{
							$phone = '';
							$email = $contact->formated_contact_term;
						}
						$res =  $this->subscribeSmsSystem($email, $phone, $first_name, $last_name, false);
						if($res && $res['success']){
							$user = Auth::user();
							ActionsLog::create([
								'user_id' => $user->id,
								'model' => 8,
								'action' => 9,
								'related_id' => $contact->id
							]);
							$customer_id = CustomersContacts::where('id', $contact->id)->value('customer_id');
							$this->subscriptionsCheck($customer_id, $user->id);
							return response()->json( $res, 200 );
						}
						return response()->json($res, 404);
						break;
				}
			}
			return $this->sendError('Error!');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'subscribe'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
	public function unsubscribe($subs_id){
		try {
			$subscription = CustomersContactSubscriptions::with('contact.customer')->find($subs_id);
			if($subscription && $subscription->count()) {
				switch ( $subscription->subscription_type ) {
					case 2: // Klaviyo: Daily Prime
						$res = $this->unsubscribeKlaviyo( $subscription->contact->formated_contact_term, false);
						if($res && $res['success']){
							$user = Auth::user();
							ActionsLog::create([
								'user_id' => $user->id,
								'model' => 8,
								'action' => 8,
								'related_id' => $subscription->contact->id
							]);
							$customer_id = CustomersContacts::where('id', $subscription->contact->id)->value('customer_id');
							$this->subscriptionsCheck($customer_id, $user->id);
							return response()->json( $res, 200 );
						}
						return response()->json($res, 404);
						break;
					case 3: // SMS System
					case 4:
						if($subscription->contact->contact_type){
							$emails = json_encode([]);
							$phones = json_encode([$subscription->contact->formated_contact_term]);
						}
						else{
							$emails = json_encode([$subscription->contact->formated_contact_term]);
							$phones = json_encode([]);
						}
						$res = $this->unsubscribeSmsSystem( $emails, $phones, false);
						if($res && $res['success']){
							$user = Auth::user();
							ActionsLog::create([
								'user_id' => $user->id,
								'model' => 8,
								'action' => 10,
								'related_id' => $subscription->contact->id
							]);
							$customer_id = CustomersContacts::where('id', $subscription->contact->id)->value('customer_id');
							$this->subscriptionsCheck($customer_id, $user->id);
							return response()->json( $res, 200 );
						}
						return response()->json($res, 404);
						break;
				}
			}
			return $this->sendError('Error!');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'unsubscribe'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
//
//	public function manageSmsSystem( $contact, $action = 'subscribe', $is_response_json = true ){
//		$user = Auth::user();
//		$ajaxData = [
//			'first_name' => $contact->customer->first_name,
//			'last_name' => $contact->customer->last_name,
//			'full_name' => $contact->customer->first_name . ' ' . $contact->customer->last_name,
//			'source' => 'portfolio-insider-prime',
//			'tags' => 'portfolio-insider-prime',
//			'token'   => 'PortInsQezInch111'
//		];
//		if($contact->contact_type){
//			$ajaxData['phone'] = $contact->contact_term;
//			$ajaxData['phones'] = json_encode([$contact->contact_term]);
//		}
//		else{
//			$ajaxData['email'] = $contact->contact_term;
//			$ajaxData['emails'] = json_encode([$contact->contact_term]);
//		}
//
//		$url = '';
//		$action_for_log = '';
//
//		switch($action) {
//			case 'subscribe':
//				$url = 'ulpi';
//				$action_for_log = 9;
//				break;
//			case 'unsubscribe':
//				$url = 'ungrancellead';
//				$action_for_log = 10;
//				break;
//		}
//
//		if($url && $action_for_log) {
//			$smssystem_res = $this->sendDataToSMSSystem( $ajaxData, $url );
//			if ( ! $smssystem_res['success'] ) {
//				$message = 'Error! Can\'t send data to SMS System';
//				if ( ! empty( $smssystem_res['message'] ) ) {
//					$message = $smssystem_res['message'];
//				}
//				return $this->sendError( $message, '', 404 , $is_response_json );
//			}
//			ActionsLog::create([
//				'user_id' => $user->id,
//				'model' => 8,
//				'action' => $action_for_log,
//				'related_id' => $contact->id
//			]);
//
//			$customer_id = CustomersContacts::where('id', $contact->id)->value('customer_id');
//			$this->subscriptionsCheck($customer_id, $user->id);
//			return $this->sendResponse( 'done' , '', $is_response_json);
//		}
//		return $this->sendError('Error!', '', 404 , $is_response_json);
//	}
//	public function manageKlaviyo( $contact, $action = 'subscribe', $is_response_json = true ){
//		$user = Auth::user();
//		switch($action) {
//			case 'subscribe':
//				$ajaxData = [
//					'phone' => $contact->customer->phone_number,
//					'email' => $contact->contact_term,
//					'first_name' => $contact->customer->first_name,
//					'last_name' => $contact->customer->last_name,
//					'full_name' => $contact->customer->first_name . ' ' . $contact->customer->last_name,
//					'source' => 'portfolio-insider-prime',
//					'tags' => 'portfolio-insider-prime',
//				];
//				$klaviyo_res = $this->sendDataToKlaviyo($ajaxData);
//				if ( ! $klaviyo_res['success'] ) {
//					$message = 'Error! Can\'t send data to SMS System';
//					if ( ! empty( $klaviyo_res['message'] ) ) {
//						$message = $klaviyo_res['message'];
//					}
//					return $this->sendError( $message, '', 404 , $is_response_json );
//				}
//				ActionsLog::create([
//					'user_id' => $user->id,
//					'model' => 8,
//					'action' => 7,
//					'related_id' => $contact->id
//				]);
//				$customer_id = CustomersContacts::where('id', $contact->id)->value('customer_id');
//				$this->subscriptionsCheck($customer_id, $user->id);
//				return $this->sendResponse( 'done', '',  $is_response_json );
//				break;
//			case 'unsubscribe':
//				$this->createKlaviyo();
//				if($this->klaviyo) {
//					$list_id = $this->klaviyo_listId;
//					$res = $this->klaviyo->lists->unsubscribeMembersFromList($list_id, $contact->contact_term);
//					if ( !$res ) {
//						ActionsLog::create([
//							'user_id' => $user->id,
//							'model' => 8,
//							'action' => 8,
//							'related_id' => $contact->id
//						]);
//						$customer_id = CustomersContacts::where('id', $contact->id)->value('customer_id');
//						$this->subscriptionsCheck($customer_id, $user->id);
//						return $this->sendResponse( 'done' , '',  $is_response_json);
//					}
//					$message = 'Can\'t delete Klaviyo user '.$contact->contact_term;
//					return $this->sendError( $message , '', 404 , $is_response_json);
//				}
//				$message = 'No Klaviyo API Key found';
//				return $this->sendError( $message , '', 404 , $is_response_json);
//				break;
//		}
//		return $this->sendError('Error!');
//	}
	public function ifHaveSubscriptions(CustomersContacts $contact){
		try {
			if ( $contact->contact_type == 1 ) { // phone
				$sms = $this->checkSmsSubsPhone( $contact->contact_term );
				if ( $sms && $sms['success'] && isset( $sms['data'] ) ) {
					$dataToSave      = [
						'customers_contact_id' => $contact->id,
						'user_id'              => 1,
						'subscription_type'    => 3,
						'subscription_status'  => $sms['data']
					];
					$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $contact->id )->where( 'subscription_type', 3 )->get();
					if ( $if_record_exist && $if_record_exist->count() ) {
						foreach ( $if_record_exist as $r ) {
							$dataToSave = [
								'user_id'             => 1,
								'subscription_status' => $sms['data']
							];
							CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSave );
						}
					} else {
						CustomersContactSubscriptions::create( $dataToSave );
					}

					return $sms['data'];
				}

				return true;
			} else {
				if ( ! $contact->contact_type ) { //email
					$sms = $this->checkSmsSubsEmail( $contact->contact_term );
					if ( $sms && $sms['success'] && isset( $sms['data'] ) ) {
						$dataToSave      = [
							'customers_contact_id' => $contact->id,
							'user_id'              => 1,
							'subscription_type'    => 4,
							'subscription_status'  => $sms['data']
						];
						$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $contact->id )->where( 'subscription_type', 4 )->get();
						if ( $if_record_exist && $if_record_exist->count() ) {
							foreach ( $if_record_exist as $r ) {
								$dataToSave = [
									'user_id'             => 1,
									'subscription_status' => $sms['data']
								];
								CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSave );
							}
						} else {
							CustomersContactSubscriptions::create( $dataToSave );
						}

						return $sms['data'];
					}

					return true;
				}
			}
			return false;
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'ifHaveSubscriptions'
			]);
			return $this->sendError($ex->getMessage());
		}
	}
	public function checkSmsSubsPhone($phone){
		$data = [
			'phone' => $phone,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data, 'stugelvichak');

	}
	public function checkSmsSubsEmail($email){
		$data = [
			'email' => $email,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data, 'stugelvichak');
	}


}
