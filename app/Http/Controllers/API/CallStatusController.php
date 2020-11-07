<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Exeptions;
use App\CallResponses;
use App\CallingNumbers;
use App\CampaignsReports;
use App\Uleads;
use App\CallsDurations;


use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;
use Carbon\Carbon;


class CallStatusController extends BaseController
{
	public function index(Request $request)
	{
//		$this->callStatus($request);  // need to remove
		$this->callReports($request);
	}
	public function store(Request $request)
	{
//		$this->callStatus($request);  // need to remove
		$this->callReports($request);
	}

	protected function callStatus($request){
			$this->callReports($request);
			try {
				$input = $request->all();

			Validator::make( $input, [
				'CallStatus' => 'required',
				'CallSid' => 'required'
			] )->validate();

				$sid           = $input['CallSid'];
				$status        = ! empty( $input['CallStatus'] ) ? $input['CallStatus'] : "none";
				$leadId        = ! empty( $input['leadid'] ) ? $input['leadid'] : 0;
				$campaignId    = ! empty( $input['cid'] ) ? $input['cid'] : 0;
				$campaign_type = ! empty( $input['ctype'] ) ? $input['ctype'] : 0;
				$duration = ! empty( $input['CallDuration'] ) ? $input['CallDuration'] : 0;

				$from = ! empty( $input['From'] ) ? $input['From'] : false;

				$data = array(
					'response'      => json_encode( $input ),
					'CallStatus' => $status,
					'sid'           => $sid,
					'from'          => ! empty( $input['From'] ) ? $input['From'] : "none",
					'to'            => ! empty( $input['To'] ) ? $input['To'] : "none",
					'error_code'    => ! empty( $input['ErrorCode'] ) ? $input['ErrorCode'] : "",
					'leadId'        => $leadId,
					'campaignId'    => $campaignId,
					'campaign_type' => $campaign_type,
					'duration' => $duration
				);
//				if(!empty($sid) && $from && ($status == 'completed' || $status == 'busy'  || $status == 'no-answer'  || $status == 'failed')) {
//					CallingNumbers::where( 'formated_phone', $from )->where('calls','>',0)->decrement( 'calls',1);
//				}
				$res = CallResponses::create( $data );
				return $this->sendResponse( '', '' );
			}
			catch (Exception $ex){
				abort(500, $ex->getMessage());
			}
			return true;
	}
	protected function callReports($request){

		try {
			$input = $request->all();
			$status        = ! empty( $input['CallStatus'] ) ? $input['CallStatus'] : '';
			$campaignId    = ! empty( $input['cid'] ) ? $input['cid'] : 0;
			$campaign_type = ! empty( $input['ctype'] ) ? $input['ctype'] : 0;
			$duration = ! empty( $input['CallDuration'] ) ? $input['CallDuration'] : 0;
			$answeredBy = ! empty( $input['AnsweredBy'] ) ? $input['AnsweredBy'] : false;
			$leadId        = ! empty( $input['leadid'] ) ? $input['leadid'] : 0;
			$formated_phone_number       = ! empty( $input['To'] ) ? $input['To'] : 0;


			$report_date = Carbon::today();
			if($campaignId) {
				$res = CampaignsReports::firstOrCreate(['campaignId' => $campaignId, 'campaign_type' => $campaign_type, 'report_date' => $report_date]);
				if($res){
					if($status == 'completed' || $status == 'busy' || $status == 'failed' || $status == 'no-answer' || $status == 'in-progress') {
						$res->increment( $status, 1 );
					}
					if($answeredBy) {
						switch ( $answeredBy ) {
							case 'human':
							case 'unknown':
								$res->increment( $answeredBy, 1 );
								break;
							default:
								$res->increment( 'machine', 1 );
						}
					}

					if($duration){
						$res->increment( 'duration', $duration );
						if($duration >= 180){
							$res->increment( 'more3min', 1 );
						}
//						if($leadId){
//							CallsDurations::updateMaxDuration($leadId, $duration);
//						}
						if($formated_phone_number){
							Uleads::updateMaxDuration($formated_phone_number, $duration);
						}
					}
					$res->save();
				}
			}
			else{
				Exeptions::create( ['error' => 'No campaignId', 'controller' => 'CallStatusController', 'function' => 'callReports'] );
			}

			if($status == 'completed' && $formated_phone_number){
				Uleads::where('formated_phone_number', $formated_phone_number)->update(['call_status' => 0]);
			}

			if($status == 'failed' && $formated_phone_number){
				$result = Uleads::where('formated_phone_number', $formated_phone_number)->where('call_status', '<=', 3)->first();
				if($result){
					$result->increment( 'call_status', 1 );
					$result->save();
				}
			}

			return $this->sendResponse( '', '' );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'CallStatusController', 'function' => 'callReports'] );
			abort(500, $ex->getMessage());
		}
		return true;
	}
}
