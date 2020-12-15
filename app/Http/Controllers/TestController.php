<?php

namespace App\Http\Controllers;


use App\Invoices;
use App\SecondarySalesPeople;

class TestController extends Controller
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
//			'email'         => 'qzzzeeee@example.com',
//			'phone'         => '464-654-6464',
//			'first_name' => 'Kevin',
//			'last_name' => 'Mart',
//			'customerId' => 'cus_IMR5waCodpvTWw',
//			'subscriptionId' => 'sub_IMR5gIal1yW5Bq'
//		];
//		dd($c->sendDataToKlaviyo($userProperties));
//		$customer = $c->sendDataToFirebase($userProperties);
//		dd($c->getFirebaseUser('kevin@portfolioinsider.com', 'email'));
//		dd($c->getFirebaseCollectionRecord('JAWGa9pT2OeqS6wQoj1bdw6f56r2')); //kevin@portfolioinsider.com
//		dd($c->getFirebaseCollectionRecord('oird7Wwc8UMF8NXi9fJunSY85ai2'));
		$this->moveSP();

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

}
