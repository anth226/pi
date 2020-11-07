<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\InputMessages;
use App\Exeptions;
use App\Leads;
use App\Messages;
use App\Uleads;
use App\Unsubscribed;
use Twilio\TwiML\MessagingResponse;
use App\KmClasses\Sms\SmsSender;

use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;

class InputmessagesController extends BaseController
{
	/**
	 *
	 *
	 * @return \Illuminate\Http\Response
	 */
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

			if(!empty($input)){
				$data = array(
					'input'      => json_encode( $input ),
					'body'       => ! empty( $input['Body'] ) ? $input['Body'] : "",
					'from'       => ! empty( $input['From'] ) ? $input['From'] : "",
					'to'         => ! empty( $input['To'] ) ? $input['To'] : ""
				);

				if(	! empty( $input['From'] ) && ! empty( $input['Body'] )){
					$body =  trim(strtolower($input['Body']));
					switch($body){
						case 'stop':
						case 'end':
						case 'cancel':
						case 'unsubscribe':
						case 'quit':
							$data['is_unsubscribed'] = 1;
							$sender = new SmsSender();
							$sender->unsubscribeNumber($input['From']);
							$message = Messages::where('message_type', 1)->first();
							if($message && $message->message) {
								$response = new MessagingResponse;
								$response->message($message->message);
								print $response;
							}
							break;
						case 'help':
						case 'info':
							$message = Messages::where('message_type', 3)->first();
							if($message && $message->message) {
								$response = new MessagingResponse;
								$response->message($message->message);
								print $response;
							}
							break;
						case 'start':
						case 'join':
							$sender = new SmsSender();
							$sender->subscribeNumber($input['From']);
							$message = Messages::where('message_type', 2)->first();
							if($message && $message->message) {
								$response = new MessagingResponse;
								$response->message($message->message);
								print $response;
							}
							break;
					}
					Uleads::updateInMessages($input['From']);

					if(config('app.url') == 'http://magicstarsystem.com'){
						$url = 'https://clickmart.magicstarsystem.com/api/vochankapkryukhaha';
						$postvars = http_build_query($input);
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_POST, count($input));
						curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
						curl_exec($ch);
						curl_close($ch);
					}

				}
				//$res = InputMessages::create( $data );
				//return $this->sendResponse( '', '' );
			}

		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'InputmessagesController', 'function' => 'inputProcessing'] );
			abort(500, $err);
		}

	}
}
