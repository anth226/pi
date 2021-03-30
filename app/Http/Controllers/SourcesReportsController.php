<?php

namespace App\Http\Controllers;

use App\Invoices;
use App\PipedriveData;
use App\SecondarySalesPeople;
use Illuminate\Http\Request;

class SourcesReportsController extends InvoicesController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:sources-reports-view');
	}

	public function index(Request $request)
	{
		$lastReportDate = Invoices::orderBy('access_date', 'desc')->value('access_date');
		$firstDate = date("F j, Y");
		$lastDate = date("F j, Y");
		if($lastReportDate) {
			$lastDate = date( "F j, Y", strtotime( $lastReportDate ) );
		}
		return view('reports.sources', compact('firstDate', 'lastDate'));
	}

	public function anyData(Request $request){
		$query =  Invoices::leftJoin( 'customers', function ( $join ) {
									$join->on( 'customers.id', 'invoices.customer_id' );
								} )
								->leftJoin( 'pipedrive_data', function ( $join ) {
									$join->on( 'customers.id', 'pipedrive_data.customer_id' );
								} )
								->leftJoin( 'strings', function ( $join ) {
									$join->on( 'pipedrive_data.pd_source_string_id', 'strings.id' );
								} )

		;


		if ( ! empty( $request['date_range'] ) ) {
			$date      = $request['date_range'];
			$dateArray = $this->parseDateRange( $date );
			$dateFrom  = date( "Y-m-d", $dateArray[0] );
			$dateTo    = date( "Y-m-d", $dateArray[1] );
			$query->where( 'invoices.access_date', '>=', $dateFrom )
			      ->where( 'invoices.access_date', '<=', $dateTo );
		}

		$query->selectRaw( '
				 sum(invoices.paid) as revenue,
				 count(invoices.id) as total_sales, 
				 strings.pi_name
			 ')
			  ->where(function($query)  {
					$query->where('pipedrive_data.field_name', 0)
					      ->orWhereNull('pipedrive_data.field_name');
			  })
		      ->groupBy('pipedrive_data.pd_source_string_id')
		      ->with('customer.invoices.salespeople');
		;
		return datatables()->eloquent( $query )->toJson();

	}
}
