<?php

namespace App\Http\Controllers;

use App\ActionsLog;
use Illuminate\Http\Request;
use App\User;

use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware(['auth','verified']);
		$this->middleware('permission:user-list|user-create|user-edit|user-delete' , ['only' => ['index','show']]);
		$this->middleware('permission:user-create' , ['only' => ['store','create']]);
		$this->middleware('permission:user-edit' , ['only' => ['edit','update']]);
		$this->middleware('permission:user-delete' , ['only' => ['destroy']]);

	}

	public function index(Request $request)
	{
		$data = User::orderBy('id','DESC')->paginate(100);
		return view('users.index',compact('data'))
			->with('i', ($request->input('page', 1) - 1) * 100);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		$roles = Role::pluck('name','name')->all();
		return view('users.create',compact('roles'));
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
			'name' => 'required',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|same:confirm-password',
			'roles' => 'required'
		]);


		$input = $request->all();
		$input['password'] = Hash::make($input['password']);


		$user = User::create($input);

		$user_logged = Auth::user();
		ActionsLog::create([
			'user_id' => $user_logged->id,
			'model' => 4,
			'action' => 0,
			'related_id' => $user->id
		]);

		$user->assignRole($request->input('roles'));

		$user->sendEmailVerificationNotification();

		return redirect()->route('users.index')
		                 ->with('success','User created successfully');
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$user = User::find($id);
		if($user) {
			return view( 'users.show', compact( 'user' ) );
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
		$user = User::find($id);
		if($user) {
			$roles    = Role::pluck( 'name', 'name' )->all();
			$userRole = $user->roles->pluck( 'name', 'name' )->all();


			return view( 'users.edit', compact( 'user', 'roles', 'userRole' ) );
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
			'name' => 'required',
			'email' => 'required|email|unique:users,email,'.$id,
			'password' => 'same:confirm-password',
			'roles' => 'required'
		]);


		$input = $request->all();
		if(!empty($input['password'])){
			$input['password'] = Hash::make($input['password']);
		}else{
			$input = array_except($input,array('password'));
		}


		$user = User::find($id);

		$user_before_update = User::where('id', $id)->first()->toArray();

		$user_logged = Auth::user();
		if($user_before_update && count($user_before_update)) {
			foreach($user_before_update as $field_name => $old_value) {
				if(isset($input[$field_name]) && $input[$field_name] != $old_value && $field_name != 'created_at' && $field_name != 'updated_at' && $field_name != 'deleted_at' ) {
					ActionsLog::create( [
						'user_id'    => $user_logged->id,
						'model'      => 4,
						'field_name' => $field_name,
						'old_value' => $old_value,
						'new_value' => $input[$field_name],
						'action'     => 1,
						'related_id' => $id
					] );
				}
			}
		}

		$user->update($input);

		$roles_before_update = $user->getRoleNames();
//		dd($roles_before_update->toArray());
		$old_value = !empty($roles_before_update) ? implode(', ', $roles_before_update->toArray()) : '';

		DB::table('model_has_roles')->where('model_id',$id)->delete();

		$user->assignRole($request->input('roles'));

		$roles_after_update = $user->getRoleNames();
		$new_value = !empty($roles_after_update) ? implode(', ', $roles_after_update->toArray()) : '';

		if($old_value != $new_value) {
			ActionsLog::create( [
				'user_id'    => $user_logged->id,
				'model'      => 4,
				'field_name' => 'roles',
				'old_value'  => $old_value,
				'new_value'  => $new_value,
				'action'     => 1,
				'related_id' => $id
			] );
		}


		return redirect()->route('users.index')
		                 ->with('success','User updated successfully');
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		User::find($id)->delete();
		$user_logged = Auth::user();
		ActionsLog::create([
			'user_id' => $user_logged->id,
			'model' => 4,
			'action' => 2,
			'related_id' => $id
		]);
		return redirect()->route('users.index')
		                 ->with('success','User deleted successfully');
	}

}
