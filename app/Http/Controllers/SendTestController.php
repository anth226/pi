<?php

namespace App\Http\Controllers;

use App\KmClasses\Sms\EmailSender;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController;

use Validator, Input;



class SendTestController extends BaseController
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//$this->middleware(['auth','verified','approved']);
		$this->middleware(['auth','verified']);
		$this->middleware('permission:send-email-test', ['only' => ['sendEmailTest']]);
	}

	public function sendEmailTest(Request $request){

	}

}
