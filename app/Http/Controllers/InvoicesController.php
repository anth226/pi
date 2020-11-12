<?php

namespace App\Http\Controllers;

use App\Customers;
use App\EmailTemplates;
use App\Invoices;
use App\Products;
use App\Salespeople;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:invoice-list|invoice-create|invoice-edit|invoice-delete', ['only' => ['index','show']]);
		$this->middleware('permission:invoice-create', ['only' => ['create','store']]);
		$this->middleware('permission:invoice-edit', ['only' => ['edit','update']]);
		$this->middleware('permission:invoice-delete', ['only' => ['destroy']]);
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$invoices = Invoices::orderBy('id','DESC')->paginate(10);
		return view('invoices.index',compact('invoices'))
			->with('i', ($request->input('page', 1) - 1) * 10);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request)
	{
		$customerId = !empty($request->input('customer_id')) ? $request->input('customer_id') : 0;
		$customers = Customers::getIdsAndFullNames();
		$salespeople = Salespeople::getIdsAndFullNames();
		$products = Products::getIdsAndFullNames();
		$template = EmailTemplates::getIdsAndFullNames();
		return view('invoices.create', compact('customers','salespeople', 'customerId', 'products'));
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
			'email' => 'email|max:120',
			'phone_number' => 'max:120|min:10',
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		Invoices::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $last_name,
			'name_for_invoice' => !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name,
			'email' => !empty($request->input('email')) ? $request->input('email') : '',
			'phone_number' => !empty($request->input('phone_number')) ? $request->input('phone_number') : '',
		]);

		return redirect()->route('invoices.index')
		                 ->with('success','Invoice created successfully');
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$invoice = Invoices::find($id);
		return view('invoices.show',compact('invoice'));
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$invoice = Invoices::find($id);
		return view('invoices.edit',compact('invoice'));
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
			'email' => 'email|max:120',
			'phone_number' => 'max:120|min:10',
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		$invoice = Invoices::find($id);
		$invoice->first_name = $request->input('first_name');
		$invoice->last_name =  $last_name;
		$invoice->name_for_invoice =  !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name;
		$invoice->phone_number = !empty($request->input('phone_number')) ? $request->input('phone_number') : '';
		$invoice->save();

		return redirect()->route('invoices.index')
		                 ->with('success','Invoice updated successfully');
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		Invoices::where('id',$id)->delete();
		return redirect()->route('invoices.index')
		                 ->with('success','Invoice deleted successfully');
	}


	public function generateInvoiceNumber($id){
		$in_number = '00424-';
		$value = 1025 + $id;
		$valueWithZeros = $formatted_value = sprintf("%05d", $value);
		return $in_number.$valueWithZeros;
	}
}
