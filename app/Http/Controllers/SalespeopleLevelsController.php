<?php

namespace App\Http\Controllers;

use App\Errors;
use App\Salespeople;
use App\SalespeopleLevels;
use App\SalespeoplePecentageLog;
use Illuminate\Http\Request;
use Validator;
use Exception;

class SalespeopleLevelsController extends Controller
{
	function __construct()
	{
		$this->middleware(['auth']);
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
		$salespeoplelevels = SalespeopleLevels::orderBy('id','DESC')->paginate(100);
		return view('salespeoplelevels.index',compact('salespeoplelevels'))
			->with('i', ($request->input('page', 1) - 1) * 100);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		return view('salespeoplelevels.create');
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		try {
			$this->validate( $request, [
				'title'      => 'required|max:120',
				'percentage' => 'required|numeric|min:0|max:100',
			] );

			SalespeopleLevels::create( [
				'title'     => $request->input( 'title' ),
				'percentage' => $request->input( 'percentage' ),
			] );

			return redirect()->route( 'levels.index' )
			                 ->with( 'success', 'Level created successfully' );
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleLevelsController',
				'function' => 'store'
			]);
			return back()->withErrors( [ $ex->getMessage() ] )
			             ->withInput();
		}
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
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
		$salespeoplelevels = SalespeopleLevels::find($id);
		if($salespeoplelevels) {
			return view( 'salespeoplelevels.edit', compact( 'salespeoplelevels' ) );
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
//				'title'      => 'required|max:120',
				'percentage' => 'required|numeric|min:1|max:100',
			] );

			if(!($this->updateLevelsLog($id, $request->input( 'percentage' )))){
				return back()->withErrors( [ 'Error updating level. Something went wrong.' ] )
				             ->withInput();
			}

			$level = SalespeopleLevels::where('id', $id)->update( [
//				'title'     => $request->input( 'title' ),
				'percentage' => $request->input( 'percentage' ),
			] );

			return redirect()->route( 'levels.index' )
			                 ->with( 'success', 'Level created successfully' );
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleLevelsController',
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
		return abort(404);
	}

	public function updateLevelsLog($level_id, $percentage){
		try{
			$salespeople = Salespeople::with('level')->withTrashed()->get();
			if($salespeople && $salespeople->count()){
				foreach($salespeople as $s){
					if($s->level->level_id == $level_id && $s->level->pecentage != $percentage) {
						SalespeoplePecentageLog::create( [
							'level_id'       => $level_id,
							'salespeople_id' => $s->id,
							'percentage'     => $percentage
						] );
					}
				}
			}
			return true;
		}
		catch(Exception $ex){
			Errors::create([
				'error' => $ex->getMessage(),
				'controller' => 'SalespeopleLevelsController',
				'function' => 'updateLevelsLog'
			]);
			return false;
		}
	}
}
