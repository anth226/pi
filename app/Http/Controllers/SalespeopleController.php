<?php

namespace App\Http\Controllers;

use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\Salespeople;
use Illuminate\Http\Request;
use Validator;

class SalespeopleController extends Controller
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:salespeople-list|salespeople-create|salespeople-edit|salespeople-delete', ['only' => ['index','show']]);
		$this->middleware('permission:salespeople-create', ['only' => ['create','store']]);
		$this->middleware('permission:salespeople-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:salespeople-delete', ['only' => ['destroy']]);
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$salespeoples = Salespeople::orderBy('id','DESC')->paginate(10);
		return view('salespeople.index',compact('salespeoples'))
			->with('i', ($request->input('page', 1) - 1) * 10);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		return view('salespeople.create');
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
			'last_name' => 'max:120',
			'name_for_invoice' => 'max:120',
			'email' => 'required|email|max:120',
			'phone_number' => 'nullable|max:120|min:10',
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		Salespeople::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $last_name,
			'name_for_invoice' => !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name,
			'email' => !empty($request->input('email')) ? $request->input('email') : '',
			'phone_number' => !empty($request->input('phone_number')) ? $request->input('phone_number') : '',
			'formated_phone_number' => !empty($request->input('phone_number')) ? FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')) : '',

		]);

		return redirect()->route('salespeople.index')
		                 ->with('success','Salesperson created successfully');
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$salespeople = Salespeople::find($id);
		if($salespeople) {
			return view( 'salespeople.show', compact( 'salespeople' ) );
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
		$salespeople = Salespeople::find($id);
		if($salespeople) {
			return view( 'salespeople.edit', compact( 'salespeople' ) );
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
			'last_name' => 'max:120',
			'name_for_invoice' => 'max:120',
			'email' => 'required|email|max:120',
			'phone_number' => 'nullable|max:120|min:10',
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		$salespeople = Salespeople::find($id);
		$salespeople->first_name = $request->input('first_name');
		$salespeople->last_name =  $last_name;
		$salespeople->name_for_invoice =  !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name;
		$salespeople->phone_number = !empty($request->input('phone_number')) ? $request->input('phone_number') : '';
		$salespeople->formated_phone_number = !empty($request->input('phone_number')) ? FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')) : '';
		$salespeople->save();

		return redirect()->route('salespeople.index')
		                 ->with('success','Salesperson updated successfully');
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		Salespeople::where('id',$id)->delete();
		return redirect()->route('salespeople.index')
		                 ->with('success','Salesperson deleted successfully');
	}
}
