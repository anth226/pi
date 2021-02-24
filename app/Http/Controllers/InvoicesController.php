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
use App\LevelsSalespeople;
use App\Products;
use App\Salespeople;
use App\SalespeopleLevels;
use App\SalespeopleLevelsUpdates;
use App\SalespeoplePecentageLog;
use App\SecondarySalesPeople;
use App\SentData;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF, DB;

class InvoicesController extends BaseController
{
	protected $full_path, $app_url;
	public $pdf_path;

	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:invoice-list|invoice-create|invoice-edit|invoice-delete|generated-invoice-list|generated-invoice-create|generated-invoice-edit|generated-invoice-delete|salespeople-reports-view-own', ['only' => ['index']]);
		$this->middleware('permission:invoice-list|invoice-create|invoice-edit|invoice-delete', ['only' => ['show', 'showPdf']]);
		$this->middleware('permission:invoice-create', ['only' => ['create','store']]);
		$this->middleware('permission:invoice-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:invoice-delete', ['only' => ['destroy']]);

		$this->pdf_path = base_path().'/resources/views/invoicesPdf/';
		$this->full_path =  config('app.url').'/pdfview/';
		$this->app_url =  config('app.url');

	}


	public function index(Request $request)
	{
		$user = Auth::user();
		if( $user->hasRole('Salesperson')){
			$salesperson_id = Salespeople::where('email', $user->email)->value('id');
			if($salesperson_id) {
				$salespeopleController = new SalespeopleController();
				return $salespeopleController->show($salesperson_id);
			}
			return abort(404);
		}
		else {
			if( $user->hasRole('Generated Invoices Only')){
				$generated_invoice = new InvoiceGeneratorController();
				return $generated_invoice->create($request);
			}
			else {
				//	    $firstReportDate = Invoices::orderBy('access_date', 'asc')->value('access_date');
				$lastReportDate = Invoices::orderBy( 'access_date', 'desc' )->value( 'access_date' );
				$firstDate      = date( "F j, Y" );
				$lastDate       = date( "F j, Y" );
				//		if($firstReportDate) {
				//			$firstDate = date( "F j, Y", strtotime( $firstReportDate ) );
				//		}
				if ( $lastReportDate ) {
					$lastDate = date( "F j, Y", strtotime( $lastReportDate ) );
				}

				return view( 'invoices.index', compact( 'firstDate', 'lastDate' ) );
			}
		}
	}

	public function anyData(Request $request){
		$query =  Invoices::with('customer')
		                  ->with('salespeople.salespersone')
						  ->with('salespeople.level')
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
			return view( 'invoices.show', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'template', 'logs','sentLog', 'states', 'salespeople', 'salespeople_multiple') );
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
			$dataToUpdate['access_date'] = Elements::createDateTime($request->input('access_date'));
			$dataToUpdate['cc_number'] = $request->input('cc_number');

			$salespeople_id = LevelsSalespeople::getSalespersonInfo($request->input('salespeople_id'));

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

			if($need_update_salespeople) {

				SecondarySalesPeople::where( 'invoice_id', $id )->delete();

				SecondarySalesPeople::create( [
					'salespeople_id' => $dataToUpdate['salespeople_id'],
					'invoice_id'     => $id,
					'sp_type' => 1,
					'earnings'=> 0,
					'percentage' => $salespeople_id->level->percentage,
					'level_id' => $salespeople_id->level_id
				] );

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
					}
				}
			}

			$this->generatePDF($id);
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

	public function generatePDF($id){
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
			PDF::setOptions(['dpi' => 400]);
			$pdf = PDF::loadView('pdfviewmain', compact( 'invoice', 'formated_price', 'access_date', 'file_name', 'full_path', 'app_url', 'phone_number', 'total', 'price_before_discount', 'total_before_discount', 'discount' ));
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
				$res = [
					'percentage' => $sp->percentage,
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
			$max_percentage = 50;
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
						$levels_count = count( $levels );

						$min_level_earnings = [ // min level earnings
							'earning'  => 0,
							'level_id' => 0
						];

						//minimal level earning
						$minEarn = $max_earning;
						foreach ( $levels as $lev_id => $lp ) {
							if ( $lp['earnings'] <= $minEarn ) {
								$minEarn            = $lp['earnings'];
								$min_level_earnings = [ // min level earnings
									'earning'  => $minEarn,
									'level_id' => $lev_id
								];
							}
						}

						$remain_earnings = $max_earning - $min_level_earnings['earning'];
						if ( $remain_earnings < $min_level_earnings['earning'] ) {
							// all levels earnings should be the same
							$level_earnings = $max_earning / $levels_count;
							foreach ( $levels as $l_id => $l ) {
								$earning = $level_earnings / count( $levels[ $l_id ]['salespeople'] ); // earning for every salesperson from that level
								foreach ( $l['salespeople'] as $sid => $p ) {
									$earnings[ $sid ] = [
										'earnings' => $earning
									];
								}
							}
						} else {
							$possible_earnings = $remain_earnings;
							if ( $levels_count > 1 ) {
								$possible_earnings = $remain_earnings / ( $levels_count - 1 );
							}

							foreach ( $levels as $l_id => $l ) {
								if ( $l_id == $min_level_earnings['level_id'] ) { //if min percentage level
									$earning = $min_level_earnings['earning'] / count( $levels[ $l_id ]['salespeople'] );
									foreach ( $l['salespeople'] as $sid => $p ) {
										$earnings[ $sid ] = [
											'earnings' => $earning
										];
									}
								} else {
									$level_earning = $l['earnings'];
									if ( $possible_earnings <= $level_earning ) {
										$level_earning = $possible_earnings;
									}
									$earning = $level_earning / count( $levels[ $l_id ]['salespeople'] );
									foreach ( $l['salespeople'] as $sid => $p ) {
										$earnings[ $sid ] = [
											'earnings' => $earning
										];
									}
								}
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

	public function calcEarning_v1(Invoices $invoice){
		try{
			$max_percentage = 50;
			$sales_price = $invoice->paid;
			$max_earning  = $sales_price*$max_percentage/100;
			$earnings = [];
			$percentages = $this->getInvoiceCurrentPercentage($invoice);
			if($percentages && count($percentages)) {
				$salespeople = $invoice->salespeople;
				$salespeople_count = $salespeople->count();
				if ($salespeople_count == 1 ) { // only one salesperson
					foreach ($percentages as $salespeople_id => $p) {
						$percentage = $percentages[ $salespeople_id ]['percentage'];
						$earning    = $sales_price / 100 * $percentage;
						if ( $earning > $max_earning ) {
							$earning = $max_earning;
						}
						$earnings[ $salespeople_id ] = [
							'earnings'   => $earning
						];
					}
				} else {// multiple salespeople
					//find minimal and max percentages
					$minPercentage = $max_percentage;
					$maxPercentage = 0;
					foreach ($percentages as $salespeople_id => $p) {
						if($p['percentage'] < $minPercentage){
							$minPercentage =  $p['percentage'];
						}
						if($p['percentage'] > $maxPercentage){
							$maxPercentage = $p['percentage'];
						}
					}

					if($minPercentage > $max_percentage){
						$minPercentage = $max_percentage;
					}
					if($maxPercentage > $max_percentage){
						$maxPercentage = $max_percentage;
					}

					if($minPercentage == $maxPercentage){ // all percentages are the same
						$earning = $sales_price/100*$minPercentage;
						if($earning*$salespeople_count > $max_earning){
							$earning = $max_earning/$salespeople_count;
						}
						foreach ($percentages as $salespeople_id => $p){
							$earnings[ $salespeople_id ] = [
								'earnings'   => $earning
							];
						}
					}
					else{
						$all_earnings = 0;
						foreach ($percentages as $salespeople_id => $p){
							$percentage = $p['percentage'];
							$earning = $earning = $sales_price/100*$percentage;
							$all_earnings += $earning;
							$earnings[ $salespeople_id ] = [
								'earnings'   => $earning
							];
						}
						if($all_earnings > $max_earning){
							$all_earnings = 0;
							$salespeople_with_maxPercentage = [];
							foreach ($percentages as $salespeople_id => $p){
								if($p['percentage'] < $maxPercentage) { //excluding salespeople with max percentage
									$percentage                  = $p['percentage'];
									$earning                     = $earning = $sales_price / 100 * $percentage;
									$all_earnings                += $earning;
									$earnings[ $salespeople_id ] = [
										'earnings'   => $earning
									];
								}
								else{
									$salespeople_with_maxPercentage[] = $salespeople_id;
								}
							}
							if($all_earnings < $max_earning){  // checking again
								$remaining_earning = $max_earning-$all_earnings;
								$sp_maxPercentage_earning = $sales_price/100*$maxPercentage;
								$sp_maxPercentage_total = count($salespeople_with_maxPercentage);
								if($sp_maxPercentage_earning*$sp_maxPercentage_total >= $remaining_earning) {// check if all salespeople with max percentage earnings more then max earning allowed
									$earning_for_each = $remaining_earning / count( $salespeople_with_maxPercentage );
								}
								else{
									$earning_for_each = $sp_maxPercentage_earning;
								}
								foreach($salespeople_with_maxPercentage as $sp_max) {
									// adding remaining earning to excluded salesperson
									$earnings[ $sp_max ] = [
										'earnings'   => $earning_for_each
									];
								}
							}
							else{ // earning will be same for all
								$earning = $max_earning/$salespeople_count;
								foreach ($percentages as $salespeople_id => $p){
									$earnings[ $salespeople_id ] = [
										'earnings'   => $earning
									];
								}
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

	public function getCurrentPercentages($report_date, $salespeople_id, $report_time = '23:59:59'){
		try{
			$res = [];
			$update_id = SalespeopleLevelsUpdates::where('salespeople_id', $salespeople_id)
			                                     ->where('created_at', '<=', $report_date.' '.$report_time)
			                                     ->orderBy('created_at', 'desc')
			                                     ->value('id')
			;
			if(!$update_id) { // first available
				$update_id = SalespeopleLevelsUpdates::where( 'salespeople_id', $salespeople_id )
				                                     ->orderBy( 'created_at', 'asc' )
				                                     ->value('id')
				;
			}
			if(!$update_id){
				return false;
			}

			$percentage = SalespeoplePecentageLog::where('update_id', $update_id)
			                                     ->where( 'salespeople_id', $salespeople_id )
			                                     ->get()
			;

			return [
				'percentage' => $percentage->percentage,
				'level_id' => $percentage->level_id,
			];
			return $res;
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

//	public function recalcAll(){
//		try {
//			$invoices = Invoices::get();
//			foreach ($invoices as $invoice) {
//				$percentages =  $this->calcEarning($invoice);
//				$this->savePercentages($percentages, $invoice->id);
//			}
//			return true;
//		}
//		catch (Exception $ex){
//			Errors::create([
//				'error' => $ex->getMessage(),
//				'controller' => 'InvoicesController',
//				'function' => 'recalcAll'
//			]);
//			return false;
//		}
//	}

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

}
