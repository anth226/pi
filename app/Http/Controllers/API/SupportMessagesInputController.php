<?php

namespace App\Http\Controllers\API;

use App\Events\NewMessage;
use App\SupportContacts;
use App\SupportMessages;
use App\SupportNumbers;
use Illuminate\Http\Request;
use App\Exeptions;
use Validator, Input, Exception;
use Carbon\Carbon;
use App\MMSMedia;


class SupportMessagesInputController extends BaseController
{
	public function index(Request $request)
	{
		$this->inputProcessing($request);
	}
	public function store(Request $request)
	{
		$this->inputProcessing($request);
	}

	protected function inputProcessing($request){
		try {
			$input = $request->all();
			if(
				!empty($input) &&
				!empty( $input['From'] ) &&
				$input['To']
			){
				$sup_num_id = SupportNumbers::where('support_number', $input['To'])->value('id');
				if($sup_num_id) {
					$contact_id = SupportContacts::where( 'contact_number', $input['From'] )->where( 'support_number_id', $sup_num_id )->value('id');
					if ( ! $contact_id ) {
						$contact = SupportContacts::create( [ 'contact_number' => $input['From'], 'support_number_id' => $sup_num_id ] );
						if ( $contact && $contact->id ) {
							$contact_id = $contact->id;
						}
					}
					else{
						SupportContacts::where('id', $contact_id)->update(['updated_at' => Carbon::now(), 'status' => 1]);
					}
					if ( $contact_id ) {
						$message = SupportMessages::create(
							[
								'contact_id' => $contact_id,
								'message' => !empty($input['Body']) ? $input['Body'] : ''
							]
						);
						if($message){
							$this->handleIncomingSMS($request, $message->id);
							broadcast(new NewMessage($message, $sup_num_id));
						}
					}
				}
			}
			return $this->sendResponse( '', '' );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'SupportMessagesInputController', 'function' => 'inputProcessing'] );
			abort(500, $err);
			return $this->sendError($err);
		}

	}

	public function handleIncomingSMS(Request $request, $messageId)
	{
		$NumMedia = (int)$request->input('NumMedia');

		for ($i=0; $i < $NumMedia; $i++) {
			$mediaUrl = $request->input("MediaUrl$i");
			$MIMEType = $request->input("MediaContentType$i");

			$mediaData = compact('mediaUrl', 'messageId', 'MIMEType');
			$mmsMedia = new MMSMedia($mediaData);
			$mmsMedia->save();
		}
	}

}
