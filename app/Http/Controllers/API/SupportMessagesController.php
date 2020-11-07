<?php

namespace App\Http\Controllers\API;

use App\Events\NewMessage;
use App\SupportMessages;
use App\SupportNumbers;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Exeptions;
use Exception;


class SupportMessagesController extends BaseController
{
	public function index(Request $request)
	{
		$this->messageReports($request);
	}
	public function store(Request $request)
	{
		$this->messageReports($request);
	}

	protected function messageReports($request){

		try {
			$input = $request->all();
			$status        = ! empty( $input['MessageStatus'] ) ? $input['MessageStatus'] : '';
			$message_id        = ! empty( $input['me_id'] ) ? $input['me_id'] : 0;
			$from = ! empty( $input['From'] ) ? $input['From'] : 0;

			if(($status == 'delivered' || $status == 'undelivered') && $from && $message_id){
				$sup_num_id = SupportNumbers::where('support_number', $from)->value('id');
				if($sup_num_id) {
					$sent_by_admin = 2;
					if($status == 'undelivered'){
						$sent_by_admin = 3;
					}
					SupportMessages::where('id', $message_id)->update(['sent_by_admin' => $sent_by_admin]);
					$message = SupportMessages::where('id', $message_id)->first();
					if($message) {
						broadcast(new NewMessage($message, $sup_num_id));
					}
				}
			}
			else{
				$err = 'status: '.$status;
				$err .= ', from: '.$from;
				$err .= ', message_id: '.$message_id;
				Exeptions::create( ['error' => $err, 'controller' => 'SupportMessagesController', 'function' => 'messageReports'] );
			}

			return $this->sendResponse( '', '' );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'SupportMessagesController', 'function' => 'messageReports'] );
			abort(500, $ex->getMessage());
		}
		return true;
	}
}
