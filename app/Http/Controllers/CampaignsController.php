<?php

namespace App\Http\Controllers;

use App\PhoneTags;
use App\Tags;
use Illuminate\Http\Request;
use App\Campaigns;
use App\CampaignsReports;
use App\PhoneSource;
use App\FormsAnswers;
use Validator,Redirect,Response;
use App\Leads;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\SmsSender;



class CampaignsController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware(['auth','verified']);
//		$this->middleware('permission:view-campaigns-page|view-admin-pages');
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
		return view('campaigns');
	}



}
