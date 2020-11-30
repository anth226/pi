<?php

namespace App\Http\Controllers;

use App\Customers;
use App\Errors;
use App\Invoices;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\Products;
use App\Salespeople;
use App\SecondarySalesPeople;
use App\SentDataLog;
use Illuminate\Http\Request;
use Validator;
use Exception;

class CustomerInvoiceController extends CustomersController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:customer-list|customer-create|customer-edit|customer-delete', ['only' => ['index','show']]);
		$this->middleware('permission:customer-create,permission:invoices-create', ['only' => ['create','store']]);
		$this->middleware('permission:customer-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:customer-delete', ['only' => ['destroy']]);
	}

	public function create()
	{
		$states = UsStates::statesUS();
		$salespeople = Salespeople::getIdsAndFullNames();
		$products = Products::getIdsAndFullNames();
		return view('customers.createandsend', compact('states', 'salespeople','products'));
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
			'zip' => 'required|digits:5',
			'city' => 'required|max:120',
			'state' => 'required||max:20',
			'email' => 'required|unique:customers,email,NULL,id,deleted_at,NULL|email|max:120',
			'phone_number' => 'required|max:120|min:10',
			'salespeople_id' => 'required|numeric|min:1',
			'product_id' => 'required|numeric|min:1',
			'sales_price' => 'required',
			'qty' => 'required|numeric|min:1',
			'access_date' => 'required',
			'cc' => 'required|digits:4'
		]);


		$customer = Customers::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'address_1' => $request->input('address_1'),
			'address_2' => !empty($request->input('address_2')) ? $request->input('address_2') : '',
			'city' => $request->input('city'),
			'state' => $request->input('state'),
			'zip' => $request->input('zip'),
			'email' => $request->input('email'),
			'phone_number' => $request->input('phone_number'),
			'formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')),
		]);


		$sales_price = !empty($request->input('sales_price')) ? Elements::moneyToDecimal($request->input('sales_price')) : 0;
		if(!$sales_price){
			return redirect()->route('customers-invoices.create')
			                 ->withErrors(['Please enter correct price.'])
			                 ->withInput();
		}

		if($customer && !empty($customer->id)){
			$invoice = Invoices::create([
				'customer_id' => $customer->id,
				'salespeople_id' => $request->input('salespeople_id'),
				'product_id' => $request->input('product_id'),
				'sales_price' => $sales_price,
				'qty' => $request->input('qty'),
				'access_date' => Elements::createDateTime($request->input('access_date')),
				'cc' => $request->input('cc')
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

			$dataToSend = [
				'first_name' => $request->input('first_name'),
				'last_name' => $request->input('last_name'),
				'full_name' => $request->input('first_name').' '.$request->input('last_name'),
				'email' => $request->input('email'),
				'phone' => $request->input('phone_number'),
				'source' => 'portfolioinsider',
				'tags' => 'portfolioinsider,portfolio-insider-prime'
			];

			$this->sendLead($dataToSend, $customer->id);

			return redirect()->route('invoices.show', $invoice->id)
			                 ->with('success','Invoice created successfully');
		}


		return redirect()->route('customers-invoices.create')
		                 ->withErrors(['Something went wrong.'])
		                 ->withInput();


	}
}
