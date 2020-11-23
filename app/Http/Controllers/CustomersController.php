<?php

namespace App\Http\Controllers;

use App\Customers;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\KmClasses\Sms\UsStates;
use Illuminate\Http\Request;
use Validator;

class CustomersController extends Controller
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:customer-list|customer-create|customer-edit|customer-delete', ['only' => ['index','show']]);
		$this->middleware('permission:customer-create', ['only' => ['create','store']]);
		$this->middleware('permission:customer-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:customer-delete', ['only' => ['destroy']]);
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$customers = Customers::with('invoices')->orderBy('id','DESC')->paginate(10);
		return view('customers.index',compact('customers'))
			->with('i', ($request->input('page', 1) - 1) * 10);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		$states = UsStates::statesUS();
		return view('customers.create', compact('states'));
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'required|max:120',
			'address_1' => 'required|max:120',
			'zip' => 'required|digits:5',
			'city' => 'required|max:120',
			'state' => 'required||max:20',
			'email' => 'required|unique:customers,email,NULL,id,deleted_at,NULL|email|max:120',
			'phone_number' => 'required|max:120|min:10',

		]);


		$customer = Customers::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'address_1' => $request->input('address_1'),
			'address_2' => !empty($request->input('address_2')) ? $request->input('address_2') : '',
			'city' => $request->input('city'),
			'state' => $request->input('state'),
			'zip' => $request->input('zip'),
			'email' => $request->input('email'),
			'phone_number' => $request->input('phone_number'),
			'formated_phone_number' => FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')),
		]);

		$this->sendLead([
			'first_name' => $request->input('first_name'),
			'last_name' => $request->input('last_name'),
			'full_name' => $request->input('first_name').' '.$request->input('last_name'),
			'email' => $request->input('email'),
			'phone' => $request->input('phone_number'),
			'source' => 'portfolioinsider',
			'tags' => 'portfolioinsider,portfolio-insider-prime'
		]);


		return redirect()->route('customers.show', ['customer_id' => $customer->id])
		                 ->with('success','Customer created successfully');


	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$customer = Customers::with('invoices')->find($id);
		if($customer) {
			return view( 'customers.show', compact( 'customer' ) );
		}
		return abort(404);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$customer = Customers::find($id);
		if($customer) {
			$states        = UsStates::statesUS();
			$customerState = $customer->state;

			return view( 'customers.edit', compact( 'customer', 'states', 'customerState' ) );
		}
		return abort(404);
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$this->validate($request, [
			'first_name' => 'required|max:120',
			'last_name' => 'required|max:120',
			'address_1' => 'required|max:120',
			'zip' => 'required|digits:5',
			'city' => 'required|max:120',
			'state' => 'required||max:20',
			'phone_number' => 'required|max:120|min:10'
		]);

		$customer = Customers::find($id);
		$customer->first_name = $request->input('first_name');
		$customer->last_name =  $request->input('last_name');
		$customer->address_1 = $request->input('address_1');
		$customer->address_2 =  !empty($request->input('address_2')) ? $request->input('address_2') : '';
		$customer->zip = $request->input('zip');
		$customer->state = $request->input('state');
		$customer->phone_number = $request->input('phone_number');
		$customer->formated_phone_number = FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number'));
		$customer->save();

		return redirect()->route('customers.index')
		                 ->with('success','Customer updated successfully');
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		Customers::where('id',$id)->delete();
		return redirect()->route('customers.index')
		                 ->with('success','Customer deleted successfully');
	}


	protected function sendLead($input){
		$url = 'https://magicstarsystem.com/api/ulp';
		$postvars = http_build_query($input);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, count($input));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
		curl_exec($ch);
		curl_close($ch);
	}
}
