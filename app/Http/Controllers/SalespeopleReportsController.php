<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Invoices;
use App\SecondarySalesPeople;
use Illuminate\Http\Request;

class SalespeopleReportsController extends InvoicesController
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
		return view('reports.salespeople', compact('firstDate', 'lastDate'));
	}

	public function anyData(Request $request){
		$query =  SecondarySalesPeople::join( 'invoices', function ( $join ) {
											$join->on( 'invoices.id', 'secondary_sales_people.invoice_id' )->whereNull('invoices.deleted_at');
										} )
		                              ->join( 'salespeoples', function ( $join ) {
											$join->on( 'salespeoples.id', 'secondary_sales_people.salespeople_id' );
										} )
									  ;
			;


		if ( ! empty( $request['date_range'] ) ) {
			$date      = $request['date_range'];
			$dateArray = $this->parseDateRange( $date );
			$dateFrom  = date( "Y-m-d", $dateArray[0] );
			$dateTo    = date( "Y-m-d", $dateArray[1] );
			$query->where( 'invoices.access_date', '>=', $dateFrom )
			      ->where( 'invoices.access_date', '<=', $dateTo );
		}
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
					 sum(invoices.paid) as revenue,
					 secondary_sales_people.salespeople_id,
					 count(invoices.id) as total_sales, 
					 salespeoples.name_for_invoice, 
					 salespeoples.first_name, 
					 salespeoples.last_name
				 ')
				->groupBy('secondary_sales_people.salespeople_id')->with('level2.level');
			;
			return datatables()->eloquent( $query )->toJson();
		}

	}

}
