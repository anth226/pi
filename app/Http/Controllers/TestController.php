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
		$res = $c->sendDataToStripe([
			'full_name' => 'test tettt',
			'email' => 'hhahkhgkkjgjk@ttt.vv',
			'phone' => '54646546465'
		]);
		dd($res);


	}

}
