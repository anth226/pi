<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

use Spatie\Permission\Models\Role;
use DB;
use Hash;


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
		$this->middleware('permission:user-list|view-admin-pages|role-create|role-edit|role-delete' , ['only' => ['index','show']]);
		$this->middleware('permission:user-create' , ['only' => ['store','create']]);
		$this->middleware('permission:user-edit' , ['only' => ['edit','update']]);
		$this->middleware('permission:user-delete' , ['only' => ['destroy']]);

	}

	public function index_v2()
	{
		$users = User::get();

		return view('users', compact('users'));
	}

	public function index(Request $request)
	{
		$data = User::orderBy('id','DESC')->paginate(5);
		return view('users.index',compact('data'))
			->with('i', ($request->input('page', 1) - 1) * 5);
	}

	public function approve($user_id)
	{
		$user = User::findOrFail($user_id);
		$user->update(['approved_at' => now()]);

		return redirect()->route('admin.users.index')->withMessage('User approved successfully');
	}

	public function disapprove($user_id)
	{
		$user = User::findOrFail($user_id);
		$user->update(['approved_at' => NULL]);

		return redirect()->route('admin.users.index')->withMessage('User disapprove successfully');
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
		$user->assignRole($request->input('roles'));


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
		return view('users.show',compact('user'));
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
		$roles = Role::pluck('name','name')->all();
		$userRole = $user->roles->pluck('name','name')->all();


		return view('users.edit',compact('user','roles','userRole'));
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
		$user->update($input);
		DB::table('model_has_roles')->where('model_id',$id)->delete();


		$user->assignRole($request->input('roles'));


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
		return redirect()->route('users.index')
		                 ->with('success','User deleted successfully');
	}

}
