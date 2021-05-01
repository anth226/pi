<?php

namespace App\Http\Controllers;

use App\Customers;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\InvoicesLog;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\PipedriveData;
use App\SentData;
use App\Strings;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Kreait\Firebase\Factory;
use Klaviyo\Klaviyo as Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;
use Validator;
use Exception;
use Illuminate\Support\Facades\Auth;



class CustomersController extends BaseController
{
	protected $stripe, $firebase, $klaviyo, $klaviyo_listId, $smssystem;
	function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware( 'permission:customer-list|customer-create|customer-edit|customer-delete', [
			'only' => [
				'index',
				'show'
			]
		] );
		$this->middleware( 'permission:customer-create', [ 'only' => [ 'create', 'store' ] ] );
		$this->middleware( 'permission:customer-edit', [ 'only' => [ 'edit', 'update' ] ] );
		$this->middleware( 'permission:customer-delete', [ 'only' => [ 'destroy' ] ] );
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$customers = Customers::with('invoices')->with('invoices.salespersone')->with('invoices.salespeople.salespersone')->orderBy('customers.id','DESC')->paginate(10);
//		dd($customers->toArray());
		return view('customers.index',compact('customers'))
			->with('i', ($request->input('page', 1) - 1) * 10);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		$states = UsStates::statesUS();
		$test_mode = !empty($request->input('test_mode')) ? $request->input('test_mode') : 0;
		return view('customers.create', compact('states', 'test_mode'));
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'required|max:120',
			'address_1' => 'required|max:120',
			'zip' => 'required|max:120',
			'city' => 'required|max:120',
			'state' => 'required||max:20',
			'email' => 'required|unique:customers,email,NULL,id,deleted_at,NULL|email|max:120',
			'phone_number' => 'required|max:120|min:10',

		]);


		$customer = Customers::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'address_1' => $request->input('address_1'),
			'address_2' => !empty($request->input('address_2')) ? $request->input('address_2') : '',
			'city' => $request->input('city'),
			'state' => $request->input('state'),
			'zip' => $request->input('zip'),
			'email' => $request->input('email'),
			'phone_number' => $request->input('phone_number'),
			'formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')),
		]);

		$dataToSend = [
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'full_name' => $request->input('first_name').' '.$request->input('last_name'),
			'email' => $request->input('email'),
			'phone' => $request->input('phone_number'),
			'source' => 'portfolioinsider',
			'tags' => 'portfolioinsider,portfolio-insider-prime'
		];

		if(config('app.env') == 'production') {
			$this->sendDataToSMSSystem( $dataToSend );
		}


		return redirect()->route('customers.show', ['customer_id' => $customer->id])
		                 ->with('success','Customer created successfully');


	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$customer = Customers::with('invoices')->find($id);
		$sentLog = SentData::where('customer_id', $id)->orderBy('id', 'asc')->get();

		if($customer) {
			return view( 'customers.show', compact( 'customer', 'sentLog' ) );
		}
		return abort(404);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$customer = Customers::find($id);
		if($customer) {
			$states        = UsStates::statesUS();
			$customerState = $customer->state;

			return view( 'customers.edit', compact( 'customer', 'states', 'customerState' ) );
		}
		return abort(404);
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'required|max:120',
			'address_1' => 'required|max:120',
			'zip' => 'required|digits:5',
			'city' => 'required|max:120',
			'state' => 'required||max:20',
			'phone_number' => 'required|max:120|min:10'
		]);

		$customer = Customers::find($id);
		$customer->first_name = $request->input('first_name');
		$customer->last_name =  $request->input('last_name');
		$customer->address_1 = $request->input('address_1');
		$customer->address_2 =  !empty($request->input('address_2')) ? $request->input('address_2') : '';
		$customer->zip = $request->input('zip');
		$customer->state = $request->input('state');
		$customer->phone_number = $request->input('phone_number');
		$customer->formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'));
		$customer->save();

		return redirect()->route('customers.index')
		                 ->with('success','Customer updated successfully');
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		Customers::where('id',$id)->delete();
		Invoices::where('customer_id', $id)->delete();
		return redirect()->route('customers.index')
		                 ->with('success','Customer deleted successfully');
	}


	public function sendDataToSMSSystem($input, $action_path = 'ulpi'){
		try {
			$this->createSMSsystem();
			if($this->smssystem) {
				$url      = $this->smssystem.$action_path;
				$postvars = http_build_query( $input );
				$ch       = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_POST, count( $input ) );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $postvars );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				$res = curl_exec( $ch );
				curl_close( $ch );
				if ( $res ) {
					$result = json_decode( $res );
					if ( $result && ! empty( $result->success ) && $result->success && ! empty( $result->data ) ) {
						return $this->sendResponse( $result->data, '', false );
					} else {
						$error = "Wrong response from " . $url;
						if ( $result && ! empty( $result->success ) && ! $result->success && ! empty( $result->message ) ) {
							$error = $result->message;
						}
						Errors::create( [
							'error'      => $error,
							'controller' => 'CustomersController',
							'function'   => 'sendDataToSMSSystem'
						] );

						return $this->sendError( $error, [], 404, false );
					}
				} else {
					$error = "No response from " . $url;
					Errors::create( [
						'error'      => $error,
						'controller' => 'CustomersController',
						'function'   => 'sendDataToSMSSystem'
					] );

					return $this->sendError( $error, [], 404, false );
				}
			}
			$error = "No SMS System Url found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToStripe'
			]);
			return $this->sendError($error, [], 404, false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendLead'
			]);
			return $this->sendError($error, [], 404, false);
		}
	}


	public function createStripeCustomer($input){
		try{
			$stripe = $this->stripe;
			$customer = $stripe->customers->create([
				'name' => $input['full_name'],
				'email' => $input['email'],
				'phone' => $input['phone'],
			]);
			return $this->sendResponse($customer->id, '', false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createStripeCustomer'
			]);
			return $this->sendError($error, [], 404, false);
		}
	}
	public function createStripeSubscription($customer_id){
		try{
			$data = [
				'customer' => $customer_id,
				'coupon' => config('stripe.coupon'),
				'trial_from_plan' => true,
			];
			if(config('stripe.price')){
				$data['items'] = [
					['price' => config('stripe.price')]
				];
			}
			if(config('stripe.coupon')){
				$data['coupon'] = config('stripe.coupon');
			}
			$subscription = $this->stripe->subscriptions->create($data);
			return $this->sendResponse($subscription, '', false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createStripeSubscription'
			]);
			return $this->sendError($error, [], 404, false);
		}
	}

	public function sendDataToStripe($input){
		try {
			$this->createStripe();
			if($this->stripe) {
				$res = $this->createStripeCustomer( $input );
				if ( $res && $res['success'] && ! empty( $res['data'] ) ) {
					return $this->createStripeSubscription( $res['data'] );
				}
				return $res;
			}
			$error = "No Stripe API Key found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToStripe'
			]);
			return $this->sendError($error, [], 404, false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToStripe'
			]);
			return $this->sendError($error, [], 404, false);
		}
	}

	public function sendDataToFirebase($user, $collection = 'users') {
		try {
			$this->createFirebase();
			if($this->firebase) {
				$auth           = $this->firebase->createAuth();
				$userProperties = [
					'email'         => $user['email'],
					'password'      => 'warrenbuffett1',
					'emailVerified' => false,
					'disabled'      => false,
					'metadata'      => [
						'lastSignInDate' => date( 'D M d Y H:i:s O' ),
					],
				];
				$createdUser    = $auth->createUser( $userProperties );
				if ( $createdUser && $createdUser->uid ) {
					$firestore = $this->firebase->createFirestore();
					$database  = $firestore->database();
					$data      = [
						'firstName'          => $user['first_name'],
						'lastName'           => $user['last_name'],
						'email'              => $user['email'],
						'phoneNumber'        => $user['phone'],
						'userId'             => $createdUser->uid,
						'customerId'         => $user['customerId'],
						'subscriptionId'     => $user['subscriptionId'],
						'isPrime'            => true,
						'subscriptionStatus' => "active",
					];
					$database->collection( $collection )->document( $createdUser->uid )->set( $data );

					$auth->setCustomUserClaims( $createdUser->uid, [
						'customer_id'     => $user['customerId'],
						'subscription_id' => $user['subscriptionId'],
					] );
				}

				return $this->sendResponse( $createdUser, '', false );
			}
			$error = "No Firebase API Key found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToStripe'
			]);
			return $this->sendError($error, [], 404, false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToFirebase'
			]);
			return $this->sendError($error, [], 404, false);
		}

	}

	public function sendDataToKlaviyo($input, $list_id = ''){
		try {
			$this->createKlaviyo();
			if($this->klaviyo && $this->klaviyo_listId) {
				if ( ! $list_id ) {
					$list_id = $this->klaviyo_listId;
				}
				$klaviyo_data = [
					'$email'        => $input['email'],
					'$phone_number' => $input['phone'],
					'$first_name'   => $input['first_name'],
					'$last_name'    => $input['last_name'],
				];
				$profile      = new KlaviyoProfile( $klaviyo_data );
				$res          = $this->klaviyo->lists->addMembersToList( $list_id, [ $profile ] );

				return $this->sendResponse( $res, '', false );
			}
			$error = "No Klaviyo API Key found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToStripe'
			]);
			return $this->sendError($error, [], 404, false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'sendDataToKlaviyo'
			]);
			return $this->sendError($error, [], 404, false);
		}
	}

	public function getFirebaseUser($value, $by = 'uid'){
		try {
			$this->createFirebase();
			if($this->firebase) {
				$auth = $this->firebase->createAuth();
				switch($by){
					case 'email':
						return $this->sendResponse($auth->getUserByEmail($value));
					case 'phone':
						return $this->sendResponse($auth->getUserByPhoneNumber($value));
					case 'uid':
					default:
						return $this->sendResponse($auth->getUser( $value ));
				}

			}
			$error = "Wrong Firebase credentials or connection issue";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseUser'
			]);
			return $this->sendError($error);
		} catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseUser'
			]);
			return $this->sendError($error);
		}

	}

	public function findFirebaseUser($email){
		try {
			$this->createFirebase();
			if($this->firebase) {
				$auth = $this->firebase->createAuth();
				$res =  $auth->getUserByEmail($email);
				if($res && $res->uid){
					return $res->uid;
				}
				else{
					return false;
				}
			}
			$error = "Wrong Firebase credentials or connection issue";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseUser'
			]);
			return false;
		} catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'findFirebaseUser'
			]);
			return false;
		}
	}


	public function getFirebaseUserData($email){
		try {
			$firebase_uid = $this->findFirebaseUser( $email );
			if ( $firebase_uid ) {
				$ret_data = [
					'uid' => $firebase_uid,
					'customerId' => '',
					'subscriptionId' => ''
				];
				$user_data = $this->getFirebaseCollectionRecord( $firebase_uid );
				if ( ! empty( $user_data ) && ! empty( $user_data['customerId'] ) ) {
					$ret_data['customerId'] = $user_data['customerId'];
					$ret_data['subscriptionId'] = !empty($user_data['subscriptionId']) ? $user_data['subscriptionId'] : '';
					return $ret_data;
				}
			}
			return false;
		} catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseUserData'
			]);
			return false;
		}
	}

	public function deleteFirebaseUserAndDoc($uid, $collection = 'users'){
		try {
			$this->createFirebase();
			if($this->firebase) {
				$auth = $this->firebase->createAuth();
				$del_res = $auth->deleteUser($uid);
				if($del_res !== false){
					//successfuly deleted
					$firestore         = $this->firebase->createFirestore();
					$database          = $firestore->database();
					$collection        = $database->collection( $collection );
					$documentReference = $collection->document( $uid );
					$result = $documentReference->delete();
					return $result;
				}
				$error = "Error! Can't delete User Acc.";
				Errors::create([
					'error' => $error,
					'controller' => 'CustomersController',
					'function' => 'deleteFirebaseUserAndDoc'
				]);
				return false;
			}
			$error = "Wrong Firebase credentials or connection issue";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'deleteFirebaseUserAndDoc'
			]);
			return false;
		} catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'deleteFirebaseUserAndDoc'
			]);
			return false;
		}
	}

	public function refundSequence(Invoices $invoice){
		try {

			$user_logged = Auth::user();

			$dataToLog = [
				'action' => 1,
				'invoice_id' => $invoice->id,
				'user_id' => $user_logged->id
			];

			$dataToLogPipedrive = $dataToLog;
			$dataToLogPipedrive['service_id'] = 0;


			$phonesAndEmails = $this->getPipedriveLeadPhonesEmails($invoice->customer);
			if(!empty($phonesAndEmails) && !empty($phonesAndEmails['data'])){

				$dataToLogPipedrive['result'] = json_encode($phonesAndEmails);

				$phones = !empty($phonesAndEmails['data']['phones']) ? $phonesAndEmails['data']['phones'] : [];
				$emails = !empty($phonesAndEmails['data']['emails']) ? $phonesAndEmails['data']['emails'] : [];

				// unsubscribe SMS
				$smsSystemInput = [
					'phones' => json_encode($phones),
					'emails' => json_encode($emails),
					'token'   => 'PortInsQezInch111'
				];


				$smsResp = $this->sendDataToSMSSystem($smsSystemInput, 'ungrancellead');
				$dataToLogDeleteAcc = $dataToLog;
				$dataToLogDeleteAcc['service_id'] = 4;
				if(!empty($smsResp)){
					if(!empty($smsResp->success) && !empty($smsResp->data) ){
						$dataToLogDeleteAcc['result'] = $smsResp->data;
						if(!empty($smsResp->data->phones)) {
							SentData::create( [
								'service_type' => $dataToLogDeleteAcc['service_id'],
								'field'        => 'phone',
								'value'        => implode( ', ', json_decode( $smsResp->data->phones, 1 ) ),
								'action'       => 4
							] );
						}
						if(!empty($smsResp->data->emails)) {
							SentData::create( [
								'service_type' => $dataToLogDeleteAcc['service_id'],
								'field'        => 'email',
								'value'        => implode( ', ', json_decode( $smsResp->data->emails, 1 ) ),
								'action'       => 4
							] );
						}
					}
					else{
						if(!empty($smsResp->message)) {
							$dataToLogDeleteAcc['error'] = $smsResp->message;
						}
					}

				}

				InvoicesLog::create($dataToLogDeleteAcc);

				if($emails && count($emails)){
					foreach($emails as $email){
							$dataToLogFBAcc = $dataToLog;
							$dataToLogFBAcc['service_id'] = 2;
							$user_data = $this->getFirebaseUserData( $email );
							if ( $user_data  && !empty($user_data['uid'])) {
								$dataToLogDeleteAcc = $dataToLog;
								$dataToLogDeleteAcc['service_id'] = 7;
								$dataToLogFBAcc['result'] = json_encode($user_data);
								SentData::create( [
									'service_type' => $dataToLogFBAcc['service_id'],
									'field'        => 'email',
									'value'        => $email,
									'action'       => 1
								] );
								if ( $del_res = $this->deleteFirebaseUserAndDoc( $user_data['uid'] ) ) {
									//firebase data deleted
									$dataToLogDeleteAcc['result'] = json_encode($del_res);
									SentData::create( [
										'service_type' => $dataToLogDeleteAcc['service_id'],
										'field'        => 'email',
										'value'        => $email,
										'action'       => 1
									] );
								}
								else{
									$dataToLogDeleteAcc['error'] = 'Can\'t delete Firebase User: '.$email;
								}
								InvoicesLog::create($dataToLogDeleteAcc);
							}
							else{
								$dataToLogFBAcc['error'] = 'Can\'t get Firebase User\'s data: '.$email;
							}
							InvoicesLog::create($dataToLogFBAcc);

							//delete stripe
							$dataToLogDeleteAcc = $dataToLog;
							$dataToLogDeleteAcc['service_id'] = 6;

							if ( ! empty( $user_data['subscriptionId'] ) ) {
								$this->createStripe();
								if($this->stripe) {
									$subscription = $this->stripe->subscriptions->cancel( $user_data['subscriptionId'], [] );
									if ( $subscription && ! empty( $subscription->status ) && $subscription->status == 'canceled' ) {
										$dataToLogDeleteAcc['result'] = !empty($subscription->id) ? $subscription->id : '';
										SentData::create( [
											'service_type' => $dataToLogDeleteAcc['service_id'],
											'field'        => 'email',
											'value'        => $email,
											'action'       => 1
										] );
									} else {
										$dataToLogDeleteAcc['error'] = 'Can\'t cancel stripe subscription for user '.$email;
									}

								}
								else{
									$dataToLogDeleteAcc['error'] = 'Can\'t connect to Stripe';
								}


							}
							else{
								$dataToLogDeleteAcc['error'] = 'Can\'t cancel stripe subscription, no stripe subscriptionId found';
							}
							InvoicesLog::create($dataToLogDeleteAcc);

							$dataToLogDeleteAcc = $dataToLog;
							$dataToLogDeleteAcc['service_id'] = 1;

							if ( ! empty( $user_data['customerId'] ) ) {
								$customer = $this->stripe->customers->delete( $user_data['customerId'], [] );
								if ( $customer && ! empty( $customer->deleted ) ) {
									$dataToLogDeleteAcc['result'] = json_encode($customer);
									SentData::create( [
										'service_type' => $dataToLogDeleteAcc['service_id'],
										'field'        => 'email',
										'value'        => $email,
										'action'       => 1
									] );
								}
								else{
									$dataToLogDeleteAcc['error'] = 'Can\'t delete stripe user '.$email;
								}
							}
							else{
								$dataToLogDeleteAcc['error'] = 'Can\'t delete stripe user '.$email.', no stripe customerId found';
							}

							InvoicesLog::create($dataToLogDeleteAcc);

							//delete klavio customer
							$dataToLogDeleteAcc = $dataToLog;
							$dataToLogDeleteAcc['service_id'] = 8;

							$this->createKlaviyo();
							if($this->klaviyo) {
								$list_id = $this->klaviyo_listId;
								$res = $this->klaviyo->lists->unsubscribeMembersFromList($list_id, $email);
								if ( $res ) {
									$dataToLogDeleteAcc['result'] = json_encode($res);
									SentData::create( [
										'service_type' => $dataToLogDeleteAcc['service_id'],
										'field'        => 'email',
										'value'        => $email,
										'action'       => 1
									] );
								}
								else{
									$dataToLogDeleteAcc['error'] = 'Can\'t delete Klaviyo user '.$email;
								}
							}
							else{
								$dataToLogDeleteAcc['error'] = 'No Klaviyo API Key found';
							}
							InvoicesLog::create($dataToLogDeleteAcc);

					}
				}


			}
			else{
				$dataToLogPipedrive['error'] = 'Unknown Error.';
			}

			InvoicesLog::create($dataToLogPipedrive);
			return true;
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			$report = [
				'success' => false,
				'errors' => [$error]
			];
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'refundSequence'
			]);
			return $report;
		}
	}



	public function getFirebaseCollectionRecord($id, $collection= 'users'){
		try {
			$this->createFirebase();
			if($this->firebase) {
				$firestore         = $this->firebase->createFirestore();
				$database          = $firestore->database();
				$collection        = $database->collection( $collection );
				$documentReference = $collection->document( $id );
				$snapshot = $documentReference->snapshot();
				return $snapshot->data();
			}
			$error = "Wrong Firebase credentials or connection issue";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseUser'
			]);
			return false;
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseCollectionRecord'
			]);
			return false;
		}
	}

	protected function createFirebase(){
		try{
			if(config( 'firebase.file_name' )) {
				$this->firebase = ( new Factory )->withServiceAccount( storage_path( config( 'firebase.file_name' ) ) );
				return $this->firebase;
			}
			$this->firebase = '';
			$error = "No Firebase Configuration Found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createFirebase'
			]);
			return false;
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createFirebase'
			]);
			$this->firebase = '';
			return false;
		}
	}

	protected function createStripe(){
		try{
			if(config( 'stripe.stripeKey' )) {
				$this->stripe = new StripeClient( config( 'stripe.stripeKey' ) );
				return $this->stripe;
			}
			$this->stripe = '';
			$error = "No Stripe Configuration Found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createStripe'
			]);
			return false;
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createStripe'
			]);
			return false;

		}
	}

	protected function createKlaviyo(){
		try{
			if(config( 'klaviyo.apiKey' ) && config( 'klaviyo.pubKey' ) && config( 'klaviyo.listId' )) {
				$this->klaviyo        = new Klaviyo( config( 'klaviyo.apiKey' ), config( 'klaviyo.pubKey' ) );
				$this->klaviyo_listId = config( 'klaviyo.listId' );
				return [
					'klaviyo'        => $this->klaviyo,
					'klaviyo_listId' => $this->klaviyo_listId
				];
			}
			$this->klaviyo = '';
			$this->klaviyo_listId =  '';
			$error = "No Klaviyo Configuration Found";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createKlaviyo'
			]);
			return false;
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'createKlaviyo'
			]);
			$this->klaviyo = '';
			$this->klaviyo_listId = !empty($this->klaviyo_listId) ? $this->klaviyo_listId : '';
			return false;

		}
	}

	protected function createSMSsystem(){
		if(config( 'smssystem.url' )) {
			$this->smssystem = config( 'smssystem.url' );
			return $this->smssystem;
		}
		$error = "No SMS System URL Found";
		Errors::create([
			'error' => $error,
			'controller' => 'CustomersController',
			'function' => 'createSMSsystem'
		]);
		$this->smssystem = '';
		return false;
	}

	public function updatePipedriveDeal($deal_id, $sales_price){
		try {
			$deal = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\UpdateDeal( $deal_id, $sales_price  ) );
			if (
				!empty($deal) &&
				!empty($deal->data) &&
				!empty($deal->data->id)
			) {
				return $this->sendResponse( $deal->data->id, '', false );
			}
			else {
				$error = "Pipedrive: Can't update deal";
				Errors::create( ['error' => $error, 'controller' => 'CustomersController', 'function' => 'updatePipedriveDeal'] );
				return $this->sendError($error, [], 404, false);
			}

		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create( ['error' => 'Pipedrive: '.$error, 'controller' => 'CustomersController', 'function' => 'updatePipedriveDeal'] );
			return $this->sendError($error, [], 404, false);
		}

	}

	public function checkPipedrive($input){
		try {
			$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\SearchPersonByName( $input['email'], 1 ) );

			if (
				!empty($searchPerson) &&
				!empty($searchPerson->data) &&
				!empty($searchPerson->data->items) &&
				count($searchPerson->data->items)
			)
			{
				foreach($searchPerson->data->items as $itm){
					if(!empty($itm->item) && !empty($itm->item->emails) && count($itm->item->emails)){
						foreach($itm->item->emails as $em){
							if(trim(strtolower($input['email'])) == trim(strtolower($em))){
								return $this->sendResponse($itm->item, '', false);
							}
						}
					}
				}
				return $this->sendResponse($searchPerson->data->items, 'pipedrive', false);
			}
			else{
				$searchPersonByName = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\SearchPersonByName( $input['full_name'] ) );
				if (
					!empty($searchPersonByName) &&
					!empty($searchPersonByName->data) &&
					!empty($searchPersonByName->data->items) &&
					count($searchPersonByName->data->items)
				)
				{
					return $this->sendResponse($searchPersonByName->data->items, 'pipedrive', false);
				}
				else {
					$error = "Pipedrive: No " . $input['email'] . " found on Pipedrive";
					Errors::create( [ 'error'      => $error,
					                  'controller' => 'CustomersController',
					                  'function'   => 'checkPipedrive'
					] );
					return $this->sendResponse( '', 'pipedrive2', false );
				}
			}

		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create( ['error' => 'Pipedrive: '.$error, 'controller' => 'CustomersController', 'function' => 'checkPipedrive'] );
			return $this->sendError($error, [], 404, false);
		}

	}

	public function updateOrAddPipedriveDeal($person, $sales_price){
		try{
			$deals = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\SearchDeal( $person->id ) );
			if (
				!empty($deals) &&
				!empty($deals->data) &&
				!empty($deals->data[0]) &&
				!empty($deals->data[0]->id)
			)
			{
				return $this->updatePipedriveDeal( $deals->data[0]->id, $sales_price );
			}
			else {
				$owner_id = 0;
				if(!empty($person->owner) && !empty($person->owner->id)){
					$owner_id = $person->owner->id;
				}
				$deal = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\CreateDeal( $person->id, $owner_id, $sales_price, $person->name ) );
				if (
					!empty($deal) &&
					!empty($deal->data) &&
					!empty($deal->data->id)
				){
					return $this->sendResponse($deal->data->id,'', false);
				}
				else {
					$error = "Pipedrive: Can't create deal";
					Errors::create( [ 'error'      => $error,
					                  'controller' => 'CustomersController',
					                  'function'   => 'updateOrAddPipedriveDeal'
					] );
					return $this->sendError( $error, [], 404, false );
				}
			}
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create( ['error' => 'Pipedrive: '.$error, 'controller' => 'CustomersController', 'function' => 'updateOrAddPipedriveDeal'] );
			return $this->sendError($error, [], 404, false);
		}
	}

	public function getPipedriveLeadSources(Customers $customers){
		try {
			$key = config( 'pipedrive.api_key' );
			$source_field_name = config( 'pipedrive.source_field_id' );
			$extra_field_name = config( 'pipedrive.extra_field_id' );

//			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
//			$source_field_name = '0d42d585b2f6407cd384cd02838de179c0a1527d';
//			$extra_field_name = '012fe2582b1a93009814bdd11aa6a630622eb209';

			$email = $customers->email;
			$customer_id = $customers->id;

			$searchPerson = Pipedrive::executeCommand( $key, new Pipedrive\Commands\SearchPersonByName( $email, 1 ) );

			if (
				!empty($searchPerson) &&
				!empty($searchPerson->data) &&
				!empty($searchPerson->data->items) &&
				count($searchPerson->data->items)
			)
			{
				foreach($searchPerson->data->items as $itm){
					if(!empty($itm->item) && !empty($itm->item->emails) && count($itm->item->emails)){
						foreach($itm->item->emails as $em){
							if(trim(strtolower($email)) == trim(strtolower($em))){
								//found lead

								if(!empty($itm->item->id)){
									//delete previous data
									PipedriveData::where( 'customer_id', $customer_id )
									             ->delete();
									$res = Pipedrive::executeCommand( $key, new Pipedrive\Commands\GetPersonCustomField( $itm->item->id ) );
									if(!empty($res) && !empty($res->data)) {
										if(!empty($res->data->$source_field_name)) {
												$sources_arr = explode( ',', $res->data->$source_field_name );
												if ( $sources_arr && count( $sources_arr ) ) {
													foreach ( $sources_arr as $s ) {
														$s_trimed = trim( $s );
														if ( $s_trimed ) {
															// check if string exist
															$str_id = Strings::where( 'pi_name', $s_trimed )->value( 'id' );
															if ( ! $str_id ) {
																$str    = Strings::create( [ 'pi_name' => $s_trimed ] );
																$str_id = $str->id;
															}
															// saving to pipedrive_data table
															PipedriveData::where( 'customer_id', $customer_id )
															             ->where( 'field_name', 0 )
															             ->where( 'pd_source_string_id', $str_id )
															             ->delete();

															PipedriveData::create( [
																'customer_id'         => $customer_id,
																'field_name'          => 0,
																'pd_person_id'        => $itm->item->id,
																'pd_source_string_id' => $str_id
															] );
														}
													}
												}
										}
										if(!empty($res->data->$extra_field_name)) {
												$extra_fields_arr = explode( ',', $res->data->$extra_field_name );
												if ( $extra_fields_arr && count( $extra_fields_arr ) ) {
													foreach ( $extra_fields_arr as $x ) {
														$x_trimed = trim( $x );
														if ( $x_trimed ) {
															// check if string exist
															$str_id = Strings::where( 'pi_name', $x_trimed )->value( 'id' );
															if ( ! $str_id ) {
																$str    = Strings::create( [ 'pi_name' => $x_trimed ] );
																$str_id = $str->id;
															}
															// saving to pipedrive_data table
															PipedriveData::where( 'customer_id', $customer_id )
															             ->where( 'field_name', 1 )
															             ->where( 'pd_source_string_id', $str_id )
															             ->delete();

															PipedriveData::create( [
																'customer_id'         => $customer_id,
																'field_name'          => 1,
																'pd_person_id'        => $itm->item->id,
																'pd_source_string_id' => $str_id
															] );
														}
													}
												}
										}
									}
								}
							}
						}
					}
				}

			}
			return $this->sendResponse([], '', false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create( ['error' => 'Pipedrive: '.$error, 'controller' => 'CustomersController', 'function' => 'getPipedriveLeadSources'] );
			return $this->sendError($error, [], 404, false);
		}

	}

	public function getPipedriveLeadPhonesEmails(Customers $customers){
		try {
			$key = config( 'pipedrive.api_key' );
			$source_field_name = config( 'pipedrive.source_field_id' );
			$extra_field_name = config( 'pipedrive.extra_field_id' );

//			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
//			$source_field_name = '0d42d585b2f6407cd384cd02838de179c0a1527d';
//			$extra_field_name = '012fe2582b1a93009814bdd11aa6a630622eb209';

			$email = $customers->email;
			$phone = $customers->phone_number;
			$customer_id = $customers->id;

			$phones = [
				trim( strtolower($phone))
			];
			$emails = [
				trim( strtolower($email))
			];



			$searchPerson = Pipedrive::executeCommand( $key, new Pipedrive\Commands\FindPersonNew( $email ) );
			if (
				!empty($searchPerson) &&
				!empty($searchPerson->data) &&
				!empty($searchPerson->data->items) &&
				count($searchPerson->data->items)
			)
			{

				foreach($searchPerson->data->items as $itm){
					if(!empty($itm->item)) {
						if ( ! empty( $itm->item->emails ) && count( $itm->item->emails ) ) {
							foreach ( $itm->item->emails as $em ) {
								$emails[] = trim( strtolower( $em ) );
							}
						}
						if ( ! empty( $itm->item->phones ) && count( $itm->item->phones ) ) {
							foreach ( $itm->item->phones as $ph ) {
								$phones[] = trim( strtolower( $ph ) );
							}
						}
					}
				}

			}

			$result = [
				'phones' => array_unique($phones),
				'emails' => array_unique($emails),
			];

			return $this->sendResponse($result, '', false);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create( ['error' => 'Pipedrive: '.$error, 'controller' => 'CustomersController', 'function' => 'getPipedriveLeadSources'] );
			return $this->sendError($error, [], 404, false);
		}

	}
}
