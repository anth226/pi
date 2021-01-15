<?php

namespace App\Http\Controllers;

use App\Customers;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\SentData;
use App\SentDataLog;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Kreait\Firebase\Factory;
use Klaviyo\Klaviyo as Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;
use Validator;
use Exception;


class CustomersController extends BaseController
{
	protected $stripe, $firebase, $klaviyo, $klaviyo_listId, $smssystem;
	function __construct()
	{
		$this->middleware( [ 'auth', 'verified' ] );
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
			$this->sendDataToSMSSystem( $dataToSend, $customer->id );
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


	protected function sendDataToSMSSystem($input){
		try {
			$this->createSMSsystem();
			if($this->smssystem) {
				$url      = $this->smssystem;
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
							'function'   => 'sendLead'
						] );

						return $this->sendError( $error, [], 404, false );
					}
				} else {
					$error = "No response from " . $url;
					Errors::create( [
						'error'      => $error,
						'controller' => 'CustomersController',
						'function'   => 'sendLead'
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

	public function getFirebaseCollectionRecord($id, $collection= 'users'){
		try {
			$this->createFirebase();
			if($this->firebase) {
				$firestore         = $this->firebase->createFirestore();
				$database          = $firestore->database();
				$collection        = $database->collection( $collection );
				$documentReference = $collection->document( $id );
				$snapshot = $documentReference->snapshot();
				return $this->sendResponse($snapshot);
			}
			$error = "Wrong Firebase credentials or connection issue";
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseUser'
			]);
			return $this->sendError($error);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'CustomersController',
				'function' => 'getFirebaseCollectionRecord'
			]);
			return $this->sendError($error);
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
}
