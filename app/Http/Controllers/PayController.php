<?php

namespace App\Http\Controllers;

use App\CommissionsBalance;
use App\Http\Controllers\API\BaseController;
use Illuminate\Http\Request;
use App\Invoices;
use App\SecondarySalesPeople;


class PayController extends BaseController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:salespeople-reports-view-all');
	}

	public function index(Request $request)
	{
		$lastReportDate = Invoices::orderBy('access_date', 'desc')->value('access_date');
		$firstDate = date("F j, Y");
		$lastDate = date("F j, Y");
		if($lastReportDate) {
			$lastDate = date( "F j, Y", strtotime( $lastReportDate ) );
		}
		return view('pay.pay', compact('firstDate', 'lastDate'));
	}

	public function anyData(Request $request){
		$query =  SecondarySalesPeople::join( 'invoices', function ( $join ) {
											$join->on( 'invoices.id', 'secondary_sales_people.invoice_id' )->whereNull('invoices.deleted_at');
										} )
		                              ->join( 'salespeoples', function ( $join ) {
			                              $join->on( 'salespeoples.id', 'secondary_sales_people.salespeople_id' )->whereNull('invoices.deleted_at');
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
//		dd($query->get()->toArray());
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
					 sum(invoices.sales_price) as revenue,
					 (SELECT sum(commissions_balances.paid_amount) FROM commissions_balances WHERE commissions_balances.salespeople_id = secondary_sales_people.salespeople_id) as paid_sum,
					 secondary_sales_people.salespeople_id,
					 count(invoices.id) as total_sales, 
					 salespeoples.name_for_invoice, 
					 salespeoples.first_name, 
					 salespeoples.last_name
				 ')
			      ->groupBy('secondary_sales_people.salespeople_id')->with('level2.level')
			;

			return datatables()->eloquent( $query )->toJson();
		}

	}
}
