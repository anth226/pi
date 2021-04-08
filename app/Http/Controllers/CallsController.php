<?php

namespace App\Http\Controllers;

use App\PhoneNumbers;
use App\Salespeople;
use Illuminate\Http\Request;
use App\KmClasses\Sms\Elements;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\SmsSender;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Facades\Image;

class CallsController extends Controller
{
	public $support_number_id;
	private $img_folder;

	public function __construct(Request $request)
	{
		$this->middleware(['auth']);
		$this->middleware('permission:make-calls');
		$this->support_number_id = $request->input('support_id');
		$this->img_folder = 'sms_support_images';
	}

	public function index($salespeople_id)
	{
		$salesperson = Salespeople::find($salespeople_id);
		return view( 'calls.index' , ['salesperson' => $salesperson]);
	}

}
