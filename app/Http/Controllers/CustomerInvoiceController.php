<?php

namespace App\Http\Controllers;

use App\Customers;
use App\Invoices;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\Products;
use App\Salespeople;
use App\SecondarySalesPeople;
use App\SentData;
use App\SentDataLog;
use Illuminate\Http\Request;
use Validator;


class CustomerInvoiceController extends CustomersController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:customer-list|customer-create|customer-edit|customer-delete', ['only' => ['index','show']]);
		$this->middleware('permission:customer-create|invoice-create', ['only' => ['create','store']]);
		$this->middleware('permission:customer-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:customer-delete', ['only' => ['destroy']]);
	}


	public function index(Request $request)
	{
		return view('customers.createandsendindex');
	}

	public function anyData(Request $request){
		$query =  Customers::with('invoices')->with('invoices.salespersone')->with('invoices.salespeople.salespersone');
		return datatables()->eloquent($query)->toJson();
	}

	public function create(Request $request)
	{
		$states = UsStates::statesUS();
		$salespeople = Salespeople::getIdsAndFullNames();
		$products = Products::getIdsAndFullNames();
		$test_mode = !empty($request->input('test_mode')) ? $request->input('test_mode') : 0;
		return view('customers.createandsend', compact('states', 'salespeople','products', 'test_mode'));
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'required|max:120',
			'address_1' => 'required|max:120',
			'zip' => 'required|max:120',
			'city' => 'required|max:120',
			'state' => 'required|max:20',
			'email' => 'required|unique:customers,email,NULL,id,deleted_at,NULL|email|max:120',
			'phone_number' => 'required|max:120|min:10',
			'salespeople_id' => 'required|numeric|min:1',
			'product_id' => 'required|numeric|min:1',
			'sales_price' => 'required',
			'qty' => 'required|numeric|min:1',
			'access_date' => 'required',
			'cc' => 'required|digits:4'
		]);

		$test_mode = !empty($request->input('test_mode')) ? $request->input('test_mode') : 0;

		$sales_price = !empty($request->input('sales_price')) ? Elements::moneyToDecimal($request->input('sales_price')) : 0;
		if(!$sales_price){
			return redirect()->route('customers-invoices.create')
			                 ->withErrors(['Please enter correct price.'])
			                 ->withInput();
		}


		$dataToSend = [
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'full_name' => $request->input('first_name').' '.$request->input('last_name'),
			'email' => $request->input('email'),
			'phone' => $request->input('phone_number'),
			'source' => 'portfolioinsider',
			'tags' => 'portfolioinsider,portfolio-insider-prime',
			'address_1' => $request->input('address_1'),
			'address_2' => !empty($request->input('address_2')) ? $request->input('address_2') : '',
			'city' => $request->input('city'),
			'state' => $request->input('state'),
			'zip' => $request->input('zip'),
			'phone_number' => $request->input('phone_number'),
			'formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')),
		];

		if(!$test_mode) {
			//////////// sending data
//			$stripe_res = $this->sendDataToStripe($dataToSend);
//			if(!$stripe_res['success']){
//				$message = 'Error! Can\'t send data to stripe';
//				if(!empty($stripe_res['message'])){
//					$message = $stripe_res['message'];
//				}
//				return back()->withErrors([$message])
//				             ->withInput();
//			}
//			else {
//				$dataToSend['customerId']     = $stripe_res['data']['customer'];
//				$dataToSend['subscriptionId'] = $stripe_res['data']['id'];
//
//				$firebase_res = $this->sendDataToFirebase( $dataToSend );
//				if ( ! $firebase_res['success'] ) {
//					$message = 'Error! Can\'t send data to firebase';
//					if ( ! empty( $firebase_res['message'] ) ) {
//						$message = $firebase_res['message'];
//					}
//
//					return back()->withErrors( [ $message ] )
//					             ->withInput();
//				}
//
//				$klaviyo_res = $this->sendDataToKlaviyo( $dataToSend );
//				if ( ! $klaviyo_res['success'] ) {
//					$message = 'Error! Can\'t send data to klaviyo';
//					if ( ! empty( $stripe_res['message'] ) ) {
//						$message = $stripe_res['message'];
//					}
//
//					return back()->withErrors( [ $message ] )
//					             ->withInput();
//				}
//			}

			$smssystem_res = $this->sendDataToSMSSystem($dataToSend);
			if(!$smssystem_res['success']){
				$message = 'Error! Can\'t send data to SMS System';
				if(!empty($smssystem_res['message'])){
					$message = $smssystem_res['message'];
				}
				return back()->withErrors([$message])
				             ->withInput();
			}

		}
		else{
			switch($test_mode){
				case 2:
					dd($dataToSend);
					break;
			}
		}

		$customer = Customers::create($dataToSend);

		if($customer && !empty($customer->id)){

			/////////////////////saving data to log
			if(!empty($stripe_res) && !empty($stripe_res['data']) && !empty($stripe_res['data']['id']) && !empty($stripe_res['data']['customer'])){
				SentData::create([
					'customer_id' => $customer->id,
					'value' => $stripe_res['data']['id'],
					'field' => 'subscriber_id',
					'service_type' => 1, // stripe
				]);
				SentData::create([
					'customer_id' => $customer->id,
					'value' => $stripe_res['data']['customer'],
					'field' => 'customer_id',
					'service_type' => 1 // stripe
				]);
			}
			if(!empty($firebase_res) && !empty($firebase_res['data']) && !empty($firebase_res['data']->uid)){
				SentData::create([
					'customer_id' => $customer->id,
					'value' => $firebase_res['data']->uid,
					'field' => 'uid',
					'service_type' => 2 // firebase,
				]);
			}
			if(!empty($klaviyo_res) && !empty($klaviyo_res['data']) && !empty($klaviyo_res['data'][0]) && !empty($klaviyo_res['data'][0]['id'])){
				SentData::create([
					'customer_id' => $customer->id,
					'value' => $klaviyo_res['data'][0]['id'],
					'field' => 'id',
					'service_type' => 3 // klaviyo,
				]);
			}
			if(!empty($smssystem_res) && !empty($smssystem_res['data']) && !empty($smssystem_res['data']->id)){
				SentDataLog::create( [
					'customer_id' => $customer->id,
					'lead_id'     => $smssystem_res['data']->id
				] );
				SentData::create([
					'customer_id' => $customer->id,
					'value' => $smssystem_res['data']->id,
					'field' => 'lead_id',
					'service_type' => 4 // sms_system
				]);
			}
			////////////////////////////////////////////

			$invoice = Invoices::create([
				'customer_id' => $customer->id,
				'salespeople_id' => $request->input('salespeople_id'),
				'product_id' => $request->input('product_id'),
				'sales_price' => $sales_price,
				'qty' => $request->input('qty'),
				'access_date' => Elements::createDateTime($request->input('access_date')),
				'cc_number' => $request->input('cc')
			]);

			$invoice_instance = new InvoicesController();
			$invoice_instance->generatePDF($invoice->id);

			if(!empty($request->input('second_salespeople_id')) && count($request->input('second_salespeople_id'))) {
				foreach ($request->input('second_salespeople_id') as $val){
					SecondarySalesPeople::create( [
						'salespeople_id' => $val,
						'invoice_id'     => $invoice->id
					] );
				}
			}

			return redirect()->route('invoices.show', $invoice->id)
			                 ->with('success','Invoice created successfully');
		}


		return redirect()->route('customers-invoices.create')
		                 ->withErrors(['Something went wrong.'])
		                 ->withInput();


	}


}
