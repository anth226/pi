<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Exeptions;
use App\Conversions;
use App\ConversionUrl;

use App\Http\Controllers\API\BaseController as BaseController;
use Validator, Input, Exception;

class ConversionController extends BaseController
{
	public function index(Request $request)
	{
		$this->getConversion($request);
	}
	public function store(Request $request)
	{
		$this->getConversion($request);
	}

	protected function getConversion($request){

		try {
			echo "<pre>";
			var_export($request->all());
			echo "</pre>";
			$input = $request->all();
			$url        = ! empty( $input['url'] ) ? $input['url'] : '';
			$leadId        = ! empty( $input['leadId'] ) ? $input['leadId'] : 0;

			if($leadId && $url) {
				$res = ConversionUrl::addUrl($url);
				if($res && $res->id){
					$r = Conversions::create(['url_id' => $res->id, 'leadId' => $leadId]);
					if(empty($r)){
						Exeptions::create( ['error' => 'can\'t create record', 'controller' => 'ConversionController', 'function' => 'getConversion'] );
					}
				}
				else{
					Exeptions::create( ['error' => 'can\'t create url record', 'controller' => 'ConversionController', 'function' => 'getConversion'] );
				}
			}
			else{
				Exeptions::create( ['error' => 'No Leadid or url', 'controller' => 'ConversionController', 'function' => 'getConversion'] );
			}
			return $this->sendResponse( '', '' );
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'ConversionController', 'function' => 'getConversion'] );
			abort(500, $ex->getMessage());
		}
		return true;
	}
}
