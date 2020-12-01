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
		$c = new CustomersController();
		$client = $c->createStripeCustomer();
		echo "<pre>";
		var_export($client->id);
		echo "</pre>";
		if($client && !empty($client->id)){
			$subscription = $c->createStripeSubscription($client->id);
			echo "<pre>";
			var_export($subscription->id);
			echo "</pre>";
		}

	}

}
