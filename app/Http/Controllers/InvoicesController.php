<?php

namespace App\Http\Controllers;

use App\Customers;
use App\EmailTemplates;
use App\Invoices;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\Products;
use App\Salespeople;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PDF;

class InvoicesController extends Controller
{
	protected $pdf_path, $full_path, $app_url;

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
		$template = EmailTemplates::getIdsAndFullNames();
		return view('invoices.create', compact('customers','salespeople', 'customerId', 'products', 'template'));
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
			'email_template_id' => 'required|numeric|min:1',
			'customer_id' => 'required|numeric|min:1',
			'salespeople_id' => 'required|numeric|min:1',
			'product_id' => 'required|numeric|min:1',
			'sales_price' => 'required',
			'qty' => 'required|numeric|min:1',
			'access_date' => 'required',
			'password' => 'required',
			'cc' => 'required|digits:4',
		]);

		$sales_price = !empty($request->input('sales_price')) ? $this->moneyToDecimal($request->input('sales_price')) : 0;
		if(!$sales_price){
			return redirect()->route('invoices.create')
							 ->withErrors(['Please enter correct price.'])
							 ->withInput();
		}

		$invoice = Invoices::create([
			'email_template_id' => $request->input('email_template_id'),
			'customer_id' => $request->input('customer_id'),
			'salespeople_id' => $request->input('salespeople_id'),
			'product_id' => $request->input('product_id'),
			'sales_price' => $sales_price,
			'qty' => $request->input('qty'),
			'access_date' => $this->createDateTime($request->input('access_date')),
			'password' => $request->input('password'),
			'cc' => $request->input('cc')
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
		                   ->with('salespersone')
		                   ->with('product')
		                   ->with('template')
		                   ->find($id);
		if($invoice) {
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$total = $this->moneyFormat( $invoice->sales_price * $invoice->qty );
			$file_name = $this->generateFileName($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			return view( 'invoices.show', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total') );
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
		                   ->with('template')
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
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'max:120',
			'name_for_invoice' => 'max:120',
			'email' => 'email|max:120',
			'phone_number' => 'max:120|min:10',
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		$invoice = Invoices::find($id);
		$invoice->first_name = $request->input('first_name');
		$invoice->last_name =  $last_name;
		$invoice->name_for_invoice =  !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name;
		$invoice->phone_number = !empty($request->input('phone_number')) ? $request->input('phone_number') : '';
		$invoice->save();

		return redirect()->route('invoices.index')
		                 ->with('success','Invoice updated successfully');
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


	public function generateInvoiceNumber($id){
		$in_number = '00424-';
		$value = 1025 + $id;
		$valueWithZeros = $formatted_value = sprintf("%05d", $value);
		return $in_number.$valueWithZeros;
	}

	public function moneyFormat($value){
		$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
		return $formatter->formatCurrency($value, 'USD');
	}

	public function moneyToDecimal($number, $dec_point=null) {
		if (empty($dec_point)) {
			$locale = localeconv();
			$dec_point = $locale['decimal_point'];
		}
		return floatval(str_replace($dec_point, '.', preg_replace('/[^\d'.preg_quote($dec_point).']/', '', $number)));
	}

	public function createDateTime($datetimestring){
		$timezone = config('app.timezone');
		$carbon = Carbon::instance(date_create_from_format('m-d-Y', $datetimestring));
		$carbon->timezone($timezone);
		return $carbon->toDateTimeString();
	}

	public function createTimeString($datetimestring){
		return date('m-d-Y', strtotime($datetimestring));
	}

	public function generatePDF($id){
		$invoice = Invoices::
							with('customer')
		                   ->with('salespersone')
		                   ->with('product')
		                   ->with('template')
		                   ->find($id);
		if($invoice) {
			$invoice->invoice_number = $this->generateInvoiceNumber($invoice->id);
			$total = $this->moneyFormat( $invoice->sales_price * $invoice->qty );
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$file_name = $this->generateFileName($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->customer->phone_number, $invoice->customer->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			$pdf = PDF::loadView('pdfviewmain', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total' ));
			$pdf->save($this->pdf_path.$file_name);
			$invoice->save();
			return true;

		}
		return false;
	}

	protected function generateFileName(Invoices $invoice){
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
		                   ->with('template')
		                   ->find($id);
		if($invoice) {
			$invoice->invoice_number = $this->generateInvoiceNumber($invoice->id);
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
