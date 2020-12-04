<?php

namespace App\Http\Controllers;


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
		dd(phpinfo());
		$c = new CustomersController();
		$userProperties = [
			'email'         => 'qzzze@example.com',
			'phone'         => '464-654-6464',
			'first_name' => 'Kev',
			'last_name' => 'M',
			'customerId' => 'cus_IMR5waCodpvTWw',
			'subscriptionId' => 'sub_IMR5gIal1yW5Bq'
		];
		$customer = $c->sendDataToFirebase($userProperties);
		dd($c->getFirebaseUser($customer->uid));
//		dd($c->getFirebaseUser('JAWGa9pT2OeqS6wQoj1bdw6f56r2')); //JAWGa9pT2OeqS6wQoj1bdw6f56r2

	}

}
