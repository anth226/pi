<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BaseController extends Controller
{
	/**
	 * success response method.
	 *
	 * @return \Illuminate\Http\Response|array
	 */
	public function sendResponse($result, $message = '', $json = true)
	{
		$response = [
			'success' => true,
			'data'    => $result,
			'message' => $message,
		];

		if($json) {
			return response()->json( $response, 200 );
		}

		return $response;
	}


	/**
	 * return error response.
	 *
	 * @return \Illuminate\Http\Response|array
	 */
	public function sendError($error, $errorMessages = [], $code = 404, $json = true)
	{
		$response = [
			'success' => false,
			'message' => $error,
		];


		if(!empty($errorMessages)){
			$response['data'] = $errorMessages;
		}

		if($json){
			return response()->json($response, $code);
		}

		return $response;
	}
}
