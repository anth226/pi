<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use App\Customers;
use App\EmailLogs;
use App\EmailTemplates;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use App\LevelsSalespeople;
use App\PdfTemplates;
use App\Products;
use App\Salespeople;
use App\SalespeoplePecentageLog;
use App\SecondarySalesPeople;
use App\SentData;
use Aws\imagebuilder\imagebuilderClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF, DB;

class InvoicesController extends BaseController
{
	protected $full_path, $app_url;
	public $pdf_path;
	public $pdf_footer, $pdf_footer2, $pdf_footer_annual, $support_phone_number;

	function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware('permission:invoice-list|invoice-create|invoice-edit|invoice-delete|generated-invoice-list|generated-invoice-create|generated-invoice-edit|generated-invoice-delete|salespeople-reports-view-own', ['only' => ['index']]);
		$this->middleware('permission:invoice-list|invoice-create|invoice-edit|invoice-delete', ['only' => ['show', 'showPdf']]);
		$this->middleware('permission:invoice-create', ['only' => ['create','store']]);
		$this->middleware('permission:invoice-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:invoice-delete', ['only' => ['destroy']]);

		$this->pdf_path = base_path().'/resources/views/invoicesPdf/';
		$this->full_path =  config('app.url').'/pdfview/';
		$this->app_url =  config('app.url');

		$this->support_phone_number = '(323) 483-4014';

		$this->pdf_footer2 =  "
		<div style=\"text-align: center;margin-top: 30px;width: 100%\">
	        <div>
	            <small>
	                Thanks for becoming a valued customer at PortfolioInsider.com.<br>
	                If you are not entirely satisfied with your purchase, we're here to help you with world-class support. You are entitled to cancel your order within 6 months without giving any given reason. To exercise your right of refund, you must inform us of your decision. <br>
	                You can contact us via email: support@portfolioinsider.com, phone number: ".$this->support_phone_number.", or mail: 9465 Wilshire Boulevard Office #300. Beverly Hills, CA 90212. We will reimburse you no later than 14 days from the day of which we receive the notification.<br>
	                Refunds are issued to the same form of payment that was used for the order with no additional fees. If you have any further questions or there is any other information you require from us, please don’t hesitate to contact us.  We remain committed to providing excellent customer service and a positive experience for all customers. 
	            </small>
	        </div>
	        <div>
	            <small>
	                <strong>Our phone service operates 24/7. Call ".$this->support_phone_number."</strong>
	            </small>
	        </div>
    	</div>
		";

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

		$this->pdf_footer_annual =  "
		<div style=\"margin-top: 30px;width: 100%\">
	        <div>	            
	                <p style=\"margin: 5px 0;font-size:13px;\"><small>1 Year subscription. Does not auto renew.</small></p>
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
		$user = Auth::user();
		if($user->hasRole('Generated Invoices Only') || $user->hasRole('Salesperson')) {
			if ( $user->hasRole( 'Salesperson' ) ) {
				$salesperson_id = Salespeople::withTrashed()->where( 'email', $user->email )->value( 'id' );
				if ( $salesperson_id ) {
					$salespeopleController = new SalespeopleController();
					return $salespeopleController->show( $salesperson_id );
				}
			}
			if ( $user->hasRole( 'Generated Invoices Only' ) ) {
				$generated_invoice = new InvoiceGeneratorController();
				return $generated_invoice->create( $request );
			}
			return abort(404);
		}
		else {
			$lastReportDate = Invoices::orderBy( 'access_date', 'desc' )->value( 'access_date' );
			$firstDate      = date( "F j, Y" );
			$lastDate       = date( "F j, Y" );
			if ( $lastReportDate ) {
				$lastDate = date( "F j, Y", strtotime( $lastReportDate ) );
			}
			return view( 'invoices.index', compact( 'firstDate', 'lastDate', 'user' ) );
		}

	}

	public function anyData(Request $request){
		$query =  Invoices::with('customer')
		                  ->with('salespeople.salespersone')
						  ->with('salespeople.level')
						  ->with('customer.pipedriveSources')
						  ->with('customer.pipedriveSources.fieldName')
		;
		if ( ! empty( $request['date_range'] ) && empty( $request['search']['value'] ) ) {
			$date      = $request['date_range'];
			$dateArray = $this->parseDateRange( $date );
			$dateFrom  = date( "Y-m-d", $dateArray[0] );
			$dateTo    = date( "Y-m-d", $dateArray[1] );
			$query->where( 'access_date', '>=', $dateFrom )
			      ->where( 'access_date', '<=', $dateTo );
		}
		if( ! empty( $request['summary'] )){
//			DB::enableQueryLog();
//			$query->get();
//			dd(DB::getQueryLog());
			$commission = 0;
			$revenue = $query->sum('paid');
			$invoices = $query->get();
			if($invoices && $invoices->count()){
				foreach($invoices  as $inv){
					$sp = $inv->salespeople;
					if($sp && $sp->count()){
						foreach($sp as $s){
							$commission += $s->earnings;
						}
					}
				}
			}
			$profit = $revenue - $commission;
			$res = [
				'revenue' => $revenue,
				'count' => $query->count(),
				'commission' => $commission,
				'profit' => $profit
			];
			return $this->sendResponse($res,'');
		}
		else {
			return datatables()->eloquent( $query )->toJson();
		}
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
		                   ->with('salespeople.level')
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

			$show_selects = true;
			$pr_salespeople = [];
			$sec_salespeople = [];
			if(!empty($invoice->salespeople)){
				foreach($invoice->salespeople as $sp){
					if(!empty($sp->salespeople_id)){
						$levelSalespeopleId =  LevelsSalespeople::where('salespeople_id', $sp->salespeople_id)->where('level_id', $sp->level_id)->value('id');
						if($levelSalespeopleId) {
							if ( $sp->sp_type ) {
								$pr_salespeople[] = $levelSalespeopleId;
							} else {
								$sec_salespeople[] = $levelSalespeopleId;
							}
						}
						else{
							$show_selects = false;
						}
					}
				}
			}

			if($show_selects){
				$all_salespeople = SecondarySalesPeople::where( 'invoice_id', $id )->get();
				foreach($all_salespeople as $salesperson){
					if($salesperson->paid_at){
						$show_selects = false;
					}
				}
			}

			if($show_selects) {
				$salespeople_multiple = Elements::salespeopleSelect( 'second_salespeople_id[]', [ 'class' => 'form-control', 'multiple' => 'multiple'	], $sec_salespeople );
				$salespeople          = Elements::salespeopleSelect( 'salespeople_id', [ 'class' => 'form-control' ], $pr_salespeople );
			}
			else{
				$salespeople_multiple = false;
				$salespeople = false;
			}

			$pdftemplates_select = Elements::pdfTemplatesSelect( 'pdftemplate_id', [ 'class' => 'form-control' ], $invoice->pdftemplate_id );

			return view( 'invoices.show', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'template', 'logs','sentLog', 'states', 'salespeople', 'salespeople_multiple', 'pdftemplates_select') );
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
//				'salespeople_id' => 'required|numeric|min:1',
				'sales_price' => 'required',
				'access_date' => 'required',
				'cc_number' => 'required|digits:4'
			]);

			$need_update_salespeople = true;
			if(!$request->input('salespeople_id')){
				if($request->input('no_salespeople')) {
					return $this->sendError( 'Please select Salesperson.' );
				}
				else{
					$need_update_salespeople = false;
				}
			}
			$sales_price = Elements::moneyToDecimal($request->input('sales_price'));
			$paid = !empty($request->input('paid')) ? Elements::moneyToDecimal($request->input('paid')) : $sales_price;
			if(($sales_price - $paid) < 0){
				return $this->sendError('Paid amount must be less than Sales Price.');
			}

			$invoice_before = Invoices::with('customer')->find($id);

			$dataToUpdate['sales_price'] = Elements::moneyToDecimal($request->input('sales_price'));
			if($invoice_before->paid != $paid || $invoice_before->sales_price != $sales_price) {
				if($invoice_before->paid != $paid) {
					$dataToUpdate['paid']    = $paid;
					$dataToUpdate['paid_at'] = Carbon::now();
				}
				$dataToUpdate['own'] = $sales_price - $paid;
			}
			$dataToUpdate['access_date'] = Elements::createDate($request->input('access_date'));
			$dataToUpdate['cc_number'] = $request->input('cc_number');

			$pdftemplate = 'pdfviewmain';
			$pdftemplate_id = 1;
			if(!empty($request->input('pdftemplate_id'))){
				$pdftemplate_id = $request->input('pdftemplate_id');
				$pdftemplate = PdfTemplates::where('id', $pdftemplate_id)->value('slug');
			}

			if($invoice_before->pdftemplate_id != $pdftemplate_id){
				$dataToUpdate['pdftemplate_id'] =   $pdftemplate_id;
			}

			$salespeople_id = LevelsSalespeople::getSalespersonInfo($request->input('salespeople_id'));

			$invoice_salespeople = [];

			if($need_update_salespeople) {
				$dataToUpdate['salespeople_id'] = $salespeople_id->salespeople_id;

				if(!empty($invoice_before->salespeople)){
					foreach($invoice_before->salespeople as $sp){
						if(!empty($sp->salespeople_id)){
							$levelSalespeopleId =  LevelsSalespeople::where('salespeople_id', $sp->salespeople_id)->where('level_id', $sp->level_id)->value('id');
							if(!$levelSalespeopleId) {
								return $this->sendError( "Data were changed. Please refresh the page.");
							}
						}
					}
				}

				$all_salespeople = SecondarySalesPeople::where( 'invoice_id', $id )->get();
				foreach($all_salespeople as $salesperson){
					if($salesperson->paid_at){
						return $this->sendError( "Data were changed. Please refresh the page.");
					}
				}
			}

			$invoice = Invoices::where('id', $id)->update($dataToUpdate);

			$user_logged = Auth::user();
			if($dataToUpdate && count($dataToUpdate)) {
				foreach($dataToUpdate as $field_name => $new_value) {
					if(isset($invoice_before->$field_name) && $invoice_before->$field_name != $new_value && $field_name != "salespeople_id" && $field_name != "paid_at") {
						$old_value = $invoice_before->$field_name;
						if($field_name == 'pdftemplate_id'){
							$field_name = 'pdftemplate';
							$new_value = PdfTemplates::where('id', $new_value)->value('title');
							$old_value = PdfTemplates::where('id', $invoice_before->pdftemplate_id)->value('title');
						}
						ActionsLog::create( [
							'user_id'    => $user_logged->id,
							'model'      => 1,
							'field_name' => $field_name,
							'old_value' => $old_value,
							'new_value' => $new_value,
							'action'     => 1,
							'related_id' => $id
						] );
					}
				}
			}

			if($need_update_salespeople) {

				$sp_before = SecondarySalesPeople::where( 'invoice_id', $id )->with('salespersone')->with('level')->get();
				$sp_old = [];
				if($sp_before && $sp_before->count()){
					foreach($sp_before as $sp){
						$sp_old[] = $sp->salespersone->name_for_invoice.'|'.$sp->level->title.'|'.$sp->percentage.'%';
					}
				}
				$old_value = !empty($sp_old) ? implode(', ',$sp_old) : '';

				SecondarySalesPeople::where( 'invoice_id', $id )->delete();

				SecondarySalesPeople::create( [
					'salespeople_id' => $dataToUpdate['salespeople_id'],
					'invoice_id'     => $id,
					'sp_type' => 1,
					'earnings'=> 0,
					'percentage' => $salespeople_id->level->percentage,
					'level_id' => $salespeople_id->level_id
				] );
				$invoice_salespeople[] = Salespeople::where('id', $dataToUpdate['salespeople_id'])->withTrashed()->value('name_for_invoice');

				if ( ! empty( $request->input( 'second_salespeople_id' ) ) && count( $request->input( 'second_salespeople_id' ) ) ) {
					foreach ( $request->input( 'second_salespeople_id' ) as $val ) {
						$salespeople_id = LevelsSalespeople::getSalespersonInfo($val);
						SecondarySalesPeople::create( [
							'salespeople_id' => $salespeople_id->salespeople_id,
							'invoice_id'     => $id,
							'earnings'=> 0,
							'percentage' => $salespeople_id->level->percentage,
							'level_id' => $salespeople_id->level_id
						] );
						$invoice_salespeople[] = Salespeople::where('id', $salespeople_id->salespeople_id)->withTrashed()->value('name_for_invoice');
					}
				}
				$note_id = SentData::where('service_type', 5)
				                   ->where('customer_id', $invoice_before->customer->id)
				                   ->where('field', 'note_id')
				                   ->orderBy('id', 'desc')
				                   ->value('value')
				;
				if($note_id) {
//					Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\UpdateNote( $note_id, implode( ', ', $invoice_salespeople ) ) );
				}

				$sp_after = SecondarySalesPeople::where( 'invoice_id', $id )->with('salespersone')->with('level')->get();
				$sp_new = [];
				if($sp_after && $sp_after->count()){
					foreach($sp_after as $sp){
						$sp_new[] = $sp->salespersone->name_for_invoice.'|'.$sp->level->title.'|'.$sp->percentage.'%';
					}
				}
				$new_value = !empty($sp_new) ? implode(', ',$sp_new) : '';
				if($new_value != $old_value){
					ActionsLog::create( [
						'user_id'    => $user_logged->id,
						'model'      => 1,
						'field_name' => 'salespeople',
						'old_value' => $old_value,
						'new_value' => $new_value,
						'action'     => 1,
						'related_id' => $id
					] );
				}

			}



			$this->generatePDF($id, $pdftemplate);

			$invoice_percentages = $this->calcEarning(Invoices::find($id));

			$all_salespeople = SecondarySalesPeople::where( 'invoice_id', $id )->get();
			foreach($all_salespeople as $salesperson){
				if($salesperson->paid_at){
					$invoice_percentages[$salesperson->salespeople_id]['discrepancy'] = $invoice_percentages[$salesperson->salespeople_id]['earnings'] * 1 - $salesperson->paid_amount * 1;
				}
			}
			$this->savePercentages($invoice_percentages, $id);

			$customersController = new CustomersController();

			if($invoice_before && $invoice_before->paid && $invoice_before->customer->id && $invoice_before->paid != $paid) {
				$deal_id = SentData::where('service_type', 5)
				                   ->where('customer_id', $invoice_before->customer->id)
				                   ->where('field', 'deal_id')
				                   ->orderBy('id', 'desc')
				                   ->value('value')
				;
				if($deal_id) {
					$pipedrive_res = $customersController->updatePipedriveDeal( $deal_id, $paid );
					if ( ! $pipedrive_res['success'] ) {
						$message = 'Error! Can\'t send data to Pipedrive';
						if ( ! empty( $pipedrive_res['message'] ) ) {
							$message = $pipedrive_res['message'];
						}
						redirect()->route( 'invoices.show', $id )->withErrors( [ $message ] );
					} else {
						SentData::create( [
							'customer_id'  => $invoice_before->customer->id,
							'value'        => $pipedrive_res['data'],
							'field'        => 'deal_id',
							'service_type' => 5 // pipedrive,
						] );
					}
				}
			}

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

	public function generatePDF($id, $pdftemplate = 'pdfviewmain'){
		$item_price = 9995;
		$invoice = Invoices::
							with('customer')
		                   ->with('salespersone')
		                   ->with('product')
		                   ->find($id);
		if($invoice) {
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
			$pdf_footer = $this->pdf_footer;
			$pdf_footer_annual = $this->pdf_footer_annual;
			$support_phone_number = $this->support_phone_number;
			PDF::setOptions(['dpi' => 400]);
			$pdf = PDF::loadView($pdftemplate, compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'price_before_discount', 'total_before_discount', 'discount', 'pdf_footer', 'pdf_footer_annual', 'support_phone_number' ));
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

	public function parseDateRange($range):?array
	{
		$dateArray = explode(" to ",$range);

		if (count($dateArray) == 2) {
			return [strtotime($dateArray[0]),strtotime($dateArray[1])];
		}else if (count($dateArray) == 1) {
			return [strtotime($dateArray[0]),strtotime($dateArray[0])];
		}

		return null;
	}

	public function getInvoiceCurrentPercentage(Invoices $invoice){
		try{
			$salespeople = $invoice->salespeople;
			$sp_percentages = [];
			foreach($salespeople as $sp){
				$salespeople_id = $sp->salespeople_id;
				$percentage = $sp->percentage;
				if($invoice->status == 2 || $invoice->status == 3){
					$percentage = 0;
				}
				$res = [
					'percentage' => $percentage,
					'level_id' => $sp->level_id,
				];
				$sp_percentages[$salespeople_id] = $res;

			}
			return $sp_percentages;
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'InvoicesController',
				'function' => 'getInvoiceCurrentPercentage'
			]);
			return false;
		}
	}

	public function calcEarning(Invoices $invoice){
		try{
			$max_percentage = 40;
			$max_percentage_for_one_salesperson = 30;
			if(strtotime($invoice->access_date.' 00:00:01') <  strtotime('2021-03-30 00:00:01')){
				$max_percentage = 50;
				$max_percentage_for_one_salesperson = 50;
			}
			$sales_price = $invoice->paid;
			$max_earning  = $sales_price*$max_percentage/100;
			$earnings = [];
			$percentages = $this->getInvoiceCurrentPercentage($invoice);
			if($percentages && count($percentages)) {
				$levels = [];
				$levels_earnings_sum = 0;
				foreach ($percentages as $salespeople_id => $p) {
					// find people with same level_id
					$level_id = $percentages[ $salespeople_id ]['level_id'];
					$percentage = $percentages[ $salespeople_id ]['percentage'];
					if($percentage > 0) { //remove 0 pecentages level
						if($percentage > $max_percentage_for_one_salesperson){
							$percentage = $max_percentage_for_one_salesperson;
						}
						$levels[ $level_id ]['salespeople'][ $salespeople_id ] = $percentage;
					}
					else{
						$earnings[$salespeople_id] = [
							'earnings' => 0
						];
					}
				}
				if(!empty($levels) && count($levels)) {
					foreach ( $levels as $l_id => $l ) {
						$level_max_earning = 0;
						//find same level max pesentage (most likely they are the same)
						foreach ( $l['salespeople'] as $s => $p ) {
							$earning = ( $sales_price / 100 ) * $p;
							if ( $level_max_earning < $earning ) {
								$level_max_earning = $earning;
							}
						}
						if ( $level_max_earning > $max_earning ) {
							$level_max_earning = $max_earning;
						}
						$levels[ $l_id ]['earnings'] = $level_max_earning; //earnings for level
						$earning                     = $level_max_earning / count( $levels[ $l_id ]['salespeople'] ); // earning for every salesperson from that level
						foreach ( $l['salespeople'] as $sid => $p ) {
							$earnings[ $sid ] = [
								'earnings' => $earning
							];
						}
						$levels_earnings_sum += $level_max_earning;
					}

					// in case if all salespeople earnings are more the max earning for deal
					if ( $levels_earnings_sum > $max_earning ) {
						$earnings     = [];

						// we need to sort array by earning asc
						$sortedLevels = [];
						foreach ($levels as $k => $v){
							if(!isset($sortedLevels[$v['earnings']])){
								$sortedLevels[$v['earnings']] = $v;
							}
						}
						ksort($sortedLevels);

						$possible_earnings = $max_earning;
						foreach ( $sortedLevels as $l_id => $l ) {
							$level_earning = $l['earnings'];
							if ( $possible_earnings <= $level_earning ) {
								$level_earning = $possible_earnings;
							}
							$earning = $level_earning / count( $sortedLevels[ $l_id ]['salespeople'] );
							$possible_earnings = $possible_earnings - $level_earning;
							foreach ( $l['salespeople'] as $sid => $p ) {
								$earnings[ $sid ] = [
									'earnings' => $earning
								];
							}
						}
					}
				}
			}
			return $earnings;
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'InvoicesController',
				'function' => 'calcEarning'
			]);
			return false;
		}
	}

	public function getCurrentPercentage($report_date, $salespeople_id, $report_time = '23:59:59'){
		try{
			$percentage = SalespeoplePecentageLog::where('salespeople_id', $salespeople_id)
			                                     ->where('created_at', '<=', $report_date.' '.$report_time)
			                                     ->orderBy('created_at', 'desc')
			                                     ->first()
			;
			if(!$percentage || !$percentage->count()) { // first available
				$percentage = SalespeoplePecentageLog::where( 'salespeople_id', $salespeople_id )
				                                     ->orderBy( 'created_at', 'asc' )
				                                     ->first()
				;
			}
			return [
				'percentage' => $percentage->percentage,
				'level_id' => $percentage->level_id,
			];
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'InvoicesController',
				'function' => 'getCurrentPercentage'
			]);
			return false;
		}
	}

	public function savePercentages($percentages, $invoice_id){
		try {
			foreach ($percentages as $salespeople_id => $percentage){
				SecondarySalesPeople::where('salespeople_id', $salespeople_id)->where('invoice_id', $invoice_id)->update($percentage);
			}
			return true;
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'InvoicesController',
				'function' => 'savePercentages'
			]);
			return false;
		}
	}

	public function updateStatus(Request $request){
		try {
			$this->validate( $request, [
				'invoice_id' => 'required',
				'refundRequested' => 'required',
			] );

			$user_logged = Auth::user();

			$status_before = Invoices::with('customer')->where('id', $request->input( 'invoice_id' ))->first();

			$dataToUpdate = [
				'status' => $request->input( 'refundRequested' )
			];

			if($request->input( 'refundRequested' ) == 3){ // refunded
				$dataToUpdate['paid'] = 0;
				$dataToUpdate['own'] = 0;
				$dataToUpdate['sales_price'] = 0;
			}

			Invoices::where('id', $request->input( 'invoice_id' ))->update($dataToUpdate);

			$invoice_percentages = $this->calcEarning(Invoices::find($request->input( 'invoice_id' )));

			$all_salespeople = SecondarySalesPeople::where( 'invoice_id',$request->input( 'invoice_id' ) )->get();
			foreach($all_salespeople as $salesperson){
				if($salesperson->paid_at){
					$invoice_percentages[$salesperson->salespeople_id]['discrepancy'] = $invoice_percentages[$salesperson->salespeople_id]['earnings'] * 1 - $salesperson->paid_amount * 1;
				}
			}
			$this->savePercentages($invoice_percentages, $request->input( 'invoice_id' ));

			$status_after = Invoices::with('customer')->where('id', $request->input( 'invoice_id' ))->first();

			if($status_before->status != $status_after->status) {
				ActionsLog::create( [
					'user_id'    => $user_logged->id,
					'model'      => 1,
					'field_name' => 'status',
					'old_value'  => Invoices::STATUS[ $status_before->status ],
					'new_value'  => Invoices::STATUS[ $status_after->status ],
					'action'     => 1,
					'related_id' => $request->input( 'invoice_id' )
				] );
			}

			if($status_before->paid != $status_after->paid){
				ActionsLog::create( [
					'user_id'    => $user_logged->id,
					'model'      => 1,
					'field_name' => 'paid',
					'old_value' => $status_before->paid,
					'new_value' => $status_after->paid,
					'action'     => 1,
					'related_id' => $request->input( 'invoice_id' )
				] );
			}

			if($status_before->own != $status_after->own){
				ActionsLog::create( [
					'user_id'    => $user_logged->id,
					'model'      => 1,
					'field_name' => 'own',
					'old_value' => $status_before->own,
					'new_value' => $status_after->own,
					'action'     => 1,
					'related_id' => $request->input( 'invoice_id' )
				] );
			}

			if($status_before->sales_price != $status_after->sales_price){
				ActionsLog::create( [
					'user_id'    => $user_logged->id,
					'model'      => 1,
					'field_name' => 'sales_price',
					'old_value' => $status_before->sales_price,
					'new_value' => $status_after->sales_price,
					'action'     => 1,
					'related_id' => $request->input( 'invoice_id' )
				] );
			}

			if($status_before && $status_before->paid && $status_before->customer->id && $status_before->paid != $status_after->paid) {
				$deal_id = SentData::where('service_type', 5)
				                   ->where('customer_id', $status_before->customer->id)
				                   ->where('field', 'deal_id')
				                   ->orderBy('id', 'desc')
				                   ->value('value')
				;
				if($deal_id) {
					$customersController = new CustomersController();
					$pipedrive_res = $customersController->updatePipedriveDeal( $deal_id, $status_after->paid );
					if ( $pipedrive_res['success'] ) {
						SentData::create( [
							'customer_id'  => $status_before->customer->id,
							'value'        => $pipedrive_res['data'],
							'field'        => 'deal_id',
							'service_type' => 5 // pipedrive,
						] );
					}
				}
			}

			return $this->sendResponse('done');
		}
		catch (Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'InvoicesController',
				'function' => 'updateStatus'
			]);
			return $this->sendError($ex->getMessage());
		}
	}

}
