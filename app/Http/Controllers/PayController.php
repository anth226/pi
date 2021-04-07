<?php

namespace App\Http\Controllers;

use App\CommissionsBalance;
use App\Errors;
use App\Http\Controllers\API\BaseController;
use App\Salespeople;
use Illuminate\Http\Request;
use App\Invoices;
use App\SecondarySalesPeople;
use DB;
use Exception;
use Validator;


class PayController extends BaseController
{
	function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware('permission:payments-manage');
	}

	public function index(Request $request)	{

		return view('pay.pay');
	}

	public function anyData(Request $request){
		$query =  Salespeople::leftJoin( 'secondary_sales_people', function ( $join ) {
	                              $join->on( 'salespeoples.id', 'secondary_sales_people.salespeople_id' );
                              } )
							->join( 'invoices', function ( $join ) {
								$join->on( 'invoices.id', 'secondary_sales_people.invoice_id' )->whereNull('invoices.deleted_at');
							} )
		;

//		if ( ! empty( $request['date_range'] ) ) {
//			$date      = $request['date_range'];
//			$dateArray = $this->parseDateRange( $date );
//			$dateFrom  = date( "Y-m-d", $dateArray[0] );
//			$dateTo    = date( "Y-m-d", $dateArray[1] );
//			$query->where( 'invoices.access_date', '>=', $dateFrom )
//			      ->where( 'invoices.access_date', '<=', $dateTo );
//		}
		if( ! empty( $request['summary'] )){
//			DB::enableQueryLog();
//			$query->get();
//			dd(DB::getQueryLog());

			$res = [
				'commission' => $query->sum('secondary_sales_people.earnings'),
			];
			return $this->sendResponse($res,'');
		}
		else {
			$query->selectRaw( '
					 sum(secondary_sales_people.earnings) as sum,
					 (SELECT sum(commissions_balances.paid_amount) FROM commissions_balances WHERE commissions_balances.salespeople_id = secondary_sales_people.salespeople_id) as paid_sum,
					 sum(invoices.paid) as revenue,					 
					 count(invoices.id) as total_sales,
					 salespeoples.id,
					 salespeoples.name_for_invoice,
					 salespeoples.first_name,
					 salespeoples.last_name
				 ')
			      ->groupBy('salespeoples.id')->with('level.level')
			;
			return datatables()->eloquent( $query )->toJson();
		}

	}

	public function setPaid(Request $request ){
		try {
			$data = $request->all();
			Validator::make( $data, [
				'salespeople_id' => 'required',
				'amount' => 'required'
			] )->validate();

			$earnings = SecondarySalesPeople::where('salespeople_id', $data['salespeople_id'] )->sum('earnings');
			$paid_amount = CommissionsBalance::where('salespeople_id', $data['salespeople_id'] )->sum('paid_amount');
			$earnings = !empty($earnings) ? $earnings : 0;
			$paid_amount = !empty($paid_amount) ? $paid_amount : 0;
			$data = [
				'salespeople_id' => $data['salespeople_id'],
				'paid_amount' => $data['amount'] * 1,
				'unpaid_balance' => ($earnings -  $paid_amount) * 1
			];
			$res = CommissionsBalance::create($data);
			return $this->sendResponse( $res, 'Success!' );
		}
		catch ( Exception $ex){
			$err = $ex->getMessage();
			Errors::create( [ 'error'      => $err,
			                        'controller' => 'PayController',
			                        'function'   => 'setPaid'
			] );
			return $this->sendError( 'Error!' );
		}
	}
}
