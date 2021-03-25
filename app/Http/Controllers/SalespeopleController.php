<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use App\CommissionPaymentsLog;
use App\CommissionsBalance;
use App\Errors;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\LevelsSalespeople;
use App\Salespeople;
use App\SalespeopleLevels;
use App\SalespeoplePecentageLog;
use App\SecondarySalesPeople;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;
use Exception;
use Illuminate\Support\Facades\Auth;

class SalespeopleController extends InvoicesController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:payments-manage', ['only' => ['setPaid']]);
		$this->middleware('permission:salespeople-list|salespeople-create|salespeople-edit|salespeople-delete|salespeople-reports-view-all|salespeople-reports-view-own', ['only' => ['show', 'anyData']]);
		$this->middleware('permission:salespeople-list|salespeople-create|salespeople-edit|salespeople-delete', ['only' => ['index']]);
		$this->middleware('permission:salespeople-create', ['only' => ['create','store']]);
		$this->middleware('permission:salespeople-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:salespeople-delete', ['only' => ['destroy']]);
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$salespeoples = Salespeople::orderBy('id','DESC')->with('level3.level')->paginate(100);
		return view('salespeople.index',compact('salespeoples'))
			->with('i', ($request->input('page', 1) - 1) * 100);
	}

	public function anyData(Request $request){
		$user = Auth::user();
		if( $user->hasRole('Salesperson')) {
			$salesperson_id = Salespeople::where( 'email', $user->email )->value( 'id' );
		}
		else{
			$salesperson_id = !empty($request['salesperson_id']) ? $request['salesperson_id'] : 0;
		}

		if($salesperson_id) {

			if(! empty( $request['discrepancy'] )){
				$dispInvoices = !empty($request['dispInvoices']) ? $request['dispInvoices'] : [];

				$query = Invoices::with( 'customer' )
				                 ->with( 'salespeople.salespersone' )
				                 ->whereHas( 'salespeople', function ( $query2 ) use ( $salesperson_id, $dispInvoices ) {
					                 $query2->where( 'salespeople_id', $salesperson_id )->where(function($q3) use($dispInvoices) {
						                 $q3->where('discrepancy','<>', 0);
						                 if(count($dispInvoices)){
							                 $q3->orWhereIn('invoices.id', $dispInvoices);
						                 };
					                 });
				                 } )
				                 ->with( 'salespeople.level' )
//				                 ->with( 'commissionPayments' )
				;



				if ( ! empty( $request['date_range'] )) {
					$date      = $request['date_range'];
					$dateArray = $this->parseDateRange( $date );
					$dateFrom  = date( "Y-m-d", $dateArray[0] );
					$dateTo    = date( "Y-m-d", $dateArray[1] );
					$query->where(function($q) use($dateFrom, $dateTo){
						$q->where( 'invoices.access_date', '<', $dateFrom );
						$q->orWhere( 'invoices.access_date', '>', $dateTo );
					});
				}



				if ( ! empty( $request['summary'] ) ) {
					$discrepancy    = 0;
					$invoices   = $query->get();
					if ( $invoices && $invoices->count() ) {
						foreach ( $invoices as $inv ) {
							$sp = $inv->salespeople;
							if ( $sp && $sp->count() ) {
								foreach ( $sp as $s ) {
									if ( $s->salespeople_id == $salesperson_id ) {
										$discrepancy += $s->discrepancy;
									}
								}
							}
						}
					}
					$res = [
						'discrepancy'    => $discrepancy,
					];
					return $this->sendResponse( $res, '' );
				} else {
					return datatables()->eloquent( $query )->toJson();
				}
			}
			else {
				$query = Invoices::with( 'customer' )
				                 ->with( 'salespeople.salespersone' )
				                 ->whereHas( 'salespeople', function ( $query2 ) use ( $salesperson_id ) {
					                 $query2->where( 'salespeople_id', $salesperson_id );
				                 } )
				                 ->with( 'salespeople.level' )
//				                 ->with( 'commissionPayments' )
				;
				if ( ! empty( $request['date_range'] ) && empty( $request['search']['value'] ) ) {
					$date      = $request['date_range'];
					$dateArray = $this->parseDateRange( $date );
					$dateFrom  = date( "Y-m-d", $dateArray[0] );
					$dateTo    = date( "Y-m-d", $dateArray[1] );
					$query->where( 'invoices.access_date', '>=', $dateFrom )
					      ->where( 'invoices.access_date', '<=', $dateTo );
				}

				if ( ! empty( $request['summary'] ) ) {
					$commission = 0;
					$paid       = 0;
					$revenue    = $query->sum( 'paid' );
					$invoices   = $query->get();
					if ( $invoices && $invoices->count() ) {
						foreach ( $invoices as $inv ) {
							$sp = $inv->salespeople;
							if ( $sp && $sp->count() ) {
								foreach ( $sp as $s ) {
									if ( $s->salespeople_id == $salesperson_id ) {
										$commission += $s->earnings;
										$paid += $s->paid_amount;
									}
								}
							}
						}
					}
					$res = [
						'revenue'    => $revenue,
						'count'      => $query->count(),
						'commission' => $commission,
						'paid'       => $paid
					];

					return $this->sendResponse( $res, '' );
				} else {
					return datatables()->eloquent( $query )->toJson();
				}
			}
		}
		return $this->sendError('no salesperson id');

	}

	public function setPaid(Request $request){
		try{
			$this->validate($request, [
				'invoice_id' => 'required|numeric|min:1',
				'salespeople_id' => 'required|numeric|min:1',
				'paid_amount' => 'required',
				'action' => 'required'
			]);
			$record = SecondarySalesPeople::where('salespeople_id', $request['salespeople_id'])->where('invoice_id', $request['invoice_id'])->first();
			$paid_amount = $record->paid_amount * 1;
			$earnings = $record->earnings * 1;
			$discrepancy = $record->discrepancy * 1;
			$paid_at = $record->paid_at;
			$refresh_page_html = 'Data were changed. Please click <a class="h6 refresh_page" href="#">here</a> to refresh the page.';
			$payment_type = 0;
			$log_paid_amount = $request['paid_amount'] * 1;
			switch ($request['action']){
				case 'pay':
					$new_paid_amount =  $request['paid_amount'] * 1;
					if(($earnings != $new_paid_amount) || $paid_at || $discrepancy) {
						return $this->sendError($refresh_page_html);
					}
					$paid_amount = $new_paid_amount;
					$paid_at = Carbon::now();
					break;
				case 'pay_disc':
					$new_paid_amount =  $request['paid_amount'] * 1;
					if(($discrepancy != $new_paid_amount) || !$paid_at || !$discrepancy) {
						return $this->sendError($refresh_page_html);
					}
					$paid_amount = $paid_amount*1 + $new_paid_amount*1;
					$discrepancy = 0;
					$paid_at = Carbon::now();
					$payment_type = 1;
					break;
				case 'cancel':
					$new_paid_amount =  $request['paid_amount'] * 1;
					if(($earnings != $new_paid_amount) != 0 || !$paid_at || $discrepancy) {
						return $this->sendError($refresh_page_html);
					}
					$paid_amount = 0;
					$discrepancy = 0;
					$paid_at = null;
					$payment_type = 2;
					$log_paid_amount =  $log_paid_amount * (-1);
					break;
			}
			$user = Auth::user();
			$dataToUpdate = [
				'paid_amount' => $paid_amount,
				'discrepancy' => $discrepancy,
				'paid_at' => $paid_at
			];
			$logData = [
				'paid_amount' => $log_paid_amount,
				'payment_type' => $payment_type,
				'invoice_id' =>  $request['invoice_id'],
				'salespeople_id' =>  $request['salespeople_id'],
				'user_id' => $user->id
			];

			$res = SecondarySalesPeople::where('id', $record->id)->update($dataToUpdate);

			CommissionPaymentsLog::create($logData);

			return $this->sendResponse($res);

		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'InvoicesController',
				'function' => 'savePercentages'
			]);
			return $this->sendError($error);
		}
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		$levels = SalespeopleLevels::getIdsAndFullNames();
		return view( 'salespeople.create', compact( 'levels' ) );
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
				'first_name'       => 'required|max:120',
				'last_name'        => 'max:120',
				'name_for_invoice' => 'max:120',
				'email'            => 'required|unique:salespeoples,email,NULL,id,deleted_at,NULL|email|max:120',
				'phone_number'     => 'nullable|max:120|min:10',
				'level_id'         => 'required'
			] );

			$last_name = ! empty( $request->input( 'last_name' ) ) ? $request->input( 'last_name' ) : '';

			$salespeople = Salespeople::create( [
				'first_name'            => $request->input( 'first_name' ),
				'last_name'             => $last_name,
				'name_for_invoice'      => ! empty( $request->input( 'name_for_invoice' ) ) ? $request->input( 'name_for_invoice' ) : $request->input( 'first_name' ) . ' ' . $last_name,
				'email'                 => ! empty( $request->input( 'email' ) ) ? $request->input( 'email' ) : '',
				'phone_number'          => ! empty( $request->input( 'phone_number' ) ) ? $request->input( 'phone_number' ) : '',
				'formated_phone_number' => ! empty( $request->input( 'phone_number' ) ) ? FormatUsPhoneNumber::formatPhoneNumber( $request->input( 'phone_number' ) ) : '',
			] );

			$user = Auth::user();
			ActionsLog::create([
				'user_id' => $user->id,
				'model' => 3,
				'action' => 0,
				'related_id' => $salespeople->id
			]);

			if ( is_array( $request->input( 'level_id' ) ) ) {
				foreach ( $request->input( 'level_id' ) as $level_id ) {
					$new_level = SalespeopleLevels::find( $level_id );
					if ( ! empty( $new_level ) && ! empty( $new_level->id ) ) {
						LevelsSalespeople::create( [
							'level_id'       => $new_level->id,
							'salespeople_id' => $salespeople->id,
							'percentage'     => $new_level->percentage
						] );
					}
				}
			}
			else{
				return back()->withErrors( ['Error, No level selected' ] )
				             ->withInput();
			}

			return redirect()->route( 'salespeople.index' )
			                 ->with( 'success', 'Salesperson created successfully' );
		}
		catch ( Exception $ex){
			$err = $ex->getMessage();
			Errors::create( [ 'error'      => $err,
			                  'controller' => 'SalespeopleController',
			                  'function'   => 'store'
			] );
			return back()->withErrors( [ $err ] )
			             ->withInput();
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
		/*
		$firstDate = date("F j, Y");
		$lastDate = date("F j, Y");
		$lastReportDate =  SecondarySalesPeople::join( 'invoices', function ( $join ) use($id){
													$join->on( 'invoices.id', 'secondary_sales_people.invoice_id' )
													     ->where('secondary_sales_people.salespeople_id', $id)
													     ->whereNull( 'invoices.deleted_at' );
												} )->orderBy('invoices.access_date', 'desc')->first()
		;
		if($lastReportDate && !empty($lastReportDate->access_date)) {
			$lastDate = date( "F j, Y", strtotime( $lastReportDate->access_date ) );
		}
		*/

		$currDate = Carbon::now();
		$fDate = $currDate->firstOfMonth();
		$firstDate = date("F j, Y", $fDate->timestamp);
		$lastDate = date("F j, Y");

//		$earnings = SecondarySalesPeople::where('salespeople_id', $id)->sum('earnings');
//		if(empty($earnings)){
//			$earnings = 0;
//		}
//		$payments = CommissionsBalance::where('salespeople_id', $id)->sum('paid_amount');
//		if(empty($payments)){
//			$payments = 0;
//		}
//
//		$to_pay = $this->moneyFormat($earnings - $payments);

		$user = Auth::user();
		if( $user->hasRole('Salesperson')){
			$salesperson_id = Salespeople::where('email', $user->email)->value('id');
			if($salesperson_id && $id == $salesperson_id) {
				$salespeople = Salespeople::with( 'level.level' )->find( $salesperson_id );
				if ( $salespeople ) {
					$firstDate = date("F j, Y");
					$lastDate = date("F j, Y");
					$lastReportDate =  SecondarySalesPeople::join( 'invoices', function ( $join ) use($id){
						$join->on( 'invoices.id', 'secondary_sales_people.invoice_id' )
						     ->where('secondary_sales_people.salespeople_id', $id)
						     ->whereNull( 'invoices.deleted_at' );
					} )->orderBy('invoices.access_date', 'desc')->first()
					;
					if($lastReportDate && !empty($lastReportDate->access_date)) {
						$firstDate = $lastDate = date( "F j, Y", strtotime( $lastReportDate->access_date ) );
					}
					return view( 'salespeople.show', compact( 'salespeople', 'firstDate', 'lastDate' ) );
				}
			}
			return abort(403);
		}
		else {
			$salespeople = Salespeople::with( 'level.level' )->find( $id );
			if ( $salespeople ) {
				return view( 'salespeople.show', compact( 'salespeople', 'firstDate', 'lastDate', 'to_pay' ) );
			}
			return abort( 404 );
		}
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$salespeople = Salespeople::with('level3.level')->find($id);
		if($salespeople) {
			$levels = SalespeopleLevels::getIdsAndFullNames();
			$salespeople_levels = [];
			foreach($salespeople->level3 as $l){
				$salespeople_levels[] = $l->level_id;
			}
			return view( 'salespeople.edit', compact( 'salespeople', 'levels', 'salespeople_levels' ) );
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
		try {
			$this->validate( $request, [
				'first_name'       => 'required|max:120',
				'last_name'        => 'max:120',
				'name_for_invoice' => 'max:120',
				'email'            => 'required|email|max:120',
				'phone_number'     => 'nullable|max:120|min:10',
				'level_id' => 'required'
			] );

			$last_name = ! empty( $request->input( 'last_name' ) ) ? $request->input( 'last_name' ) : '';

			$salespeople                        = Salespeople::with( 'level' )->find( $id );
			$salespeople->first_name            = $request->input( 'first_name' );
			$salespeople->last_name             = $last_name;
			$salespeople->email                 = ! empty( $request->input( 'email' ) ) ? $request->input( 'email' ) : '';
			$salespeople->name_for_invoice      = ! empty( $request->input( 'name_for_invoice' ) ) ? $request->input( 'name_for_invoice' ) : $request->input( 'first_name' ) . ' ' . $last_name;
			$salespeople->phone_number          = ! empty( $request->input( 'phone_number' ) ) ? $request->input( 'phone_number' ) : '';
			$salespeople->formated_phone_number = ! empty( $request->input( 'phone_number' ) ) ? FormatUsPhoneNumber::formatPhoneNumber( $request->input( 'phone_number' ) ) : '';


			$sp_before_update = Salespeople::where('id', $id)->first()->toArray();

			$user_logged = Auth::user();
			if($sp_before_update && count($sp_before_update)) {
				foreach($sp_before_update as $field_name => $old_value) {
					if(isset($salespeople->$field_name) && $salespeople->$field_name != $old_value && ($field_name == 'first_name' || $field_name != 'last_name' || $field_name != 'email' || $field_name != 'name_for_invoice' || $field_name != 'phone_number') ) {
						ActionsLog::create( [
							'user_id'    => $user_logged->id,
							'model'      => 3,
							'field_name' => $field_name,
							'old_value' => $old_value,
							'new_value' => $salespeople->$field_name,
							'action'     => 1,
							'related_id' => $id
						] );
					}
				}
			}

			$salespeople->save();


			if ( is_array( $request->input( 'level_id' ) ) ) {

				$levels_before_update = LevelsSalespeople::where('salespeople_id', $id)->with('level')->get();
				$levels_before = [];
				if($levels_before_update && $levels_before_update->count()){
					foreach($levels_before_update as $l){
						$levels_before[] = $l->level->title;
					}
				}
				$old_value = !empty($levels_before) ? implode(', ', $levels_before) : '';

				LevelsSalespeople::where('salespeople_id', $id)->delete();
				foreach ( $request->input( 'level_id' ) as $level_id ) {
					$new_level = SalespeopleLevels::find( $level_id );
					if ( ! empty( $new_level ) && ! empty( $new_level->id ) ) {
						LevelsSalespeople::create( [
							'level_id'       => $new_level->id,
							'salespeople_id' => $salespeople->id,
							'percentage'     => $new_level->percentage
						] );
					}
				}

				$levels_after_update = LevelsSalespeople::where('salespeople_id', $id)->with('level')->get();
				$levels_after = [];
				if($levels_after_update && $levels_after_update->count()){
					foreach($levels_after_update as $l){
						$levels_after[] = $l->level->title;
					}
				}
				$new_value = !empty($levels_after) ? implode(', ', $levels_after) : '';

				if($old_value != $new_value) {
					ActionsLog::create( [
						'user_id'    => $user_logged->id,
						'model'      => 3,
						'field_name' => 'levels',
						'old_value'  => $old_value,
						'new_value'  => $new_value,
						'action'     => 1,
						'related_id' => $id
					] );
				}

			}
			else{
				return back()->withErrors( ['Error, No level selected' ] )
				             ->withInput();
			}

			return redirect()->back()
			                 ->with( 'success', 'Salesperson updated successfully' );
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleController',
				'function' => 'update'
			]);
			return back()->withErrors( [ $ex->getMessage() ] )
			                    ->withInput();
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
		Salespeople::where('id',$id)->delete();
		$user = Auth::user();
		ActionsLog::create([
			'user_id' => $user->id,
			'model' => 3,
			'action' => 2,
			'related_id' => $id
		]);
		return redirect()->route('salespeople.index')
		                 ->with('success','Salesperson deleted successfully');
	}


	public function findOwnerOnPipedrive(){
		try {
			$salespeople = Salespeople::withTrashed()->get();
			$allUsers    = Pipedrive::executeCommand( config( 'pipedrive.api_key' ), new Pipedrive\Commands\getAllUsers() );
			if (
				! empty( $salespeople ) &&
				$salespeople->count() &&
				! empty( $allUsers ) &&
				count( $allUsers )
			) {
				foreach ( $salespeople as $s ) {
					if ( ! empty( $s->email ) ) {
						foreach ( $allUsers as $u ) {
							if (
								!empty($u) &&
								!empty($u->data) &&
								!empty($u->data->id) &&
								!empty($u->data->email) &&
								trim( strtolower( $u->data->email ) ) == trim( strtolower( $s->email ) )
							) {
								Salespeople::where( 'id', $s->id )->update( [ 'pipedrive_user_id' => $u->data->id ] );
							}
						}
					}
				}
				return true;
			}
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleController',
				'function' => 'findOwnerOnPipedrive'
			]);
			return false;
		}
	}

	public function moneyFormat($value){
		$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
		return $formatter->formatCurrency($value, 'USD');
	}
}
