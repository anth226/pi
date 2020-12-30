<?php

namespace App\Http\Controllers;


use App\Customers;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\SecondarySalesPeople;
use Exception;

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
//			'email'         => '',
//			'phone'         => '310 405 9772',
//			'first_name' => 'Dan',
//			'last_name' => '',
//			'source' => 'portfolioinsider',
//			'tags' => 'portfolioinsider,portfolio-insider-prime',
//		];
//		dd($this->sendDataToSMSSystem($userProperties));
//		$customer = $c->sendDataToFirebase($userProperties);
//		dd($c->getFirebaseUser('kevin@portfolioinsider.com', 'email'));
//		dd($c->getFirebaseCollectionRecord('JAWGa9pT2OeqS6wQoj1bdw6f56r2')); //kevin@portfolioinsider.com
//		dd($c->getFirebaseCollectionRecord('oird7Wwc8UMF8NXi9fJunSY85ai2'));
//		$this->moveSP();
//		dd(Invoices::with('customer')->with('salespeople.salespersone')->get()->toArray());
//		$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\CreateDeal( 33, 11916517, 1200, 'Test Person', 'lll' ) );
//		$searchPerson = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\SearchPerson( 'test1@test.com' ) );
//		dd($searchPerson);
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

}
