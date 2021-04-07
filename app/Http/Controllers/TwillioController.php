<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TwillioController extends Controller
{

	public function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware('permission:salespeople-reports-view-all');
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		return view('twilio.call');
	}

}
