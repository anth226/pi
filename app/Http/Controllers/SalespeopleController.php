<?php

namespace App\Http\Controllers;

use App\Errors;
use App\KmClasses\Sms\FormatUsPhoneNumber;
use App\Salespeople;
use App\SalespeopleLevels;
use App\SalespeoplePecentageLog;
use Illuminate\Http\Request;
use Validator;
use Exception;
use Illuminate\Support\Facades\Auth;

class SalespeopleController extends Controller
{
	function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:salespeople-list|salespeople-create|salespeople-edit|salespeople-delete||salespeople-reports-view-own', ['only' => ['show']]);
		$this->middleware('permission:salespeople-list|salespeople-create|salespeople-edit|salespeople-delete', ['only' => ['index']]);
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
		$salespeoples = Salespeople::orderBy('id','DESC')->with('level.level')->paginate(100);
		return view('salespeople.index',compact('salespeoples'))
			->with('i', ($request->input('page', 1) - 1) * 100);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		$levels = SalespeopleLevels::getIdsAndFullNames();
		return view( 'salespeople.create', compact( 'levels' ) );
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
			'email' => 'required|unique:salespeoples,email,NULL,id,deleted_at,NULL|email|max:120',
			'phone_number' => 'nullable|max:120|min:10',
			'level_id' => 'required'
		]);

		$last_name = !empty($request->input('last_name')) ? $request->input('last_name') : '';

		$salespeople = Salespeople::create([
			'first_name' => $request->input('first_name'),
			'last_name' => $last_name,
			'name_for_invoice' => !empty($request->input('name_for_invoice')) ? $request->input('name_for_invoice') : $request->input('first_name'). ' ' .$last_name,
			'email' => !empty($request->input('email')) ? $request->input('email') : '',
			'phone_number' => !empty($request->input('phone_number')) ? $request->input('phone_number') : '',
			'formated_phone_number' => !empty($request->input('phone_number')) ? FormatUsPhoneNumber::formatPhoneNumber($request->input('phone_number')) : '',
		]);


		$new_level = SalespeopleLevels::find( $request->input( 'level_id' ) );
		if(!empty($new_level) && !empty($new_level->id)) {
			$level_log_created = SalespeoplePecentageLog::create( [
				'level_id'       => $new_level->id,
				'salespeople_id' => $salespeople->id,
				'percentage'     => $new_level->percentage
			] );
		}
		if(empty($level_log_created) || empty($level_log_created->id) ){
			Salespeople::where('id', $salespeople->id)->delete();
			return back()->withErrors( [ 'Can\'t create record' ] )
			             ->withInput();
		}


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
		$user = Auth::user();
		if( $user->hasRole('Salesperson')){
			$salesperson_id = Salespeople::where('email', $user->email)->value('id');
			if($salesperson_id && $id == $salesperson_id) {
				$salespeople = Salespeople::with( 'level.level' )->find( $salesperson_id );
				if ( $salespeople ) {
					return view( 'salespeople.show', compact( 'salespeople' ) );
				}
			}
			return abort(403);
		}
		else {
			$salespeople = Salespeople::with( 'level.level' )->find( $id );
			if ( $salespeople ) {
				return view( 'salespeople.show', compact( 'salespeople' ) );
			}
			return abort( 404 );
		}
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$salespeople = Salespeople::with('level.level')->find($id);
		if($salespeople) {
			$levels = SalespeopleLevels::getIdsAndFullNames();
			return view( 'salespeople.edit', compact( 'salespeople', 'levels' ) );
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
		try {
			$this->validate( $request, [
				'first_name'       => 'required|max:120',
				'last_name'        => 'max:120',
				'name_for_invoice' => 'max:120',
				'email'            => 'required|email|max:120',
				'phone_number'     => 'nullable|max:120|min:10',
				'level_id' => 'required'
			] );

			$last_name = ! empty( $request->input( 'last_name' ) ) ? $request->input( 'last_name' ) : '';

			$salespeople                        = Salespeople::with( 'level' )->find( $id );
			$salespeople->first_name            = $request->input( 'first_name' );
			$salespeople->last_name             = $last_name;
			$salespeople->email                 = ! empty( $request->input( 'email' ) ) ? $request->input( 'email' ) : '';
			$salespeople->name_for_invoice      = ! empty( $request->input( 'name_for_invoice' ) ) ? $request->input( 'name_for_invoice' ) : $request->input( 'first_name' ) . ' ' . $last_name;
			$salespeople->phone_number          = ! empty( $request->input( 'phone_number' ) ) ? $request->input( 'phone_number' ) : '';
			$salespeople->formated_phone_number = ! empty( $request->input( 'phone_number' ) ) ? FormatUsPhoneNumber::formatPhoneNumber( $request->input( 'phone_number' ) ) : '';
			$salespeople->save();

			if ( $salespeople->level->level_id != $request->input( 'level_id' ) ) {
				$new_level = SalespeopleLevels::find($request->input( 'level_id' ));
				SalespeoplePecentageLog::create([
					'level_id' => $new_level->id,
					'salespeople_id' => $salespeople->id,
					'percentage' => $new_level->percentage
				]);
			}

			return redirect()->route( 'salespeople.index' )
			                 ->with( 'success', 'Salesperson updated successfully' );
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleController',
				'function' => 'update'
			]);
			return back()->withErrors( [ $ex->getMessage() ] )
			                    ->withInput();
		}
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
