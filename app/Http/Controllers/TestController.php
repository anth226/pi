<?php

namespace App\Http\Controllers;


use App\Customers;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\LevelsSalespeople;
use App\Salespeople;
use App\SalespeoplePecentageLog;

use App\KmClasses\Pipedrive;

use App\SecondarySalesPeople;
use App\SentData;
use Exception;
use DB;
use Klaviyo\Klaviyo as Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;

class TestController extends BaseController
{
	public function __construct()
	{
		$this->middleware(['auth','verified']);
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */

	public function index()
	{
//		dd(LevelsSalespeople::getSalespersonInfo(23)->toArray());
//		dd(phpinfo());
		$c = new CustomersController();
		$res = $c->getPipedriveLeadSources('rson226@gmail.com', 1156);
		dd($res);
//		$invoice = Invoices::where('id',852)->with('customer')->first();
//		$res = $c->refundSequence($invoice);
//		dd($res);
//		$userProperties = [
//			'email'         => 'mike.santens@gmail.com',
//			'phone'         => '(703) 489-1872',
//			'first_name' => 'Mike',
//			'last_name' => 'Santens',
//			'full_name' => 'Mike Santens',
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
//		$invoice = Invoices::find(734);
//		dd($i->calcEarning($invoice));
//		dd($this->recalcAll());


//		$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\CreateDeal( 33, 11916517, 1200, 'Test Person', 'lll' ) );
//		$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\SearchPerson( 'test1@test.com' ) );
//		dd($searchPerson);
//		$this->markAllWonOnPipedrive();

//		dd($this->findOwnerOnPipedrive());
//		dd(LevelsSalespeople::getSalespersonInfo(5));
	}

	public function recalcAll(){
		try {
			$errors   = [];
			$invoices = Invoices::withTrashed()->get();
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
				if ( $result && ! empty( $result->success ) && $result->success && ! empty( $result->data ) ) {
					return $this->sendResponse( $result->data, '' );
				} else {
					$error = "Wrong response from " . $url;
					if ( $result && ! empty( $result->success ) && ! $result->success && ! empty( $result->message ) ) {
						$error = $result->message;
					}
					return $this->sendError( $error );
				}
			} else {
				$error = "No response from " . $url;
				return $this->sendError( $error );
			}
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			return $this->sendError($error);
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
													$updated_deal = Pipedrive::executeCommand( $key, new Pipedrive\Commands\UpdateDeal( $d->id, $sales_price  ) );
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

	public function findOwnerOnPipedrive(){
		try {
			$key = config( 'pipedrive.api_key' );
//			$key = 'fbdff7e0ac6e80b3b3c6e4fbce04e00f10b37864';
			$salespeople = Salespeople::withTrashed()->get();
			$allUsers    = Pipedrive::executeCommand( $key, new Pipedrive\Commands\getAllUsers() );
//			dd($allUsers);
			if (
				! empty( $salespeople ) &&
				$salespeople->count() &&
				! empty( $allUsers ) &&
				! empty( $allUsers->data ) &&
				! empty( count($allUsers->data) )
			) {
				foreach ( $salespeople as $s ) {
					if ( ! empty( $s->email ) ) {
						foreach ( $allUsers->data as $u ) {
							if (
								!empty($u) &&
								!empty($u->id) &&
								!empty($u->email) &&
								trim( strtolower( $u->email ) ) == trim( strtolower( $s->email ) )
							) {
								Salespeople::where( 'id', $s->id )->update( [ 'pipedrive_user_id' => $u->id ] );
								echo "<pre>";
								var_export($u->id);
								echo "</pre>";
							}
						}
					}
				}
				return true;
			}
		}
		catch(Exception $ex){
			return $ex->getMessage();
		}
	}

}
