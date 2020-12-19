<?php

namespace App\Http\Controllers;


use App\Customers;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\Salespeople;
use App\SalespeoplePecentageLog;
use App\SecondarySalesPeople;
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
//		dd(phpinfo());
//		$c = new CustomersController();
//		$userProperties = [
//			'email'         => 'gus@portfolioinsider.com',
//			'phone'         => '8184563045',
//			'first_name' => 'Gus',
//			'last_name' => 'J',
//			'full_name' => 'Gus J',
//			'source' => 'portfolioinsider',
//			'tags' => 'portfolioinsider,portfolio-insider-prime',
//		];
//		dd($this->sendDataToSMSSystem($userProperties));
//		dd($this->sendDataToKlaviyo($userProperties));

//		$customer = $c->sendDataToFirebase($userProperties);
//		dd($c->getFirebaseUser('kevin@portfolioinsider.com', 'email'));
//		dd($c->getFirebaseCollectionRecord('JAWGa9pT2OeqS6wQoj1bdw6f56r2')); //kevin@portfolioinsider.com
//		dd($c->getFirebaseCollectionRecord('oird7Wwc8UMF8NXi9fJunSY85ai2'));
//		$this->moveSP();
//		dd(Invoices::with('customer')->with('salespeople.salespersone')->get()->toArray());


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

	public function sendDataToSMSSystem($input, $url = 'https://magicstarsystem.com/api/ulp'){
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

}
