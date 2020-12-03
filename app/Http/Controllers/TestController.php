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
		$userProperties = [
			'email'         => 'user10@example.com',
//			'emailVerified' => false,
			'password'      => 'secretPassword6',
//			'disabled'      => false,
//			'metadata'      => [
//				'lastSignInDate' => date( 'D M d Y H:i:s O' ),
//			],
		];
		dd($c->sendDataToFirebase($userProperties));
//		dd($c->getFirebaseUser('7ReNZAV04iYyH8Y0yXmKZUimtab2'));
//		$res = $c->sendDataToStripe([
//			'full_name' => 'test tettt',
//			'email' => 'hhahkhgkkjgjk@ttt.vv',
//			'phone' => '54646546465'
//		]);
//		dd($res);
//		dd(config('firebase'));

	}

}
