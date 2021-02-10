<?php

namespace App\Http\Controllers;

use App\InvoiceGenerator;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceGeneratorController extends InvoicesController
{
	public function create(Request $request)
	{
		$states = UsStates::statesUS();
		return view('invoices.invoicegenerator', compact('states'));
	}

	public function index(Request $request)
	{
		return abort(404);
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
			'address_1' => 'max:120',
			'zip' => 'max:120',
			'city' => 'max:120',
			'state' => 'max:20',
			'email' => 'required|email|max:120',
			'phone_number' => 'max:120',
			'sales_price' => 'required',
			'access_date' => 'required',
			'cc' => 'digits:4|nullable'
		]);

		$sales_price = !empty($request->input('sales_price')) ? Elements::moneyToDecimal($request->input('sales_price')) : 0;
		if(!$sales_price){
			return $this->sendError('Please enter correct price.');
		}

		$discounts = [];
		$discount_total = 0;
		foreach ($request->request as $k=>$v){
			if(strpos($k, 'discountamount') !== false){
				$disc_array = explode('_', $k);
				$discount_id = $disc_array[1];
				$amount = Elements::moneyToDecimal($v);
				if(!empty($amount)) {
					$discounts[] = [
						'amount' => $amount,
						'title' => !empty($request->input('discounttitle_'.$discount_id)) ? $request->input('discounttitle_'.$discount_id) : 'Discount'
					];
					$discount_total += $amount*1;
				}
			}
		}

		$grand_total = $sales_price - $discount_total;
		if($grand_total < 0){
			return $this->sendError('Sales price must be more than Grand Total.');
		}

		$paid = !empty($request->input('paid')) ? Elements::moneyToDecimal($request->input('paid')) : 0;
		if((($sales_price - $paid) < 0) || (($grand_total - $paid) < 0)){
			return $this->sendError('Paid amount must be less than Sales Price or Grand Total.');
		}

		$dataToSend = [
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'email' => strtolower($request->input('email')),
			'phone' => !empty($request->input('phone_number')) ? $request->input('phone_number') : '',
			'address_1' => !empty($request->input('address_1')) ? $request->input('address_1') : '',
			'address_2' => !empty($request->input('address_2')) ? $request->input('address_2') : '',
			'city' => !empty($request->input('city')) ? $request->input('city') : '',
			'state' => !empty($request->input('state')) ? $request->input('state') : '',
			'zip' => !empty($request->input('zip')) ? $request->input('zip') : '',
			'formated_phone_number' => !empty($request->input('phone_number')) ? FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')) : '',
			'access_date' => Elements::createDateTime($request->input('access_date')),
			'cc_number' => !empty($request->input('cc')) ? $request->input('cc') : '',
			'sales_price' => $sales_price,
			'grand_total' => $grand_total,
			'discount_total' => $discount_total,
			'paid' => $paid,
			'own' => $grand_total - $paid,
			'discounts' => $discounts
		];

		dd($dataToSend);

		$invoice = InvoiceGenerator::create([
			'first_name' => $dataToSend['first_name'],
			'last_name' => $dataToSend['last_name'],
			'email' => $dataToSend['email'],
			'access_date' => $dataToSend['access_date'],
			'own' => $dataToSend['own'],
			'invoice_data' => $dataToSend
		]);

		$this->generatePDF($invoice->id);
		return $this->sendResponse($invoice->id);

	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		return abort(404);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		return abort(404);
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		return abort(404);
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		return abort(404);
	}


	public function generatePDF($id){
		$invoice = InvoiceGenerator::find($id);
		if($invoice && $invoice->invoice_data && $invoice->invoice_data->count()) {
			$invoice = $invoice->invoice_data;
			$item_price = $invoice->sales_price;
			if(($item_price - $invoice->sales_price) <=0 ) {
				$item_price = $invoice->sales_price;
			}
			$invoice->invoice_number = $this->generateInvoiceNumber($invoice->customer->id);
			$price_before_discount = $this->moneyFormat( $item_price );
			$total_before_discount = $this->moneyFormat( $item_price * $invoice->qty );
			$discount = $this->moneyFormat( ($item_price - $invoice->sales_price) * $invoice->qty );
			$total = $this->moneyFormat( $invoice->sales_price * $invoice->qty );
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$file_name = $this->generateFileName($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			PDF::setOptions(['dpi' => 400]);
			$pdf = PDF::loadView('pdfviewmain', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'price_before_discount', 'total_before_discount', 'discount' ));
			$pdf->save($this->pdf_path.$file_name);
			$invoice->save();
			return true;

		}
		return false;
	}
}
