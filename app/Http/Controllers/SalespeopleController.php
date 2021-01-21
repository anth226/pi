<?php

namespace App\Http\Controllers;

use App\Errors;
use App\Invoices;
use App\KmClasses\Pipedrive;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\Salespeople;
use App\SalespeopleLevels;
use App\SalespeoplePecentageLog;
use App\SecondarySalesPeople;
use Illuminate\Http\Request;
use Validator;
use Exception;
use Illuminate\Support\Facades\Auth;

class SalespeopleController extends InvoicesController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
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
		$salespeoples = Salespeople::orderBy('id','DESC')->with('level.level')->paginate(100);
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

			$query =  Invoices::with('customer')
			                  ->with('salespeople.salespersone')
								->whereHas('salespeople', function ($query) use($salesperson_id){
									$query->where('salespeople_id',$salesperson_id);
								})
			                  ->with('salespeople.level')
			;
			if ( ! empty( $request['date_range'] ) && empty( $request['search']['value'] ) ) {
				$date      = $request['date_range'];
				$dateArray = $this->parseDateRange( $date );
				$dateFrom  = date( "Y-m-d", $dateArray[0] );
				$dateTo    = date( "Y-m-d", $dateArray[1] );
				$query->where( 'invoices.access_date', '>=', $dateFrom )
				      ->where( 'invoices.access_date', '<=', $dateTo );
			}
			if( ! empty( $request['summary'] )){
				$commission = 0;
				$revenue = $query->sum('paid');
				$invoices = $query->get();
				if($invoices && $invoices->count()){
					foreach($invoices  as $inv){
						$sp = $inv->salespeople;
						if($sp && $sp->count()){
							foreach($sp as $s){
								if($s->salespeople_id == $salesperson_id) {
									$commission += $s->earnings;
								}
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
		return $this->sendError('no salesperson id');

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
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'max:120',
			'name_for_invoice' => 'max:120',
			'email' => 'required|unique:salespeoples,email,NULL,id,deleted_at,NULL|email|max:120',
			'phone_number' => 'nullable|max:120|min:10',
			'level_id' => 'required'
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		$salespeople = Salespeople::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $last_name,
			'name_for_invoice' => !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name,
			'email' => !empty($request->input('email')) ? $request->input('email') : '',
			'phone_number' => !empty($request->input('phone_number')) ? $request->input('phone_number') : '',
			'formated_phone_number' => !empty($request->input('phone_number')) ? FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')) : '',
		]);


		$new_level = SalespeopleLevels::find( $request->input( 'level_id' ) );
		if(!empty($new_level) && !empty($new_level->id)) {
			$level_log_created = SalespeoplePecentageLog::create( [
				'level_id'       => $new_level->id,
				'salespeople_id' => $salespeople->id,
				'percentage'     => $new_level->percentage
			] );
		}
		if(empty($level_log_created) || empty($level_log_created->id) ){
			Salespeople::where('id', $salespeople->id)->delete();
			return back()->withErrors( [ 'Can\'t create record' ] )
			             ->withInput();
		}


		return redirect()->route('salespeople.index')
		                 ->with('success','Salesperson created successfully');
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
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
		$user = Auth::user();
		if( $user->hasRole('Salesperson')){
			$salesperson_id = Salespeople::where('email', $user->email)->value('id');
			if($salesperson_id && $id == $salesperson_id) {
				$salespeople = Salespeople::with( 'level.level' )->find( $salesperson_id );
				if ( $salespeople ) {

					return view( 'salespeople.show', compact( 'salespeople', 'firstDate', 'lastDate' ) );
				}
			}
			return abort(403);
		}
		else {
			$salespeople = Salespeople::with( 'level.level' )->find( $id );
			if ( $salespeople ) {
				return view( 'salespeople.show', compact( 'salespeople', 'firstDate', 'lastDate' ) );
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
		$salespeople = Salespeople::with('level.level')->find($id);
		if($salespeople) {
			$levels = SalespeopleLevels::getIdsAndFullNames();
			return view( 'salespeople.edit', compact( 'salespeople', 'levels' ) );
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
			$salespeople->save();

			if ( $salespeople->level->level_id != $request->input( 'level_id' ) ) {
				$new_level = SalespeopleLevels::find($request->input( 'level_id' ));
				SalespeoplePecentageLog::create([
					'level_id' => $new_level->id,
					'salespeople_id' => $salespeople->id,
					'percentage' => $new_level->percentage
				]);
			}

			return redirect()->route( 'salespeople.index' )
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
}
