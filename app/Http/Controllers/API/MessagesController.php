<?php

namespace App\Http\Controllers\API;

use App\Logs;
use App\Responses;
use App\CampaignsReports;
use App\Exeptions;
use App\Uleads;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;
use Carbon\Carbon;



class MessagesController extends BaseController
{
    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
//	    $this->writeToLogs($request);
	    $this->messageReports($request);
    }
	public function store(Request $request)
	{
//		$this->writeToLogs($request);
		$this->messageReports($request);
	}

    protected function writeToLogs($request){
	        $this->messageReports($request);
			try {
				$input = $request->all();

				Validator::make( $input, [
					'MessageSid' => 'required'
				] )->validate();

				$sid           = $input['MessageSid'];
				$status        = ! empty( $input['MessageStatus'] ) ? $input['MessageStatus'] : "none";
				$leadId        = ! empty( $input['leadid'] ) ? $input['leadid'] : 0;
				$campaignId    = ! empty( $input['cid'] ) ? $input['cid'] : 0;
				$campaign_type = ! empty( $input['ctype'] ) ? $input['ctype'] : 0;

				$data = array(
					//'response'      => json_encode( $input ),
					'MessageStatus' => $status,
					'sid'           => $sid,
					'from'          => ! empty( $input['From'] ) ? $input['From'] : "none",
					'to'            => ! empty( $input['To'] ) ? $input['To'] : "none",
					'error_code'    => ! empty( $input['ErrorCode'] ) ? $input['ErrorCode'] : "",
					'leadId'        => $leadId,
					'campaignId'    => $campaignId,
					'campaign_type' => $campaign_type
				);

				$res = Responses::create( $data );
				return $this->sendResponse( '', '' );
			}
			catch (Exception $ex){
				abort(500, $ex->getMessage());
			}
			return false;
	}

	protected function messageReports($request){

		try {
			$input = $request->all();
			$status        = ! empty( $input['MessageStatus'] ) ? $input['MessageStatus'] : '';
			$campaignId    = ! empty( $input['cid'] ) ? $input['cid'] : 0;
			$campaign_type = ! empty( $input['ctype'] ) ? $input['ctype'] : 0;
			$formated_phone_number = ! empty( $input['To'] ) ? $input['To'] : 0;

			$report_date = Carbon::today();
			if($campaignId) {
				$res = CampaignsReports::firstOrCreate(['campaignId' => $campaignId, 'campaign_type' => $campaign_type, 'report_date' => $report_date]);
				if($res && ($status == 'delivered' || $status == 'undelivered' || $status == 'sent')) {
					$res->increment( $status, 1 );
					$res->save();
				}
			}
			else{
				Exeptions::create( ['error' => 'No campaignId', 'controller' => 'MessagesController', 'function' => 'messageReports'] );
			}

			if($status == 'delivered' && $formated_phone_number){
				Uleads::where('formated_phone_number', $formated_phone_number)->update(['sms_status' => 0]);
			}

			if($status == 'undelivered' && $formated_phone_number){
				$result = Uleads::where('formated_phone_number', $formated_phone_number)->where('sms_status', '<=', 3)->first();
				if($result){
					$result->increment( 'sms_status', 1 );
					$result->save();
				}
			}
			return $this->sendResponse( '', '' );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'MessagesController', 'function' => 'messageReports'] );
			abort(500, $ex->getMessage());
		}
		return true;
	}

}
