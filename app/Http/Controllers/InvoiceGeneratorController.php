<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use App\EmailLogs;
use App\EmailLogsGeneratedInvoices;
use App\EmailTemplates;
use App\Errors;
use App\InvoiceGenerator;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use Illuminate\Http\Request;
use Exception;
use PDF;
use Illuminate\Support\Facades\Auth;

class InvoiceGeneratorController extends InvoicesController
{
	public $attachments;

	function __construct()
	{
//		parent::__construct();
		$this->middleware(['auth','verified']);
		$this->middleware('permission:generated-invoice-list|generated-invoice-create|generated-invoice-edit|generated-invoice-delete', ['only' => ['index','show', 'showPdf']]);
		$this->middleware('permission:generated-invoice-create', ['only' => ['create','store']]);
		$this->middleware('permission:generated-invoice-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:generated-invoice-delete', ['only' => ['destroy']]);

		$this->pdf_path = base_path().'/resources/views/invoicesPdf/';
		$this->full_path =  config('app.url').'/pdfview/';
		$this->app_url =  config('app.url');

		$this->pdf_path = base_path().'/resources/views/invoicesGeneratedPdf/';
		$this->full_path =  config('app.url').'/pdfviewforgeneratedinvoices/';

		$title1 = 'PI Returns From Daily Digest.pdf';
		$mime1 = 'application/pdf';
		if(config('app.env') == 'local'){
			$title1 = 'qqq.pdf';
		}
		$this->attachments = [
			[
				'title' => $title1,
				'filename' => $this->pdf_path.$title1,
				'mime' => $mime1
			]
		];

		$this->support_phone_number = '(323) 483-4014';

		$this->pdf_footer =  "
		<div style=\"margin-top: 30px;width: 100%\">
	        <div>	            
	                <p style=\"margin: 5px 0;font-size:13px;\"><small>Thanks for becoming a valued customer at PortfolioInsider.com.</small></p>
	                <p style=\"margin: 5px 0;font-size:13px;\"><small>If you are not entirely satisfied with your purchase, we're here to help you with world-class support. You are entitled to cancel your order within 6 months without giving any given reason. To exercise your right of refund, you must inform us of your decision.</small></p>
	                <p style=\"margin: 5px 0;font-size:13px;\"><small>You can contact us via email: support@portfolioinsider.com, phone number: ".$this->support_phone_number.", or mail: 9465 Wilshire Boulevard Office #300. Beverly Hills, CA 90212. We will reimburse you no later than 14 days from the day of which we receive the notification.</small></p>
	                <p style=\"margin: 5px 0;font-size:13px;\"><small>Refunds are issued to the same form of payment that was used for the order with no additional fees. If you have any further questions or there is any other information you require from us, please don’t hesitate to contact us.  We remain committed to providing excellent customer service and a positive experience for all customers.</small></p> 	       
	        </div>
	        <div>	           
	                <p style=\"margin: 5px 0;font-size:13px;\"><strong><small>Our phone service operates 24/7. Call ".$this->support_phone_number."</small></strong></p>	           
	        </div>
    	</div>
		";

	}

	public function index(Request $request)
	{
		return view( 'invoicesGenerated.index');

	}

	public function anyData(Request $request){
		$query =  InvoiceGenerator::selectRaw('*');
		return datatables()->eloquent( $query )->toJson();

	}


	public function create(Request $request)
	{
		$states = UsStates::statesUS();
		return view('invoicesGenerated.create', compact('states'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		try {
			$this->validate( $request, [
				'first_name'   => 'required|max:120',
				'last_name'    => 'required|max:120',
				'address_1'    => 'max:120',
				'zip'          => 'max:120',
				'city'         => 'max:120',
				'state'        => 'max:20',
				'email'        => 'required|email|max:120',
				'phone_number' => 'max:120',
				'sales_price'  => 'required',
				'access_date'  => 'required',
				'cc'           => 'digits:4|nullable'
			] );

			$sales_price = ! empty( $request->input( 'sales_price' ) ) ? Elements::moneyToDecimal( $request->input( 'sales_price' ) ) : 0;
			if ( ! $sales_price ) {
				return $this->sendError( 'Please enter correct price.' );
			}

			$discounts      = [];
			$discount_total = 0;
			foreach ( $request->request as $k => $v ) {
				if ( strpos( $k, 'discountamount' ) !== false ) {
					$disc_array  = explode( '_', $k );
					$discount_id = $disc_array[1];
					$amount      = Elements::moneyToDecimal( $v );
					if ( ! empty( $amount ) ) {
						$discounts[]    = [
							'amount' => $amount,
							'title'  => ! empty( $request->input( 'discounttitle_' . $discount_id ) ) ? $request->input( 'discounttitle_' . $discount_id ) : 'Discount'
						];
						$discount_total += $amount * 1;
					}
				}
			}

			$grand_total = $sales_price - $discount_total;
			if ( $grand_total < 0 ) {
				return $this->sendError( 'Sales price must be more than Grand Total.' );
			}

			$paid = ! empty( $request->input( 'paid' ) ) ? Elements::moneyToDecimal( $request->input( 'paid' ) ) : 0;
			if ( ( ( $sales_price - $paid ) < 0 ) || ( ( $grand_total - $paid ) < 0 ) ) {
				return $this->sendError( 'Paid amount must be less than Sales Price or Grand Total.' );
			}

			$dataToSend = [
				'first_name'            => $request->input( 'first_name' ),
				'last_name'             => $request->input( 'last_name' ),
				'email'                 => strtolower( $request->input( 'email' ) ),
				'phone_number'          => ! empty( $request->input( 'phone_number' ) ) ? $request->input( 'phone_number' ) : '',
				'formated_phone_number' => ! empty( $request->input( 'phone_number' ) ) ? FormatUsPhoneNumber::formatPhoneNumber( $request->input( 'phone_number' ) ) : '',
				'address_1'             => ! empty( $request->input( 'address_1' ) ) ? $request->input( 'address_1' ) : '',
				'address_2'             => ! empty( $request->input( 'address_2' ) ) ? $request->input( 'address_2' ) : '',
				'city'                  => ! empty( $request->input( 'city' ) ) ? $request->input( 'city' ) : '',
				'state'                 => ! empty( $request->input( 'state' ) ) ? $request->input( 'state' ) : '',
				'zip'                   => ! empty( $request->input( 'zip' ) ) ? $request->input( 'zip' ) : '',
				'access_date'           => Elements::createDateTime( $request->input( 'access_date' ) ),
				'cc'                    => ! empty( $request->input( 'cc' ) ) ? $request->input( 'cc' ) : false,
				'sales_price'           => $sales_price,
				'grand_total'           => $grand_total,
				'paid'                  => $paid,
				'own'                   => $grand_total - $paid,
				'discount_total'        => $discount_total,
				'discounts'             => $discounts
			];

			$invoice = InvoiceGenerator::create( $dataToSend );
			$this->generatePDF( $invoice );

			$user = Auth::user();
			ActionsLog::create([
				'user_id' => $user->id,
				'model' => 5,
				'action' => 0,
				'related_id' => $invoice->id
			]);

			return $this->sendResponse( $invoice->id );
		}
		catch ( Exception $ex){
			$err = $ex->getMessage();
			Errors::create( [ 'error'      => $err,
			                  'controller' => 'InvoiceGeneratorController',
			                  'function'   => 'store'
			] );
			return $this->sendError( $err );
		}

	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$invoice = InvoiceGenerator::find($id);
		if($invoice) {
			$formated_price = $this->moneyFormat( $invoice->sales_price );
			$access_date    = $this->createTimeString( $invoice->access_date );
			$total = $this->moneyFormat( $invoice->grand_total );
			$file_name = $this->generateFileNameForGeneratedInvoice($invoice);
			$phone_number = FormatUsPhoneNumber::nicePhoneNumberFormat($invoice->phone_number, $invoice->formated_phone_number);
			$full_path =  $this->full_path;
			$app_url =  $this->app_url;
			$template = EmailTemplates::getIdsAndFullNames();
			$logs = EmailLogsGeneratedInvoices::where('invoice_id', $id)->get();
			$states = UsStates::statesUS();
			$attachments = $this->attachments;
			return view( 'invoicesGenerated.show', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'template', 'logs','sentLog', 'states', 'salespeople', 'salespeople_multiple', 'attachments') );
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


	public function generatePDF($invoice, $pdfTemplate = 'pdfgeneratedviewmain'){
		try {
			if ( $invoice && $invoice->count() ) {
				$invoice->invoice_number = $this->generateInvoiceNumber( $invoice->id, '00746-' );
				$total_before_discount   = $this->moneyFormat( $invoice->sales_price );
				$discount_total          = $this->moneyFormat( $invoice->discount_total );
				$discounts               = $invoice->discounts;
				$grand_total             = $this->moneyFormat( $invoice->grand_total );
				$formated_price          = $this->moneyFormat( $invoice->sales_price );
				$access_date             = $this->createTimeString( $invoice->access_date );
				$file_name               = $this->generateFileNameForGeneratedInvoice( $invoice );
				$phone_number            = FormatUsPhoneNumber::nicePhoneNumberFormat( $invoice->phone_number, $invoice->formated_phone_number );
				$full_path               = $this->full_path;
				$app_url                 = $this->app_url;
				$support_phone_number = $this->support_phone_number;
				PDF::setOptions( [ 'dpi' => 400 ] );
				$pdf_footer = $this->pdf_footer;
				$pdf = PDF::loadView( $pdfTemplate, compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'grand_total', 'price_before_discount', 'total_before_discount', 'discounts', 'discount_total', 'pdf_footer', 'support_phone_number' ) );
				$pdf->save( $this->pdf_path . $file_name );
				$invoice->save();
				return true;
			}
			Errors::create( [ 'error'      => "No Invoice found",
			                  'controller' => 'InvoiceGeneratorController',
			                  'function'   => 'generatePDF'
			] );
			return false;
		}
		catch ( Exception $ex){
			$err = $ex->getMessage();
			Errors::create( [ 'error'      => $err,
			                  'controller' => 'InvoiceGeneratorController',
			                  'function'   => 'generatePDF'
			] );
			return false;
		}
	}

	public function generateFileNameForGeneratedInvoice(InvoiceGenerator $invoice){
		return 'Portfolio Insider '.$invoice->first_name.' '.$invoice->last_name.' ['.$invoice->invoice_number.'].pdf';
	}

	public function downloadPdf($id){
		$invoice = InvoiceGenerator::find($id);
		if($invoice) {
			return response()->download($this->pdf_path.$this->generateFileNameForGeneratedInvoice($invoice));
		}
		return abort(404);
	}

	public function downloadFile($title){
		if($title) {
			return response()->download($this->pdf_path.$title);
		}
		return abort(404);
	}

	public function showFile($title){
		if($title) {
			return response()->file($this->pdf_path.$title);
		}
		return abort(404);
	}

	public function showPdf($id){
		$invoice = InvoiceGenerator::find($id);
		if($invoice) {
			return response()->file($this->pdf_path.$this->generateFileNameForGeneratedInvoice($invoice));
		}
		return abort(404);
	}
}
