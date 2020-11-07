<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoicesController extends Controller
{
	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:manage-invoices');
//		$this->middleware('permission:create-sms-campaign', ['only' => ['store']]);
//		$this->middleware('permission:create-call-campaign', ['only' => ['storecall']]);
//		$this->middleware('permission:delete-campaign', ['only' => ['delete']]);
//		$this->middleware('permission:send-campaign', ['only' => ['send']]);
//		$this->middleware('permission:cancel-campaign', ['only' => ['cancel']]);
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */


	public function index()
	{
		return view('invoices');
	}
}
