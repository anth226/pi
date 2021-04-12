<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\PhoneNumbers;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Errors;
use Exception;

class TwilioNumbersController extends BaseController
{
	public $twilio ;

	public function __construct()
	{
		$this->middleware(['auth']);
		$this->middleware('permission:make-calls');
		$this->twilio = new Client(config('twilio.twilio.twilio_sid'), config('twilio.twilio.twilio_token'));
	}

	public function getAvailibleNumber($areaCode){
		try {
			$local = $this->twilio->availablePhoneNumbers( "US" )
				->local
				->read( [
					"areaCode"                  => $areaCode,
					"smsEnabled"                => true,
					"voiceEnabled"              => true,
					"excludeAllAddressRequired" => true
				], 1 );

			return $local;
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'TwilioNumbersController',
				'function' => 'getAvailibleNumber'
			]);
			return $this->sendError($error);
		}
	}

	public function buyNumber($phone_number){
		try {
			$new_phone_number = $this->twilio->incomingPhoneNumbers
				->create( [ "phoneNumber" => $phone_number ] );
			return $new_phone_number;
		}
		catch (TwilioException $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'TwilioNumbersController',
				'function' => 'buyNumber'
			]);
			return $this->sendError($error);
		}
	}

	public function getNumber($areaCode){
		try {
			$user = Auth::user();
			$availibleNumbers = $this->getAvailibleNumber($areaCode);
			$newNumber = $this->buyNumber($availibleNumbers[0]->phoneNumber);
			$data_to_save = [
				'friendlyName' => $newNumber->friendlyName,
				'phoneNumber' => $newNumber->phoneNumber,
				'sid' => $newNumber->sid,
				'buyer_user_id' => $user->id
			];
			$res = PhoneNumbers::create($data_to_save);
			return $this->sendResponse($res->phoneNumber);
		}
		catch (Exception $ex){
			$error = $ex->getMessage();
			Errors::create([
				'error' => $error,
				'controller' => 'TwilioNumbersController',
				'function' => 'getNumber'
			]);
			return $this->sendError($error);
		}
	}
}
