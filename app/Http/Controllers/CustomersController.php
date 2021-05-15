<?php

namespace App\Http\Controllers;

use App\Customers;
use App\CustomersContacts;
use App\CustomersContactSubscriptions;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\PipedriveData;
use App\Products;
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

        $this->sendDataToPISystem($customer->toArray(), 'user');

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

		$contact_type = json_encode(CustomersContacts::CONTACT_TYPES);
		$contact_subtype = json_encode(CustomersContacts::CONTACT_SUBTYPES);
		$subscription_type = json_encode(CustomersContactSubscriptions::SUBSCRIPTION_TYPES);
		$subscription_status = json_encode(CustomersContactSubscriptions::SUBSCRIPTION_STATUS);

		if($customer) {
			$user_logged = Auth::user();
			$user_id = $user_logged->id;
			return view( 'customers.show', compact( 'customer', 'sentLog', 'contact_subtype', 'contact_type', 'subscription_status', 'subscription_type', 'user_id' ) );
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
					if ( $result && ! empty( $result->success ) && $result->success && isset( $result->data ) ) {
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

    public function sendDataToPISystem($input, $action_path = 'user')
    {
        try{
            $result = Http::post();
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
	public function createStripeSubscription($customer_id, $product_id){
		try{
			$product =  Products::find($product_id);
			$data = [
				'customer' => $customer_id,
				'coupon' => config('stripe.coupon'),
				'trial_from_plan' => true,
			];
			if(config('app.env') == 'local') {
				$data['items'] = [
					['price' => $product->dev_stripe_price_id]
				];
				$data['coupon'] = $product->dev_stripe_coupon_id;
			}
			else{
				$data['items'] = [
					['price' => $product->stripe_price_id]
				];
				$data['coupon'] = $product->stripe_coupon_id;
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
					return $this->createStripeSubscription( $res['data'], $input['stripe_product_id'] );
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
				if($res){
					return $res;
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
			$firebase = $this->findFirebaseUser( $email );
			if ( !empty($firebase) && !empty($firebase->uid) ) {
				$user_data = $this->getFirebaseCollectionRecord( $firebase->uid );
				if (isset( $user_data ) && is_array($user_data) ) {
					$user_data['uid'] = $firebase->uid;
					$user_data['disabled'] = $firebase->disabled;
					return $user_data;
				}
				else{
					return [
						'uid' => $firebase->uid,
						'disabled' => $firebase->disabled
					];
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
				$err_message = "Error! Can't delete User Acc.";
			}
			else {
				$err_message = "Wrong Firebase credentials or connection issue";
			}
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'deleteFirebaseUserAndDoc'
			]);
			return false;
		} catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'deleteFirebaseUserAndDoc'
			]);
			return false;
		}
	}

	public function refundSequence(Invoices $invoice){
		$errors = [];
		$succes_messages = [];
		try {
			$phones = [];
			$emails = [];
			$contacts = CustomersContacts::where('customer_id', $invoice->customer_id)->get();
			if($contacts && $contacts->count()){
				foreach($contacts as $contact){
					if($contact->contact_type){
						$phones[] = $contact->formated_contact_term;
					}
					else{
						$emails[] = $contact->formated_contact_term;
					}
				}
			}

			if($emails && count($emails)){
				foreach($emails as $email){
//					$email = trim(strtolower($email));
//					$customer_email = trim(strtolower($invoice->customer->email));
//					if($email == $customer_email) {
						// unsubscribe Stripe
						$res_stripe = $this->unsubscribeStripe( $email, false );
						if ( ! $res_stripe || ! $res_stripe['success'] ) {
							$error    = "Can not unsubscribe " . $email . " from Stripe. ";
							$errors[] = $error . ! empty( $res_stripe['message'] ) ? $res_stripe['message'] : "";
						}
						else{
							if(!empty($res_stripe['message'])){
								$succes_messages[] = $res_stripe['message'];
							}
						}

						// delete Firebase
						$res_firebase = $this->unsubscribeFirebase( $email, false );
						if ( ! $res_firebase || ! $res_firebase['success'] ) {
							$error    = "Can not unsubscribe " . $email . " from FireBase. ";
							$errors[] = $error . ! empty( $res_firebase['message'] ) ? $res_firebase['message'] : "";
						}
						else{
							if(!empty($res_firebase['message'])){
								$succes_messages[] = $res_firebase['message'];
							}
						}
//					}

					// unsubscribe klavio customer
					$res_klaviyo = $this->unsubscribeKlaviyo( $email, false);
					if(!$res_klaviyo || !$res_klaviyo['success']){
						$error = "Can not unsubscribe ".$email." from Klaviyo. ";
						$errors[] = $error . !empty($res_klaviyo['message']) ? $res_klaviyo['message'] : "";
					}
					else{
						if(!empty($res_klaviyo['message'])){
							$succes_messages[] = $res_klaviyo['message'];
						}
					}


				}
			}

			// unsubscribe SMS
			$res_smssystem = $this->unsubscribeSmsSystem(json_encode($emails), json_encode($phones), false);
			if(!$res_smssystem || !$res_smssystem['success']){
				$errors[] = !empty($res_smssystem['message']) ? $res_smssystem['message'] : "Can not unsubscribe from SMS System";
			}
			else{
				if(!empty($res_smssystem['message'])){
					$succes_messages[] = $res_smssystem['message'];
				}
			}

			$user = Auth::user();
			$this->subscriptionsCheck($invoice->customer_id, $user->id );

			if(count($errors)){
				return $this->sendError( "Some errors happen.", $errors, 404, false );
			}
			return $this->sendResponse( $succes_messages, '', false );
		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			$errors[] = $err_message;
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'refundSequence'
			]);
			return $this->sendError( $err_message, $errors, 404, false );
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

	public function addContacts(Customers $customer, $user_id,  $invoice_id = 0){
		try {
			$contactData = [
				'customer_id' => $customer->id,
				'user_id'     => $user_id
			];
			if ( $invoice_id ) {
				$contactData['is_main_for_invoice_id'] = $invoice_id;
			}
			$phone                 = ! empty( $customer->phone_number ) ? trim( strtolower( $customer->phone_number ) ) : '';
			$email                 = ! empty( $customer->email ) ? trim( strtolower( $customer->email ) ) : '';
			$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber( $phone );
			if ( $formated_phone_number ) {
				$if_phone_exist = CustomersContacts::where( 'customer_id', $customer->id )->where( 'contact_type', 1 )->where( 'formated_contact_term', $formated_phone_number )->count();
				if ( ! $if_phone_exist ) {
					$contactData['contact_type']          = 1;
					$contactData['contact_term']          = $phone;
					$contactData['formated_contact_term'] = $formated_phone_number;
					CustomersContacts::create( $contactData );
				}
			}
			$if_email_exist = CustomersContacts::where( 'customer_id', $customer->id )->where( 'contact_type', 0 )->where( 'formated_contact_term', $email )->count();
			if ( ! $if_email_exist ) {
				$contactData['contact_type']          = 0;
				$contactData['contact_term']          = $email;
				$contactData['formated_contact_term'] = $email;
				CustomersContacts::create( $contactData );
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersController',
				'function' => 'addContacts'
			]);
		}
	}


	public function checkSmsSubsPhone($phone){
		$data = [
			'phone' => $phone,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data, $url = 'stugelvichak');

	}
	public function checkSmsSubsEmail($email){
		$data = [
			'email' => $email,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data, $url = 'stugelvichak');
	}
	public function checkKlaviyo($email){
		try {
			$this->createKlaviyo();
			if ( $this->klaviyo ) {
				$list_id    = $this->klaviyo_listId;
				$res        = $this->klaviyo->lists->checkListMembership( $list_id, [ $email ] );
				$subscr_res = count( $res ) ? 1 : 0;
				return $this->sendResponse( $subscr_res, '', false );
			}
			$error_mess = 'No Klaviyo API Key found';
			return $this->sendError( $error_mess, '', 404, false );
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersController',
				'function' => 'checkKlaviyo'
			]);
			return $this->sendError($ex->getMessage(),'',404, false);
		}
	}

	public function checkStripe($subs_id, $email){
		try {
			if($subs_id){
				$this->createStripe();
				if($this->stripe) {
					$subscription = $this->stripe->subscriptions->retrieve( $subs_id, [] );
					if ( !empty($subscription) ) {
						return $this->sendResponse($subscription, '', false );
					}
					$err_message = 'No stripe subscription or subscription is not active for user '.$email;
				}
				else{
					$err_message = 'Can\'t connect to Stripe';
				}
			}
			else{
				$err_message = 'No Stripe subscriptionId';
			}
			return $this->sendError( $err_message, '', 404, false );
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersController',
				'function' => 'checkStripe'
			]);
			return $this->sendError($ex->getMessage(),'',404, false);
		}
	}
	public function checkFirebase($email){
		try {
			$user_data = $this->getFirebaseUserData( $email );
			if ( $user_data )  {
				return $this->sendResponse( $user_data, '', false);
			} else {
				$message = 'Can\'t get Firebase User\'s data: '.$email;
				return $this->sendResponse($user_data,$message, false);
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersController',
				'function' => 'checkFirebaseAndStripe'
			]);
			return $this->sendError($ex->getMessage(),'',404, false);
		}
	}

	public function subscriptionsCheck($customer_id, $user_id, $invoice_id = 0){
		try {
			$contacts = CustomersContacts::where( 'customer_id', $customer_id )->get();
			if ( $contacts && $contacts->count() ) {
				foreach ( $contacts as $c ) {
					$dataToSave = [
						'customers_contact_id' => $c->id,
						'user_id'              => $user_id
					];
					if ( $c->contact_type ) { //phone
						$sms = $this->checkSmsSubsPhone( $c->contact_term );
						if ( $sms && $sms['success'] && isset( $sms['data'] ) ) {
							$dataToSave['subscription_type']   = 3;
							$dataToSave['subscription_status'] = $sms['data'];
							if ( $invoice_id ) {
								$dataToSave['invoice_id'] = $invoice_id;
							}
							$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $c->id )->where( 'subscription_type', 3 )->get();
							if ( $if_record_exist && $if_record_exist->count() ) {
								foreach ( $if_record_exist as $r ) {
									$dataToSaveForUpdate = [
										'subscription_status' => $sms['data']
									];
									CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSaveForUpdate );
								}
							} else {
								CustomersContactSubscriptions::create( $dataToSave );
							}
						}
					} else { //email

						$sms = $this->checkSmsSubsEmail( $c->contact_term );
						if ( $sms && $sms['success'] && isset( $sms['data'] ) ) {
							$dataToSave['subscription_type']   = 4;
							$dataToSave['subscription_status'] = $sms['data'];
							if ( $invoice_id ) {
								$dataToSave['invoice_id'] = $invoice_id;
							}
							$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $c->id )->where( 'subscription_type', 4 )->get();

							if ( $if_record_exist && $if_record_exist->count() ) {
								foreach ( $if_record_exist as $r ) {
									$dataToSaveForUpdate = [
										'subscription_status' => $sms['data']
									];
									CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSaveForUpdate );
								}
							} else {
								CustomersContactSubscriptions::create( $dataToSave );
							}
						}
						$sms = $this->checkKlaviyo($c->contact_term);

						if ( $sms && $sms['success']) {
							$sms['data'] = !empty($sms['data']) ? $sms['data'] : 0;
							$dataToSave['subscription_type']   = 2;
							$dataToSave['subscription_status'] = $sms['data'];
							if ( $invoice_id ) {
								$dataToSave['invoice_id'] = $invoice_id;
							}
							$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $c->id )->where( 'subscription_type', 2 )->get();

							if ( $if_record_exist && $if_record_exist->count() ) {
								foreach ( $if_record_exist as $r ) {
									$dataToSaveForUpdate = [
										'subscription_status' => $sms['data']
									];
									CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSaveForUpdate );
								}
							} else {
								CustomersContactSubscriptions::create( $dataToSave );
							}
						}

						$sms = $this->checkFirebase($c->contact_term);

						if ( $sms && $sms['success']) {
							$user_data = !empty($sms['data']) ? $sms['data'] : [];
							$dataToSave['subscription_type']   = 1;
							$dataToSave['subscription_status'] = 1;
							if(empty($user_data['userId'])){
								$dataToSave['subscription_status'] = 0;
							}
							if(!empty($user_data['disabled'])){
								$dataToSave['subscription_status'] = 12;
							}
							if ( $invoice_id ) {
								$dataToSave['invoice_id'] = $invoice_id;
							}

							$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $c->id )->where( 'subscription_type', 1 )->get();

							if ( $if_record_exist && $if_record_exist->count() ) {
								foreach ( $if_record_exist as $r ) {
									$dataToSaveForUpdate = [
										'subscription_status' => $dataToSave['subscription_status']
									];
									CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSaveForUpdate );
								}
							} else {
								CustomersContactSubscriptions::create( $dataToSave );
							}



							//check stripe
							if(!empty($user_data['subscriptionId'])) {
								$subscription_id = $user_data['subscriptionId'];
							}
							else {
								$subscription_id = Invoices::where('customer_id', $customer_id)->value('stripe_subscription_id');
							}
							if($subscription_id){
								$sms = $this->checkStripe( $subscription_id, $c->contact_term );

								if ( $sms && $sms['success'] ) {
									$sms['data']  = ! empty( $sms['data'] ) ? $sms['data'] : 0;
									$subscription_price_id = (!empty($sms['data']->plan) && !empty($sms['data']->plan->id)) ? $sms['data']->plan->id : '';
									$prod_id = 0;
									$subscription_type = false;
									if($subscription_price_id){
										if(config('app.env') == 'local') {
											$prod_id = Products::where('dev_stripe_price_id', $subscription_price_id)->value('id');
										}
										else{
											$prod_id = Products::where('stripe_price_id', $subscription_price_id)->value('id');
										}
									}
									if($prod_id) {
										switch ( $prod_id ) {
											case 1:
												$subscription_type   = 6;
												break;
											case 2:
												$subscription_type   = 7;
												break;
											case 3:
												$subscription_type   = 8;
												break;
										}
									}

									if($subscription_type){
										$dataToSave['subscription_type']   = $subscription_type;
										$dataToSave['subscription_status'] = Invoices::STRIPE_STATUSES[$sms['data']->status];
										if ( $invoice_id ) {
											$dataToSave['invoice_id'] = $invoice_id;
										}
										$if_record_exist = CustomersContactSubscriptions::where( 'customers_contact_id', $c->id )->where( 'subscription_type', $subscription_type )->get();

										if ( $if_record_exist && $if_record_exist->count() ) {
											foreach ( $if_record_exist as $r ) {
												$dataToSaveForUpdate = [
													'subscription_status' => $dataToSave['subscription_status']
												];
												CustomersContactSubscriptions::where( 'id', $r->id )->update( $dataToSaveForUpdate );
											}
										} else {
											CustomersContactSubscriptions::create( $dataToSave );
										}
									}

								}
							}




						}
					}
				}
			}
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'CustomersController',
				'function' => 'subscriptionCheck'
			]);
		}

	}

	public function unsubscribeStripe( $email, $is_response_json = true ){
		// we need to get subs_id from firebase first
		try {
			$err_message = '';
			$user_data = $this->getFirebaseUserData( $email );
			if ( $user_data  && !empty($user_data['uid'])) {

				if ( ! empty( $user_data['subscriptionId'] ) ) {
					$this->createStripe();
					if($this->stripe) {
						$subscription = $this->stripe->subscriptions->cancel( $user_data['subscriptionId'], [] );
						if ( $subscription && ! empty( $subscription->status ) && $subscription->status == 'canceled' ) {
							return $this->sendResponse($subscription->id, $email.' subscription canceled from Stripe', $is_response_json );
						}
						$err_message = 'Can\'t cancel stripe subscription for user '.$email;
					}
					else{
						$err_message = 'Can\'t connect to Stripe';
					}
				}
				else{
//					$err_message = 'Can\'t cancel stripe subscription, no stripe subscriptionId found';
					return $this->sendResponse('done','No Stripe Subscription Found for '.$email. 'and subs_id: '.$user_data['subscriptionId'],  $is_response_json );
				}
			}
			else{
//				$err_message = 'Can\'t get Firebase User\'s data: '.$email;
				return $this->sendResponse('done', 'Unsubscribing from Stripe. No Firebase User '.$email.' Found',  $is_response_json );
			}

			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeFirebase'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );

		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeStripe'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}
	}
	public function unsubscribeFirebase( $email, $is_response_json = true ){
		try {
			$err_message = '';
			$user_data = $this->getFirebaseUserData( $email );
			if ( $user_data  && !empty($user_data['uid'])) {
				if ( $this->deleteFirebaseUserAndDoc( $user_data['uid'] ) ) {
					//firebase data deleted
					return $this->sendResponse( $user_data, $email.' deleted from Firebase', $is_response_json );
				}
				$err_message = 'Can\'t delete Firebase User: '.$email;
			}
			else {
//				$err_message = 'Can\'t get Firebase User\'s data: ' . $email;
				return $this->sendResponse( 'done','No Firebase User '.$email.' Found',  $is_response_json );
			}
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeFirebase'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );

		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeFirebase'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}
	}
	public function unsubscribeSmsSystem( $emails, $phones, $is_response_json = true ){
		try {
			$ajaxData      = [
				'phones' => $phones, // need json
				'emails' => $emails, //need json
				'token'  => 'PortInsQezInch111'
			];

			$smssystem_res = $this->sendDataToSMSSystem( $ajaxData, 'ungrancellead' );
			if ( ! $smssystem_res['success'] ) {
				$message = 'Error! Can\'t send data to SMS System';
				if ( ! empty( $smssystem_res['message'] ) ) {
					$message = $smssystem_res['message'];
				}
				Errors::create([
					'error' => $message,
					'controller' => 'CustomersController',
					'function' => 'unsubscribeSmsSystem'
				]);
				return $this->sendError( $message, '', 404, $is_response_json );
			}

			$success_message = '';
			if(!empty($smssystem_res['data'])){
				if(!empty($smssystem_res['data']->phones)){
					$term_string = 'phone';
					if(count($smssystem_res['data']->emails) > 1){
						$term_string = 'phones'; //if plural
					}
					$success_message .= 'Unsubscribed '.$term_string.' from SMS System : '. implode(', ',$smssystem_res['data']->phones);
				}
				if(!empty($smssystem_res['data']->emails)){
					$term_string = 'email';
					if(count($smssystem_res['data']->emails) > 1){
						$term_string = 'emails'; //if plural
					}
					if($success_message){ //checking if comma needed
						$success_message .= ', ';
					}
					$success_message .= 'Unsubscribed '.$term_string.' from SMS System: '. implode(', ',$smssystem_res['data']->emails);
				}
			}
			return $this->sendResponse( 'done', $success_message, $is_response_json );
		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeSmsSystem'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}
	}
	public function unsubscribeKlaviyo( $email, $is_response_json = true ){
		try {
			$err_message = '';
			$this->createKlaviyo();
			if ( $this->klaviyo ) {
				$list_id = $this->klaviyo_listId;
				$res     = $this->klaviyo->lists->unsubscribeMembersFromList( $list_id, $email );
				if ( ! $res ) {
					return $this->sendResponse( 'done', $email.' unsubscribed from Klaviyo prime daily emails', $is_response_json );
				}
				$err_message = 'Can\'t delete Klaviyo user ' . $email;
			}
			else {
				$err_message = 'No Klaviyo API Key found';
			}
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeKlaviyo'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'unsubscribeKlaviyo'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}
	}

	public function subscribeSmsSystem( $email = '', $phone = '', $first_name = '', $last_name = '', $is_response_json = true ){
		try {
			$ajaxData = [
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'full_name'  => $first_name . ' ' . $last_name,
				'phone'      => $phone,
				'email'      => $email,
				'source'     => 'portfolio-insider-prime',
				'tags'       => 'portfolio-insider-prime',
				'token'      => 'PortInsQezInch111'
			];

			$smssystem_res = $this->sendDataToSMSSystem( $ajaxData, 'ulpi' );
			if ( ! $smssystem_res['success'] ) {
				$err_message = 'Error! Can\'t send data to SMS System';
				if ( ! empty( $smssystem_res['message'] ) ) {
					$err_message = $smssystem_res['message'];
				}
				Errors::create([
					'error' => $err_message,
					'controller' => 'CustomersController',
					'function' => 'subscribeSmsSystem'
				]);
				return $this->sendError( $err_message, '', 404, $is_response_json );
			}
			return $this->sendResponse( 'done', '', $is_response_json );
		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'subscribeSmsSystem'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}
	}
	public function subscribeKlaviyo( $email, $phone = '', $first_name = '', $last_name = '', $is_response_json = true ){
		try {
			$ajaxData    = [
				'phone'      => $phone,
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name
			];
			$klaviyo_res = $this->sendDataToKlaviyo( $ajaxData );
			if ( ! $klaviyo_res['success'] ) {
				$err_message = 'Error! Can\'t send data to SMS System';
				if ( ! empty( $klaviyo_res['message'] ) ) {
					$err_message = $klaviyo_res['message'];
				}
				Errors::create([
					'error' => $err_message,
					'controller' => 'CustomersController',
					'function' => 'subscribeKlaviyo'
				]);
				return $this->sendError( $err_message, '', 404, $is_response_json );
			}

			return $this->sendResponse( 'done', '', $is_response_json );
		}
		catch (Exception $ex){
			$err_message = $ex->getMessage();
			Errors::create([
				'error' => $err_message,
				'controller' => 'CustomersController',
				'function' => 'subscribeKlaviyo'
			]);
			return $this->sendError( $err_message, '', 404, $is_response_json );
		}

	}

}
