<?php

namespace App\Http\Controllers;

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
					$if_phone_exist = CustomersContacts::where( 'customer_id', $customer_id )->where( 'contact_type', 1 )->where( 'formated_contact_term', $formated_phone_number )->count();
					if ( ! $if_phone_exist ) {
						$contactData['contact_type']          = 1;
						$contactData['contact_term']          = $phone_number;
						$contactData['formated_contact_term'] = $formated_phone_number;
						$res = CustomersContacts::create( $contactData );
						return $this->sendResponse($res);
					}
					else{
						return $this->sendError('Phone number already exist.');
					}
				}
				else{
					if($email_address){
						$if_email_exist = CustomersContacts::where('customer_id', $customer_id)->where('contact_type', 0)->where('formated_contact_term', $email_address)->count();
						if(!$if_email_exist){
							$contactData['contact_type'] = 0;
							$contactData['contact_term'] = $email_address;
							$contactData['formated_contact_term'] = $email_address;
							$res = CustomersContacts::create($contactData);
							return $this->sendResponse($res);
						}
						else{
							return $this->sendError('Email address already exist.');
						}
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

	public function unsubscribeFromSmsSystem($subs_id){
		try {
			$contact = CustomersContactSubscriptions::with('contact')->find($subs_id);
			return $this->sendResponse($contact);

//			return $this->sendError('Error!');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersContactsController',
				'function' => 'unsubscribeFromSmsSystem'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

	public function unsubscribeNumberFromSmsSystem($phone){

	}

	public function unsubscribeEmailFromSmsSystem($email){

	}

	public function ifHaveSubscriptions(CustomersContacts $contact){
		if($contact->contact_type == 1){ // phone
			$sms = $this->checkSmsSubsPhone( $contact->contact_term );
			if ( $sms && $sms['success'] && isset( $sms['data'] ) ) {
				$dataToSave = [
					'customers_contact_id' => $contact->id,
					'user_id'              => 1,
					'subscription_type'    => 3,
					'subscription_status'  => $sms['data']
				];
				$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $contact->id )->where( 'subscription_type', 3)->get();
				if($if_record_exist && $if_record_exist->count()){
					foreach($if_record_exist as $r) {
						$dataToSave = [
							'user_id'              => 1,
							'subscription_status'  => $sms['data']
						];
						CustomersContactSubscriptions::where('id', $r->id)->update($dataToSave);
					}
				}
				else {
					CustomersContactSubscriptions::create( $dataToSave );
				}
				return $sms['data'];
			}
			return true;
		}
		else{
			if(!$contact->contact_type){ //email
				$sms = $this->checkSmsSubsEmail( $contact->contact_term );
				if ( $sms && $sms['success'] && isset( $sms['data'] )) {
					$dataToSave = [
						'customers_contact_id' => $contact->id,
						'user_id'              => 1,
						'subscription_type'    => 4,
						'subscription_status'  => $sms['data']
					];
					$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $contact->id )->where( 'subscription_type', 4)->get();
					if($if_record_exist && $if_record_exist->count()) {
						foreach ( $if_record_exist as $r ) {
							$dataToSave = [
								'user_id'              => 1,
								'subscription_status'  => $sms['data']
							];
							CustomersContactSubscriptions::where('id', $r->id)->update($dataToSave);
						}
					}
					else {
						CustomersContactSubscriptions::create( $dataToSave );
					}
					return $sms['data'];
				}
				return true;
			}
		}
		return false;
	}

	public function checkSmsSubsPhone($phone){
		$data = [
			'phone' => $phone,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data);

	}
	public function checkSmsSubsEmail($email){
		$data = [
			'email' => $email,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data);
	}

	public function deleteContact($contact_id){
		$contact = CustomersContacts::find($contact_id);
		if(!$contact->is_main_for_invoice_id && !$this->ifHaveSubscriptions($contact)){

		}
		else{
			return $this->sendError('Sorry you can not delete this contact');
		}
	}
}
