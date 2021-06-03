<?php

namespace App\Http\Controllers;



use App\Customers;
use App\CustomersContacts;
use App\CustomersContactSubscriptions;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\API\StripeController;
use App\Invoices;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\LevelsSalespeople;
use App\Salespeople;
use App\SalespeoplePecentageLog;

use App\KmClasses\Pipedrive;

use App\SecondarySalesPeople;
use App\SentData;
use App\User;
use Exception;
use DB;
use Klaviyo\Klaviyo as Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;
use Kreait\Firebase\Factory;

class TestController extends BaseController
{
	public function __construct()
	{
		$this->middleware(['auth']);
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */

	public function index()
	{

		$pi_users = [
			'it@portfolioinsider.com',
			'kyle@portfolioinsider.com',
			'simon@portfolioinsider.com',
			'Dan@portfolioinsider.com',
			'deric@portfolioinsider.com',
			'Gus@Portfolioinsider.com',
			'Daniel@Portfolioinsider.com',
			'Santiago@Portfolioinsider.com',
			'Nickp@portfolioinsider.com',
			'walter@portfolioinsider.com',
			'evan@portfolioinsider.com',
			'collin@portfolioinsider.com',
			'steve@portfolioinsider.com',
			'jaydon@portfolioinsider.com',
			'albert@portfolioinsider.com',
			'christopher@portfolioinsider.com',
			'michaelo@portfolioinsider.com',
			'lucasrichter@portfolioinsider.com',
			'chandler@portfolioinsider.com',
			'ahmed@portfolioinsider.com',
			'cameron@portfolioinsider.com',
			'lane@portfolioinsider.com',
			'joshuaD@portfolioinsider.com',
			'Greg@portfolioinsider.com',
			'jacob@portfolioinsider.com',
			'diego@portfolioinsider.com',
			'nicholas@portfolioinsider.com',
			'miguel@portfolioinsider.com',
			'alejandro@portfolioinsider.com',
			'andrew@portfolioinsider.com',
			'joshuaL@portfolioinsider.com',
			'crusader@portfolioinsider.com',
			'jordan@portfolioinsider.com',
			'cesarg@portfolioinsider.com',
			'john@portfolioinsider.com',
			'stewart@portfolioinsider.com',
			'michael@portfolioinsider.com',
			'brandon@portfolioinsider.com',
			'hunter@portfolioinsider.com',
			'jay@portfolioinsider.com'
		];
//		foreach($pi_users as $u){
//			$u = trim(strtolower($u));
//			$u_array = explode('@', $u);
//			$u_name = ucwords($u_array[0]);
//
//			$userProperties = [
//				'email'         => $u,
//				'phone'         => '',
//				'first_name' => $u_name,
//				'last_name' => '',
//				'full_name' => $u_name,
//				'source' => 'portfolio-insider-prime',
//				'tags' => 'portfolio-insider-prime',
//			];
//
//			$this->sendDataToSMSSystem($userProperties);
//		}
//		echo 'done';

//		$customer = Customers::find(1575);
//		$cc = new CustomersController();
//		$phones = [
//			'+1 (336) 946-2244',
//			'+1 (610) 888-6106'
//		];
//		$emails = [
//			'gbptouchstone@gmail.com',
//			'jdisante@gmail.com'
//		];
//		$input = [
//			'phones' => json_encode($phones),
//			'emails' => json_encode($emails),
//			'token'   => 'PortInsQezInch111'
//		];
//		dd($this->sendDataToSMSSystem($input, 'https://magicstarsystem.com/api/ungrancellead' ));

//		dd($cc->getPipedriveLeadPhonesEmails($customer));
//		dd(LevelsSalespeople::getSalespersonInfo(23)->toArray());
//		dd(phpinfo());

//		dd($this->getPersonsSources());

//		$invoice = Invoices::where('id',852)->with('customer')->first();

//		$res = $c->refundSequence($invoice);
//		dd($res);
//		$userProperties = [
//			'email'         => 'ahillfinancial@gmail.com',
//			'phone'         => '8479623361',
//			'first_name' => 'Fred',
//			'last_name' => 'Hill',
//			'full_name' => 'Fred Hill',
//			'source' => 'portfolio-insider-prime',
//			'tags' => 'portfolio-insider-prime',
//		];
//		dd($this->sendDataToSMSSystem($userProperties));
//		dd($this->sendDataToKlaviyo($userProperties));

//		dd(
//			$this->sendDataToSMSSystem(
//				[
//					'lead_id' => 377,
//					'token' => 'PortInsQezInch111'
//				],
//				'https://test.magicstarsystem.com/api/ungrancellead'
//			)
//		);

//		$customer = $c->sendDataToFirebase($userProperties);
//		dd($c->getFirebaseUser('kevin@portfolioinsider.com', 'email'));
//		dd($c->getFirebaseCollectionRecord('JAWGa9pT2OeqS6wQoj1bdw6f56r2')); //kevin@portfolioinsider.com
//		dd($c->getFirebaseCollectionRecord('oird7Wwc8UMF8NXi9fJunSY85ai2'));
//		$this->moveSP();
//		dd(Invoices::with('customer')->with('salespeople.salespersone')->get()->toArray());

//		$i = new InvoicesController();
//		$invoice = Invoices::find(1361);
//		dd($i->calcEarning($invoice));
//		dd($this->recalcAll());


//		$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\CreateDeal( 33, 11916517, 1200, 'Test Person', 'lll' ) );
//		$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\SearchPerson( 'test1@test.com' ) );
//		dd($searchPerson);
//		$this->markAllWonOnPipedrive();

//		dd($this->findOwnerOnPipedrive());
//		dd(LevelsSalespeople::getSalespersonInfo(5));

//		$tt = new Pipedrive();
//		$tt->findOwnersOnPipedrive();
//		$pl = new TwillioController();
//		dd($pl->findLeads('ELIZABETH ASHER'));
//		dd($pl->getLeadsByOwner(12165079));
//		dd($pl->getLeadsByOwnerOnePage(11778811,0,500));
//		dd($res  = $pl->getLeadsByOwner(12120214, '(561) 625-8632', 0));
//		$persons = Pipedrive::executeCommand( 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864', new Pipedrive\Commands\FindPerson( 'angefl99@aol.com', 0, 100 ) );
//		dd($persons);

//		$tt = new TwilioNumbersController();
//		dd($tt->getNumber(818));
//		dd($tt->getAvailibleNumber(818));
//		dd($tt->buyNumber('+18188623918'));


//		$this->contacts();
//		$this->contactsFromPipedrive();
//		$this->smsSubsCheck();
//		dd($this->updateAllAnnualSubscriptions());
		// $i = Invoices::with('customer')->find(1893); 

		// echo "<pre>";
		// var_export( $this->getFirebaseUserData($i->customer->email)->customClaims);
		// echo "</pre><br><hr>";

		// echo "<pre>";
		// var_export($this->updateInvoiceToAnnual($i));
		// echo "</pre><br><hr>";

		// echo "<pre>";
		// var_export( $this->getFirebaseUserData($i->customer->email)->customClaims);
		// echo "</pre>";

		// $cc = new CustomersController();
		// dd($cc->updateFirebaseAuth('bd9Ta96ATUMWnGa7fRWRTBgoim33', 'fffffffffff'));
		// dd($cc->updateFirebase('VOxoHr2oIJSikNMYwajxfG4hnkL2', "sub_JTiWV9ElPveXgH"));
		// die;
//		dd($this->updateAllAnnualSubscriptions());
//        $sc = new StripeController();
//        dd($sc->unsunscribeEmailsAndPhones(Customers::where('id',1901)->with('contacts')->first()));
//        $in = Invoices::where( 'id', 11 )->with('customer.contacts')->first();
//        if($in && $in->count() && !empty($in->customer) && !empty($in->customer->contacts)){
//            dd($in->customer->contacts);
//        }
//        $data      = [
//            'first_name'          => 'John',
//            'last_name'           => 'Turek',
//            'email'              => 'turek2168@gmail.com',
//            'phone'        => '+1 (732) 771-1691',
//            'customerId'         => 'cus_JAf1i5AQzpdD4J',
//            'subscriptionId'     => 'sub_JAf1VAd9DvZkLK',
//        ];
//        $res = $this->sendDataToFirebase($data);
//        dd($res);
//        $cc = new CustomersController();
//        dd($cc->updatePipedriveDeal( 60, 0, 'lost' ));
	}

	public function getPersonsSources(){
		ini_set( 'memory_limit', '8024M' );
		set_time_limit( 7200 );
		$customers = Customers::withTrashed()->get();
		foreach($customers as $c){
			$cc = new CustomersController();
			$cc->getPipedriveLeadSources($c);
		}
	}

	public function recalcAll(){
		try {
			$errors   = [];
			$invoices = Invoices::where('access_date', '>','2021-03-29')->withTrashed()->get();
			if ( $invoices && $invoices->count() ) {
				$i = new InvoicesController();
				foreach ( $invoices as $invoice ) {
					$earnings = $i->calcEarning( $invoice );
					if ( ! empty( $earnings ) && count( $earnings ) ) {
						foreach ( $earnings as $salespeople_id => $e ) {
							if ( isset( $e['earnings'] ) ) {
								SecondarySalesPeople::where( 'salespeople_id', $salespeople_id )->where( 'invoice_id', $invoice->id )->update( $e );
							} else {
								$errors[] = 'No earnings for invoice: ' . $invoice->id . ', for salespeople id: ' . $salespeople_id;
							}
						}
					} else {
						$errors[] = 'No earnings for invoice: ' . $invoice->id;
					}
				}
			}
		}
		catch (Exception $ex){
			$errors[] = 'Fatal Error! ' . $ex->getMessage();
		}
		dd($errors);
	}

	public function moveSP(){
		$invoices = Invoices::withTrashed()->get();
		foreach($invoices as $i){
			$invoice_id = $i->id;
			$sp_id = $i->salespeople_id;
			SecondarySalesPeople::create([
				'invoice_id' => $invoice_id,
				'salespeople_id' => $sp_id,
				'sp_type' => 1
			]);
		}
	}

	public function sendDataToSMSSystem($input, $url = 'https://magicstarsystem.com/api/ulpi'){
		try {
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
					return $this->sendError( $error, [], 404,false );
				}
			} else {
				$error = "No response from " . $url;
				return $this->sendError( $error, [], 404,false );
			}
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			return $this->sendError($error, [], 404,false);
		}
	}
	public function sendDataToKlaviyo($input){
		try {
			$klaviyo = new Klaviyo( 'pk_91ce3fe4c8434e2895e341280a7264c1bf', 'UqjjZP' );
			$klaviyo_listId = 'XnKisw';

			$klaviyo_data = [
				'$email'        => $input['email'],
				'$phone_number' => $input['phone'],
				'$first_name'   => $input['first_name'],
				'$last_name'    => $input['last_name'],
			];
			$profile      = new KlaviyoProfile( $klaviyo_data );
			$res          = $klaviyo->lists->addMembersToList( $klaviyo_listId, [ $profile ] );

			return $this->sendResponse( $res , '');

		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			return $this->sendError($error);
		}
	}

	public function markAllWonOnPipedrive(){
		try {
			ini_set('memory_limit', '8024M');
			set_time_limit(72000);
			$invoices = Invoices::select('invoices.*', 'customers.email')
								->join('customers', function ( $join ) {
									$join->on( 'customers.id', 'invoices.customer_id' );
								})
								->leftJoin('sent_data', function ( $join ) {
									$join->on( 'sent_data.customer_id', 'invoices.customer_id' )
									     ->where('sent_data.service_type', 5)
									;
								})
								->whereNull('sent_data.customer_id')
								->orderBy('invoices.id', 'asc')
								->skip(0)
								->take(1000)
								->get()
			;
//			dd($invoices->toArray());

			$emailsExisted = [];
			$emailsNotFounded = [];
			$moreThenOneDeal = [];
			$errors = [];

			$i = 0;
			$invoices_total = count($invoices);
			echo '<h1>Invoices Total: '.$invoices_total.'</h1>';
			foreach($invoices as $invoice){

				$i++;
				if($i == 1){
					echo '<h1>First Invoice id is: '.$invoice->id.'</h1>';
				}
				if($i >= $invoices_total){
					echo '<h1>Last Invoice id is: '.$invoice->id.'</h1>';
				}

				$email = $invoice->email;
				$customer_id = $invoice->customer_id;
				$sales_price = $invoice->sales_price;

					$emailsToProcess[$customer_id] = $email;
					$key = config( 'pipedrive.api_key' );
//					$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
				    $searchPerson = Pipedrive::executeCommand( $key, new Pipedrive\Commands\SearchPerson( $email ) );
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
										$person =  $itm->item;
										$person_id = $person->id;
										$emailsExisted[$customer_id] = [
											'email' => $email,
											'person_id' => $person_id,
											'sales_price' => $sales_price,
										];
										$deals = Pipedrive::executeCommand( $key, new Pipedrive\Commands\SearchDeal( $person_id ) );

										if (
											!empty($deals) &&
											!empty($deals->data) &&
											count($deals->data)
										)
										{
											$all_deals= [];
											foreach($deals->data as $d){
												if(
													($d->status != 'won') ||
													(($sales_price*1) !== (($d->value)*1))
												){
													$updated_deal = Pipedrive::executeCommand( $key, new Pipedrive\Commands\UpdateDeal( $d->id, $sales_price, 'won'  ) );
													if (
														!empty($updated_deal) &&
														!empty($updated_deal->data) &&
														!empty($updated_deal->data->id)
													) {
														SentData::create([
															'customer_id' => $customer_id,
															'value' => $d->id,
															'field' => 'deal_id',
															'service_type' => 5 // pipedrive,
														]);
													}
													else {
														$errors[$customer_id] = "Pipedrive: Can't update deal";
													}
												}
												else{
													SentData::create([
														'customer_id' => $customer_id,
														'value' => $d->id,
														'field' => 'deal_id',
														'service_type' => 5 // pipedrive,
													]);
												}
												$all_deals[] = [
													'id' => $d->id,
													'status' => $d->status,
													'value' => $d->value,
												];
											}

											$emailsExisted[$customer_id]['deals'] = $all_deals;
											if(count($all_deals)>1){
												$moreThenOneDeal[$customer_id]['deals'] = $all_deals;
											}
										}
										else{
											//no deals found
											$owner_id = 0;
											if(!empty($person->owner) && !empty($person->owner->id)){
												$owner_id = $person->owner->id;
											}
											$deal = Pipedrive::executeCommand( $key, new Pipedrive\Commands\CreateDeal( $person->id, $owner_id, $sales_price, $person->name ) );
											if (
												!empty($deal) &&
												!empty($deal->data) &&
												!empty($deal->data->id)
											){
												SentData::create([
													'customer_id' => $customer_id,
													'value' => $deal->data->id,
													'field' => 'deal_id',
													'service_type' => 5 // pipedrive,
												]);
											}
											else {
												$errors[$customer_id] = "Can't create deal";
											}
										}
									}
								}
							}
						}

					}
					else{
						$emailsNotFounded[$customer_id] = $email;
					}



			}

			echo '<h1>Errors total: '.count($errors).'</h1>';
			echo "<pre>";
			var_export($errors);
			echo "</pre>";

			echo '<h1>Found '.count($emailsExisted).' people on Pipedrive</h1>';
			echo "<pre>";
			var_export($emailsExisted);
			echo "</pre>";

			echo '<h1 style="margin-top:1rem;">'.count($emailsNotFounded).' emails not found on Pipedrive</h1>';
			echo "<pre>";
			var_export($emailsNotFounded);
			echo "</pre>";

			echo '<h1 style="margin-top:1rem;">'.count($moreThenOneDeal).' people have more then one deals on Pipedrive</h1>';
			echo "<pre>";
			var_export($moreThenOneDeal);
			echo "</pre>";


		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			dd($error);
		}
	}























	public function contacts(){
		$invoices = Invoices::with('customer')->get();
		foreach($invoices as $i){
			$contactData = [
				'customer_id' =>  $i->customer->id,
				'is_main_for_invoice_id' => $i->id,
				'user_id' => 1
			];
			$phone = !empty($i->customer->phone_number) ? trim(strtolower($i->customer->phone_number)) : '';
			$email = !empty($i->customer->email) ? trim(strtolower($i->customer->email)) : '';
			$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($phone);
			if($formated_phone_number) {
				$if_phone_exist = CustomersContacts::where( 'customer_id', $i->customer->id )->where( 'contact_type', 1 )->where( 'formated_contact_term', $formated_phone_number )->count();

				if ( ! $if_phone_exist ) {
					$contactData['contact_type']          = 1;
					$contactData['contact_term']          = $phone;
					$contactData['formated_contact_term'] = $formated_phone_number;
					CustomersContacts::create( $contactData );
				}
			}

			
			$if_email_exist = CustomersContacts::where('customer_id', $i->customer->id)->where('contact_type', 0)->where('formated_contact_term', $email)->count();
			if(!$if_email_exist){
				$contactData['contact_type'] = 0;
				$contactData['contact_term'] = $email;
				$contactData['formated_contact_term'] = $email;
				CustomersContacts::create($contactData);
			}

		}
		echo "done";
	}

	public function contactsFromPipedrive(){
		$invoices = Invoices::with('customer')->get();
		foreach($invoices as $i){
			$contactData = [
				'customer_id' =>  $i->customer->id,
				'user_id' => 1
			];
			$cc = new CustomersController();
			$phonesAndEmails = $cc->getPipedriveLeadPhonesEmails($i->customer);
			if(!empty($phonesAndEmails) && !empty($phonesAndEmails['data'])) {
				$phones = ! empty( $phonesAndEmails['data']['phones'] ) ? $phonesAndEmails['data']['phones'] : [];
				if($phones && count($phones)){
					foreach($phones as $phone){
						$phone = trim(strtolower($phone));
						if($phone) {
							$formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($phone);
							if($formated_phone_number) {
								$if_phone_exist = CustomersContacts::where( 'customer_id', $i->customer->id )->where( 'contact_type', 1 )->where( 'formated_contact_term', $formated_phone_number )->count();
								if ( ! $if_phone_exist ) {
									$contactData['contact_type']          = 1;
									$contactData['contact_term']          = $phone;
									$contactData['formated_contact_term'] = $formated_phone_number;
									CustomersContacts::create( $contactData );
								}
							}

						}
					}
				}
				$emails = ! empty( $phonesAndEmails['data']['emails'] ) ? $phonesAndEmails['data']['emails'] : [];
				if($emails && count($emails)){
					foreach($emails as $email){
						$email = trim(strtolower($email));
						if($email) {
							$if_email_exist = CustomersContacts::where( 'customer_id', $i->customer->id )->where( 'contact_type', 0 )->where( 'formated_contact_term', $email )->count();
							if ( ! $if_email_exist ) {
								$contactData['contact_type'] = 0;
								$contactData['contact_term'] = $email;
								$contactData['formated_contact_term'] = $email;
								CustomersContacts::create( $contactData );
							}

						}
					}
				}
			}

		}
		echo "done";
	}



	public function checkSmsSubsPhone($phone){
		$data = [
			'phone' => $phone,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data, $url = 'https://magicstarsystem.com/api/stugelvichak');

	}
	public function checkSmsSubsEmail($email){
		$data = [
			'email' => $email,
			'token'   => 'PortInsQezInch111'
		];
		return $this->sendDataToSMSSystem($data, $url = 'https://magicstarsystem.com/api/stugelvichak');
	}


	public function smsSubsCheck(){
		$invoices = Invoices::with('customer')->get();
		$cc = new CustomersController();
		foreach($invoices as $i){
			if(!empty($i->customer)) {
				$cc->subscriptionsCheck($i->customer->id, 1, $i->id);
			}
		}
		echo "Done!";
	}


	public function updateAllAnnualSubscriptions(){
		$annual_invoices = Invoices::where('pdftemplate_id', 4)->where('product_id', 1)->with('customer')->orderBy('id', 'desc')->limit(20)->get();
		$result = [];
		foreach($annual_invoices as $i){
			$result[] = $this->updateInvoiceToAnnual($i);
		}
		echo "<pre>";
		var_export($result);
		echo "</pre>";die;
	}

	public function updateInvoiceToAnnual(Invoices $invoice){
		try{
			$cc = new CustomersController();
			$res = $cc->getFirebaseUserData($invoice->customer->email);
			$err_mess = '';
			if(!empty($res['uid']) && !empty($res['subscriptionId']) && !empty($res['customerId'])){
			
					$cc->createStripe();
					$res_subs = $cc->createStripeSubscription($res['customerId'], 2);
					if($res_subs['success'] && !empty($res_subs['data']->id)){
						$res_upd = $cc->updateFirebase($res['uid'], $res_subs['data']->id);
						if(!empty($res_upd) && !empty($res_upd['success'])){
                                $invoice_data_to_save = [
                                    'product_id' => 2,
                                    'old_prime_subs_id' => $res['subscriptionId'],
                                ];
                                if(!empty($res_subs['data']['id'])){
                                    $invoice_data_to_save['stripe_subscription_id'] = $res_subs['data']['id'];
                                }

                                if(!empty($res_subs['data']['current_period_end'])){
                                    $invoice_data_to_save['stripe_current_period_end'] = date("Y-m-d H:i:s",$res_subs['data']['current_period_end']);
                                }
                                if(!empty($res_subs['data']['current_period_start'])){
                                    $invoice_data_to_save['stripe_current_period_start'] = date("Y-m-d H:i:s",$res_subs['data']['current_period_start']);
                                }
                                if(!empty($res_subs['data']['status'])){
                                    $invoice_data_to_save['stripe_subscription_status'] = Invoices::STRIPE_STATUSES[$res_subs['data']['status']];
                                }

							$res_invoice_update = Invoices::where('id', $invoice->id)->update($invoice_data_to_save);
							if($res_invoice_update){
								$cc->updateFirebaseAuth($res['uid'], $res_subs['data']->id, $res['customerId']);
								$cc->subscriptionsCheck($invoice->customer_id, 1 );
								return $invoice->customer->email;
							}
							else {
								$err_mess = 'Can not update invoice for ' . $invoice->customer->email;
							}
						}
						else {
							$err_mess = 'Can not update firebase for ' . $invoice->customer->email;
						}
					
					}
					else {
						$err_mess = 'Can not find stripe customer for ' . $invoice->customer->email;
					}
			}
			else {
				$err_mess = 'No subscription Id or no user on Firebase for ' . $invoice->customer->email;
			}
			Errors::create([
				'error' => $err_mess,
				'controller' => 'TestController',
				'function' => 'updateInvoiceToAnnual'
			]);
			return $err_mess;
		}
		catch (Exception $ex){
			$err_mess = $ex->getMessage();
			Errors::create([
				'error' => $err_mess,
				'controller' => 'TestController',
				'function' => 'updateInvoiceToAnnual'
			]);
			return $err_mess . ' email: '.$invoice->customer->email;
		}
	}

	public function getFirebaseUserData($email){
		try {
			$cc = new CustomersController();
			$firebase = $cc->findFirebaseUser( $email );
			if ( !empty($firebase) && !empty($firebase->uid) ) {
				return $firebase;				
			}
			return false;
		} catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'TestController',
				'function' => 'getFirebaseUserData'
			]);
			return false;
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
    public function createFirebase(){
        try{
            if(config( 'firebase.file_name' )) {
//                $conf = 'portfolio-insider-18e90e011d22.json';
                $conf = config( 'firebase.file_name' );
                $this->firebase = ( new Factory )->withServiceAccount( storage_path( $conf ) );
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

}
