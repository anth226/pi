<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use Illuminate\Http\Request;

class UnsubscribeController extends BaseController
{
    function __construct(){
        $this->middleware(['auth']);
        $this->middleware('permission:unsubscribe_nonprime_customer_from_text');

    }
    public function index(Request $request){
        return view( 'unsubscribe');
    }

    public function unsubscribe(Request $request){
        // unsubscribe SMS
        $input = $request->all();
        $phone_number = !empty($input['phone_number']) ? $input['phone_number'] : '';

        $cc = new CustomersController();
        $ajaxData      = [
            'phone_number' => $phone_number,
            'token'  => 'PortInsQezInch111'
        ];
        if(config('app.env') == 'local') {
            $ajaxData['prime_tag_id'] = 12;
        }
        $smssystem_res = $cc->sendDataToSMSSystem( $ajaxData, 'ungrancelleadnonprime' );
        return response()->json( $smssystem_res, 200 );
    }
}
