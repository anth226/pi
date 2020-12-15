<?php

namespace App\Http\Controllers;

use App\Customers;
use App\EmailLogs;
use App\EmailTemplates;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\Products;
use App\Salespeople;
use App\SecondarySalesPeople;
use App\SentData;
use Exception;
use Illuminate\Http\Request;
use PDF;

class InvoicesController extends BaseController
{
	protected $full_path, $app_url;
	public $pdf_path;

	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:invoice-list|invoice-create|invoice-edit|invoice-delete', ['only' => ['index','show', 'showPdf']]);
		$this->middleware('permission:invoice-create', ['only' => ['create','store']]);
		$this->middleware('permission:invoice-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:invoice-delete', ['only' => ['destroy']]);
		$this->pdf_path = base_path().'/resources/views/invoicesPdf/';
		$this->full_path =  config('app.url').'/pdfview/';
		$this->app_url =  config('app.url');

	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$invoices = Invoices::orderBy('id','DESC')->paginate(10);
		return view('invoices.index',compact('invoices'))
			->with('i', ($request->input('page', 1) - 1) * 10);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		$customerId = !empty($request->input('customer_id')) ? $request->input('customer_id') : 0;
		$customers = Customers::getIdsAndFullNames();
		$salespeople = Salespeople::getIdsAndFullNames();
		$products = Products::getIdsAndFullNames();
		return view('invoices.create', compact('customers','salespeople', 'customerId', 'products'));
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
			'customer_id' => 'required|numeric|min:1',
			'salespeople_id' => 'required|numeric|min:1',
			'product_id' => 'required|numeric|min:1',
			'sales_price' => 'required',
			'qty' => 'required|numeric|min:1',
			'access_date' => 'required',
			'cc' => 'required|digits:4',
		]);

		$sales_price = !empty($request->input('sales_price')) ? Elements::moneyToDecimal($request->input('sales_price')) : 0;
		if(!$sales_price){
			return redirect()->route('invoices.create')
							 ->withErrors(['Please enter correct price.'])
							 ->withInput();
		}

		$invoice = Invoices::create([
			'customer_id' => $request->input('customer_id'),
			'salespeople_id' => $request->input('salespeople_id'),
			'product_id' => $request->input('product_id'),
			'sales_price' => $sales_price,
			'qty' => $request->input('qty'),
			'access_date' => Elements::createDateTime($request->input('access_date')),
			'cc_number' => $request->input('cc')
		]);

		$this->generatePDF($invoice->id);

		return redirect()->route('invoices.show', $invoice->id)
		                 ->with('success','Invoice created successfully');
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$invoice = Invoices::
							with('customer')
		                   ->with('salespeople.salespersone')
		                   ->with('product')
		                   ->find($id);
		if($invoice) {
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$total = $this->moneyFormat( $invoice->sales_price * $invoice->qty );
			$file_name = $this->generateFileName($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			$template = EmailTemplates::getIdsAndFullNames();
			$sentLog = SentData::where('customer_id', $invoice->customer->id)->orderBy('id', 'asc')->get();
			$logs = EmailLogs::where('invoice_id', $id)->get();

			$states = UsStates::statesUS();
			$salespeople = Salespeople::getIdsAndFullNames();
			return view( 'invoices.show', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'template', 'logs','sentLog', 'states', 'salespeople') );
		}
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
		$invoice = Invoices::
		with('customer')
		                   ->with('salespersone')
		                   ->with('product')
		                   ->find($id);
		if($invoice) {
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			return view( 'invoices.edit', compact( 'invoice', 'formated_price', 'access_date' ) );
		}
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
		try{
			$dataToUpdate = [];
			$this->validate($request, [
				'salespeople_id' => 'required|numeric|min:1',
				'sales_price' => 'required',
				'access_date' => 'required',
				'cc_number' => 'required|digits:4'
			]);
			$dataToUpdate['sales_price'] = Elements::moneyToDecimal($request->input('sales_price'));
			$dataToUpdate['access_date'] = Elements::createDateTime($request->input('access_date'));
			$dataToUpdate['cc_number'] = $request->input('cc_number');
			$dataToUpdate['salespeople_id'] = $request->input('salespeople_id');

			$invoice = Invoices::where('id', $id)->update($dataToUpdate);

			SecondarySalesPeople::where('invoice_id', $id)->delete();

			SecondarySalesPeople::create( [
				'salespeople_id' => $request->input('salespeople_id'),
				'invoice_id'     => $id,
				'sp_type' => 1
			] );

			if(!empty($request->input('second_salespeople_id')) && count($request->input('second_salespeople_id'))) {
				foreach ($request->input('second_salespeople_id') as $val){
					SecondarySalesPeople::create( [
						'salespeople_id' => $val,
						'invoice_id'     => $id
					] );
				}
			}

			$this->generatePDF($id);

			return $this->sendResponse($invoice, '');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'InvoicesController',
				'function' => 'update'
			]);
			return $this->sendError( $ex->getMessage() );
		}
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		Invoices::where('id',$id)->delete();
		return redirect()->route('invoices.index')
		                 ->with('success','Invoice deleted successfully');
	}


	public function generateInvoiceNumber($id, $prefix = '00425-'){
		$value = 1025 + $id;
		$valueWithZeros = $formatted_value = sprintf("%05d", $value);
		return $prefix.$valueWithZeros;
	}

	public function moneyFormat($value){
		$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
		return $formatter->formatCurrency($value, 'USD');
	}




	public function createTimeString($datetimestring){
		return date('m-d-Y', strtotime($datetimestring));
	}

	public function generatePDF($id){
		$invoice = Invoices::
							with('customer')
		                   ->with('salespersone')
		                   ->with('product')
		                   ->find($id);
		if($invoice) {
			$invoice->invoice_number = $this->generateInvoiceNumber($invoice->customer->id);
			$total = $this->moneyFormat( $invoice->sales_price * $invoice->qty );
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$file_name = $this->generateFileName($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			PDF::setOptions(['dpi' => 400]);
			$pdf = PDF::loadView('pdfviewmain', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total' ));
			$pdf->save($this->pdf_path.$file_name);
			$invoice->save();
			return true;

		}
		return false;
	}

	public function generateFileName(Invoices $invoice){
		return 'Portfolio Insider '.$invoice->customer->first_name.' '.$invoice->customer->last_name.' ['.$invoice->invoice_number.'].pdf';
	}

	public function showPdf($id){
		$invoice = Invoices::with('customer')->find($id);
		if($invoice) {
			return response()->file($this->pdf_path.$this->generateFileName($invoice));
		}
		return abort(404);
	}

	public function downloadPdf($id){
		$invoice = Invoices::with('customer')->find($id);
		if($invoice) {
			return response()->download($this->pdf_path.$this->generateFileName($invoice));
		}
		return abort(404);
	}


	public function testview($id)
	{
		$invoice = Invoices::
							with('customer')
		                   ->with('salespersone')
		                   ->with('product')
		                   ->find($id);
		if($invoice) {
			$invoice->invoice_number = $this->generateInvoiceNumber($invoice->customer->id);
			$total = $this->moneyFormat( $invoice->sales_price * $invoice->qty );
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$file_name = $this->generateFileName($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			return view('pdfviewmain', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total' ));
		}
		return abort(404);

	}

}
