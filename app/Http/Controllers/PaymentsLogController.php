<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\CommissionsBalance;
use App\Errors;

use App\Salespeople;

use App\Invoices;

use DB;
use Exception;
use Validator;

class PaymentsLogController extends BaseController
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:payments-manage');
	}

	public function index(Request $request)
	{
//		$currDate = Carbon::now();
//		$fDate = $currDate->firstOfMonth();
//		$firstDate = date("F j, Y", $fDate->timestamp);
//		$first_date_str = CommissionsBalance::where('paid_amount', '!=', 0)->orderBy('created_at', 'asc')->value('created_at');
//		$firstDate = date("F j, Y", strtotime($first_date_str));
//		$lastDate = date("F j, Y");
//		return view('pay.paylog', compact( 'firstDate', 'lastDate' ) );
		return view('pay.paylog' );
	}

	public function anyData(Request $request){
		$query = CommissionsBalance::where('paid_amount', '!=', 0)->with('salespersone.level.level');
		if ( ! empty( $request['date_range'] ) && empty( $request['search']['value'] ) ) {
			$date      = $request['date_range'];
			$dateArray = $this->parseDateRange( $date );
			$dateFrom  = date( "Y-m-d", $dateArray[0] );
			$dateTo    = date( "Y-m-d", $dateArray[1] );
			$query->where( 'access_date', '>=', $dateFrom )
			      ->where( 'access_date', '<=', $dateTo );
		}
		return datatables()->eloquent( $query )->toJson();
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
}
